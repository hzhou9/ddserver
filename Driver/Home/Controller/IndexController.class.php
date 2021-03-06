<?php
use Foundation\JsonResponse;
use Node\Relationship;
use Think\Controller;

class IndexController extends BaseController {

	private $uid;
	private $lat;
	private $lng;

	public function _initialize(){
        parent::_initialize();

		$uid = I('get.uid');
		$uuid = I('get.uuid');
		$this->uid = $uid;
		$data = $this->getUsercache($uuid);
		if($data){
			if ($data['uid'] == $uid) {
				$this->uid = $uid;
				return;
			}
			else{
				$this->ajaxFail();
			}
		}
		else{
			$this->ajaxFail();
		}
	}

    public function index(){
        $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover,{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>','utf-8');
    }

	public function setRelationship($targetId, $action) {
		try {
			list($outgoing, $incoming) = Relationship::modify($this->uid, $targetId, $action);

			$this->ajaxOk([
				'targetId' => $targetId,
				'outgoing' => $outgoing->status,
				'incoming' => $incoming->status,
			]);

		} catch (Exception $e) {
			$this->ajaxMsg($e->getMessage());
		}
	}

	public function getRelationship($targetId) {
		$sourceId = $this->uid;
		$outgoing = Relationship::getInstance($sourceId, $targetId);
		$incoming = Relationship::getInstance($targetId, $sourceId);
		$this->ajaxOk([
			'targetId' => $targetId,
			'outgoing' => $outgoing->status,
			'incoming' => $incoming->status,
		]);
	}

    public function setPushid($pushid){
        $con = array('id'=>$this->uid);
        $arr = array('pushid'=>$pushid);
        $DriverInfo = M('DriverInfo');
        $DriverInfo->where($con)->save($arr);
        
        $result = array();
        $this->ajaxOk($result);
    }

	//返回附近停车场接口
	public function search($lat,$lng){
        //CVS记录查询的位置信息
        $msgs = array();
        $msgs['ip'] = $_SERVER['REMOTE_ADDR'];//用户ip
        $msgs['uid'] = $this->uid;//操作者id
        $msgs['lat'] = $lat;
        $msgs['lng'] = $lng;//新值
        locCSV($msgs);

		$this->lat = $lat;
		$this->lng = $lng;
		$Park = M('ParkInfo');
		$gap = 0.004545;
		$con = array();
		$con['lat'] = array(array('gt',$lat - $gap),array('lt',$lat + $gap));
		$con['lng'] = array(array('gt',$lng - $gap),array('lt',$lng + $gap));
		$now = getdate();
		$startstr='startsun';
		$endstr='endsun';
		switch($now['wday']){
			case 1:
			$startstr='startmon';
			$endstr='endmon';
			break;
			case 2:
			$startstr='starttue';
			$endstr='endtue';
			break;
			case 3:
			$startstr='startwed';
			$endstr='endwed';
			break;
			case 4:
			$startstr='startthu';
			$endstr='endthu';
			break;
			case 5:
			$startstr='startfri';
			$endstr='endfri';
			break;
			case 6:
			$startstr='startsat';
			$endstr='endsat';
			break;
		}
		$nowstr = date("H:i:s");
		$con[$startstr] = array('elt',$nowstr);
		$con[$endstr] = array('gt',$nowstr);
		

		//HardCode 用于测试
		$openid = $this->getOpenID($this->uid);
		$opens = C('OPENID');
		if(in_array($openid, $opens)){
			$con['status'] = array('in', '13,14,3,4');
		}
		else{
			$con['status'] = array('in', '14,4');
		}

		$listdata = $Park->where($con)->select();
		usort($listdata, array($this, "distance_sort"));	//按距离远近排序

		$list = array_slice($listdata,0,10,true);
		//封装返回值
		$result = array();
		foreach($list as $key => $value){
			$tmp = array();
			$tmp['pid'] = $value['id'];
			$tmp['name'] = $value['name'];
			$tmp['rules'] = $value['chargingrules'];
			$tmp['address'] = $value['address'];
            $tmp['address2'] = $value['address2'];
            $tmp['image'] = C('PARK_IMG_QINIU').'/Park/'.$value['image'];
			$tmp['prepay'] = $value['prepay'];
            if($value['pretype'] && $value['pretype'] != ''){
                $tmp['pretype'] = str_replace("／","/",$value['pretype']);
            }
			$tmp['lat'] = $value['lat'];
			$tmp['lng'] = $value['lng'];
			$tmp['spacesum'] = $value['spacesum'];
			$tmp['parkstate'] = $value['parkstate'];
			$tmp['note'] = $value['note'];
            $tmp['carid'] = $this->getDefualtCarid($this->uid);
//			$tmp['dis'] = $this->getDistance($value['lat'],$value['lng'],$this->lat,$this->lng);
//			$tmp['llat'] = $this->lat;
//			$tmp['llng'] = $this->lng;
			array_push($result, $tmp);
		}
		if(count($result) == 0){
			include_once(dirname(__FILE__) . '/../Conf/' . 'config_open_area.php');
			$this->ajaxOk($result,array('area'=>$config_open_area['sh']));
		}else{
			$this->ajaxOk($result);
		}

	}

   //生成预付订单借口-JSAPI
	public function genOrder($pid, $cid = 0){

        include_once(dirname(__FILE__) . '/../Common/Weixin/WxPay/' . 'WxPayPubHelper.php');
        $jsApi = new JsApi_pub();


        //=========步骤1：网页授权获取用户openid============
//        //通过code获得openid
//        $callBackUrl = U('genOrder/pid/'.$pid.'/cid/'.$cid.'/');
//        if (!isset($_GET['code']))
//        {
//            //触发微信返回code码
//            $url = $jsApi->createOauthUrlForCode($callBackUrl);
//            Header("Location: $url");
//        }else
//        {
//            //获取code码，以获取openid
//            $code = $_GET['code'];
//            $jsApi->setCode($code);
//            $openid = $jsApi->getOpenId();
//        }
//        $openid="oMjtxuH5YZ_6TSkGGLUWvW64aiHQ";

        //根据uid到用户表里面取得openid
        $DriverInfo = M('DriverInfo');
        $map = array();
        $map['id'] = $this->uid;
        $openid = $DriverInfo->where($map)->getField('openid');
        if(empty($openid)){
            $this->ajaxMsg("请在微信中支付！");
        }

        //生成订单，处理业务逻辑
        $Park = M('ParkInfo');
		$map = array('id' => $pid);
		$parkinfo = $Park->where($map)->find();
		if(empty($parkinfo)){
			$this->ajaxMsg("停车场信息错误");
		}

        $createtime = date("Y-m-d H:i:s");
        $Order = M('ParkOrder');
        //设置订单
		$arr['uid'] = I('get.uid');
		$arr['pid'] = $pid;
        $arr['carid'] = $this->getDefualtCarid(I('get.uid'));
		$arr['state'] = -1;
		$arr['startime'] = date("Y-m-d H:i:s",0);
		$arr['endtime'] = date("Y-m-d H:i:s",0);
		$arr['creater'] = $this->uid;
		$arr['createtime'] = $createtime;
		$arr['updater'] = $this->uid;
		$oid = $Order->add($arr);

		if(empty($oid)){
			$this->ajaxMsg("创建订单失败");
		}

        //计算价格是否受驻场活动影响
        $remainFee = $parkinfo['prepay'];
        $remainFee_e = $remainFee;
        if($createtime > $parkinfo['e_start'] && $createtime < $parkinfo['e_end']){//活动期间
            if($parkinfo['e_t']&1){//只限第一单用户
                $con2 = array('uid'=>$this->uid,'state'=>array('neq',-1));
                if(!$Order->where($con2)->find()){
                    $showevent = true;
                }
            }else{//全部用户
                $showevent = true;
            }
        }
        if($showevent){
            if($parkinfo['e_t']&2){//固定价格
                $remainFee_e = $parkinfo['e_p'];
            }else{
                $remainFee_e -= $parkinfo['e_p'];
                if($remainFee_e <= 0){
                    $remainFee_e = 0.01;
                }
            }
        }

        //计算折扣劵
		$remianFee_r = $remainFee_e;
		if($cid > 0){
			$cpamount = $this->_checkCoupon($this->uid, $cid, $remianFee_r);
			//0				抵用劵不存在
			//-1			已领完
			//-2			活动还没开始
			//-3			活动已结束
			//int			抵扣金额
			if($cpamount == 0){
				$this->ajaxMsg("该抵用劵信息不正确，请重新选择");
			}else if($cpamount == -1){
				$this->ajaxMsg("该抵用劵已被使用过，请重新选择");
			}else if($cpamount == -2){
				$this->ajaxMsg("该抵用劵活动尚未开始，请重新选择");
			}else if($cpamount == -3){
				$this->ajaxMsg("该抵用劵已过期，请重新选择");
			}
			$remianFee_r-=$cpamount;
		}
		$currentTime = time();
		$Payment = M('PaymentRecord');
		$temp['oid'] = $oid;
		$temp['money'] = $remainFee;
		$temp['money_r'] = $remianFee_r;
		$temp['cid'] = $cid;
		$temp['state'] = 0;
		$temp['creater'] = $this->uid;
		$temp['createtime'] = date("Y-m-d H:i:s",$currentTime);
		$temp['updater'] = $this->uid;
		$prid = $Payment->add($temp);

		if(empty($prid)){
			$this->ajaxMsg("创建支付消息失败");
		}

		$trade_no = date("YmdHis",$currentTime).$prid;
		//HardCode 测试人员生成订单0.01元
		//if($parkinfo['status'] == 2){
			//$fee = 0.01;
		//}
		//else{
			$fee = $remianFee_r;
		//}

        $fee = round($fee ,2);//防止float精度丢失问题

        //=========步骤2：使用统一支付接口，获取prepay_id============
        //使用统一支付接口
        $unifiedOrder = new UnifiedOrder_pub();

        //设置统一支付接口参数
        //设置必填参数
        //appid已填,商户无需重复填写
        //mch_id已填,商户无需重复填写
        //noncestr已填,商户无需重复填写
        //spbill_create_ip已填,商户无需重复填写
        //sign已填,商户无需重复填写
        $unifiedOrder->setParameter("openid","$openid");//商品描述
        $unifiedOrder->setParameter("body","预付停车费：".$fee);//商品描述
        $unifiedOrder->setParameter("out_trade_no",$trade_no);//商户订单号
        $unifiedOrder->setParameter("total_fee",$fee*100);//总金额
        include_once(dirname(__FILE__) . '/../Conf/' . 'config_biz.php');
        $unifiedOrder->setParameter("notify_url", $wx_notify_url);//通知地址
        $unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);

        $jsApiParameters = $jsApi->getParameters();


		$result = array();
		$result['oid'] = $oid;
		$result['paydata'] = json_decode($jsApiParameters);
		$this->ajaxOk($result);

	}

	/**
	 * @param $pid String Park Id
	 * @return String Order Id or NULL
	 */
	private function createOrder($pid, $createtime) {
		$Order = M('ParkOrder');
		$arr['uid'] = I('get.uid');
		$arr['pid'] = $pid;
		$arr['carid'] = $this->getDefualtCarid(I('get.uid'));
		$arr['state'] = -1;
		$arr['startime'] = date("Y-m-d H:i:s", 0);
		$arr['endtime'] = date("Y-m-d H:i:s", 0);
		$arr['creater'] = $this->uid;
		$arr['createtime'] = $createtime;
		$arr['updater'] = $this->uid;
		return $Order->add($arr);
	}

	/**
	 * @param $oid String Order Id
	 * @return String PaymentRecord Id or NULL
	 */
	private function createPaymentRecord($oid, $cid, $remainFee, $remianFee_r) {
		$Payment = M('PaymentRecord');
		$temp['oid'] = $oid;
		$temp['money'] = $remainFee;
		$temp['money_r'] = $remianFee_r;
		$temp['cid'] = $cid;
		$temp['state'] = 0;
		$temp['creater'] = $this->uid;
		$temp['createtime'] = date("Y-m-d H:i:s");
		$temp['updater'] = $this->uid;
		return $Payment->add($temp);
	}

	private function recalcRemainFee($cid, $parkinfo, $createtime) {
		$Order = M('ParkOrder');
		$showevent = false;
		$remainFee_e = $parkinfo['prepay'];
		if ($createtime > $parkinfo['e_start'] && $createtime < $parkinfo['e_end']) {//活动期间
			if ($parkinfo['e_t'] & 1) {//只限第一单用户
				$con2 = array('uid' => $this->uid, 'state' => array('neq',-1));
				if (!$Order->where($con2)->find()) {
					$showevent = true;
				}
			} else {//全部用户
				$showevent = true;
			}
		}
		if ($showevent) {
			if ($parkinfo['e_t'] & 2) {//固定价格
				$remainFee_e = $parkinfo['e_p'];
			} else {
				$remainFee_e -= $parkinfo['e_p'];
				if ($remainFee_e <= 0) {
					$remainFee_e = 0.01;
				}
			}
		}

		//计算折扣劵
		$remianFee_r = $remainFee_e;
		if ($cid > 0) {
			$cpamount = $this->_checkCoupon($this->uid, $cid, $remianFee_r);
			//0				抵用劵不存在
			//-1			已领完
			//-2			活动还没开始
			//-3			活动已结束
			//int			抵扣金额
			if ($cpamount == 0) {
				$this->ajaxMsg("该抵用劵信息不正确，请重新选择");
			} else if ($cpamount == -1) {
				$this->ajaxMsg("该抵用劵已被使用过，请重新选择");
			} else if ($cpamount == -2) {
				$this->ajaxMsg("该抵用劵活动尚未开始，请重新选择");
			} else if ($cpamount == -3) {
				$this->ajaxMsg("该抵用劵已过期，请重新选择");
			}
			$remianFee_r -= $cpamount;
		}

		return $remianFee_r;
	}

	/**
	 * @param $pid String Park Id
	 * @param $cid String Coupon Id
	 */
	public function genOrderAlipay($pid, $cid) {
		$Park = M('ParkInfo');
		$createtime = date("Y-m-d H:i:s");

		$map = ['id' => $pid];
		$parkinfo = $Park->where($map)->find();
		if (empty($parkinfo)) {
			$this->ajaxMsg("停车场信息错误");
		}

		//生成订单，处理业务逻辑
		$oid = $this->createOrder($pid, $createtime);
		if (empty($oid)) {
			$this->ajaxMsg("创建订单失败");
		}

		//计算价格是否受驻场活动影响
		$remianFee = $this->recalcRemainFee($cid, $parkinfo, $createtime);

		//生成支付凭证
		$prid = $this->createPaymentRecord($oid, $cid, $parkinfo['prepay'], $remianFee);
		if (empty($prid)) {
			$this->ajaxMsg("创建支付消息失败");
		}

		$tradeNo = date("YmdHis") . $prid;
		//HardCode 测试人员生成订单0.01元
		//if($parkinfo['status'] == 2){
		//$fee = 0.01;
		//}
		//else{
		$fee = $remianFee;
		//}

		$fee = round($fee, 2);

		//返回订单信息
		$body = "#{$oid}";
		$subject = "预付停车费：{$fee}";
		$price = $fee;
		$notifyUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/driver.php/Home/public/alipay_notify/';
		$result = compact('subject', 'body', 'price', 'notifyUrl', 'tradeNo', 'oid');
		$this->ajaxOk($result);
	}

	//生成预付订单借口-APP
    public function genOrderAPP($pid, $cid = 0){

        //生成订单，处理业务逻辑
        $Park = M('ParkInfo');
        $map = array('id' => $pid);
        $parkinfo = $Park->where($map)->find();
        if(empty($parkinfo)){
            $this->ajaxMsg("停车场信息错误");
        }

        $createtime = date("Y-m-d H:i:s");
        $Order = M('ParkOrder');
        $arr['uid'] = I('get.uid');
        $arr['pid'] = $pid;
        $arr['carid'] = $this->getDefualtCarid(I('get.uid'));
        $arr['state'] = -1;
        $arr['startime'] = date("Y-m-d H:i:s",0);
        $arr['endtime'] = date("Y-m-d H:i:s",0);
        $arr['creater'] = $this->uid;
        $arr['createtime'] = $createtime;
        $arr['updater'] = $this->uid;
        $oid = $Order->add($arr);

        if(empty($oid)){
            $this->ajaxMsg("创建订单失败");
        }

        //计算价格是否受驻场活动影响
        $remainFee = $parkinfo['prepay'];
        $remainFee_e = $remainFee;
        if($createtime > $parkinfo['e_start'] && $createtime < $parkinfo['e_end']){//活动期间
            if($parkinfo['e_t']&1){//只限第一单用户
                $con2 = array('uid'=>$this->uid,'state'=>array('neq',-1));
                if(!$Order->where($con2)->find()){
                    $showevent = true;
                }
            }else{//全部用户
                $showevent = true;
            }
        }
        if($showevent){
            if($parkinfo['e_t']&2){//固定价格
                $remainFee_e = $parkinfo['e_p'];
            }else{
                $remainFee_e -= $parkinfo['e_p'];
                if($remainFee_e <= 0){
                    $remainFee_e = 0.01;
                }
            }
        }
        
        //计算折扣劵
        $remianFee_r = $remainFee_e;
        if($cid > 0){
            $cpamount = $this->_checkCoupon($this->uid, $cid, $remianFee_r);
            //0				抵用劵不存在
            //-1			已领完
            //-2			活动还没开始
            //-3			活动已结束
            //int			抵扣金额
            if($cpamount == 0){
                $this->ajaxMsg("该抵用劵信息不正确，请重新选择");
            }else if($cpamount == -1){
                $this->ajaxMsg("该抵用劵已被使用过，请重新选择");
            }else if($cpamount == -2){
                $this->ajaxMsg("该抵用劵活动尚未开始，请重新选择");
            }else if($cpamount == -3){
                $this->ajaxMsg("该抵用劵已过期，请重新选择");
            }
            $remianFee_r-=$cpamount;
        }
        $currentTime = time();
        $Payment = M('PaymentRecord');
        $temp['oid'] = $oid;
        $temp['money'] = $remainFee;
        $temp['money_r'] = $remianFee_r;
        $temp['cid'] = $cid;
        $temp['state'] = 0;
        $temp['creater'] = $this->uid;
        $temp['createtime'] = date("Y-m-d H:i:s",$currentTime);
        $temp['updater'] = $this->uid;
        $prid = $Payment->add($temp);

        if(empty($prid)){
            $this->ajaxMsg("创建支付消息失败");
        }

        $trade_no = date("YmdHis",$currentTime).$prid;
        //HardCode 测试人员生成订单0.01元
        //if($parkinfo['status'] == 2){
            //$fee = 0.01;
        //}
        //else{
            $fee = $remianFee_r;
        //}

        $fee = round($fee ,2);

        //调用微信支付
        include_once(dirname(__FILE__) . '/../Common/Weixin/WxPay/' . 'WxPayPubHelper.php');
        $appApi = new AppApi_pub();
        $appApi->setParameter("body","预付停车费：".$fee);//商品描述
        $appApi->setParameter("out_trade_no",$trade_no);//商户订单号
        $appApi->setParameter("total_fee",$fee*100);//总金额
        include_once(dirname(__FILE__) . '/../Conf/' . 'config_biz.php');
        $appApi->setParameter("notify_url", $wx_notify_url);//通知地址
        $appApi->setParameter("trade_type","APP");//交易类型
        $prepay_id = $appApi->getPrepayId();
        if(empty($prepay_id)){
            $this->ajaxMsg('生成预付订单失败！');
        }
        else{
            $result = array();
            $result['oid'] = $oid;
            $result['paydata'] = $appApi->getParameters();
            $this->ajaxOk($result);
        }
    }


	/*
	 * @desc 查询最后的若干数量的订单，或者查询最新的一条未支付订单
	 * @last int 0-所有订单/1-最后订单
	*/

	public  function getOrder($last){
		$Order = M('ParkOrder');
		if($last == 1 ){
			$map = array();
			$map['uid'] = $this->uid;
			$map['state'] = array(0,1,2,'OR');
			$orderData = $Order->where($map)->order('startime desc')->find();
			if(empty($orderData)){
				$this->ajaxOk(null);
			}
			else{
				$this->detailOrder($orderData['id']);
			}
		}
		else{
			$con = array();
			$con['uid'] = $this->uid;
			$con['state'] = array(0,1,2,3,'OR');
			$orderData = $Order->where($con)->order('startime desc')->limit(15)->select();

			$result = array();
			$now = time();
			foreach($orderData as $key => $value){
				$tmp['oid'] = $value['id'];
				$tmp['startTime'] = $value['startime'];
				$tmp['startTimeStamp'] = strtotime($value['startime']);
				$tmp['state'] = $value['state'];
				$tmp['remaintime'] = strtotime($value['endtime'])  - $now;
				$tmp['leaveTimeStamp'] = strtotime($value['leavetime']);
                $tmp['cost'] = $value['cost'];

				$Park = M('ParkInfo');
				$parkInfo = $Park->where('id = '.$value['pid'])->find();
				$tmp['parkname'] = $parkInfo['name'];
				$tmp['address'] = $parkInfo['address'];
				$tmp['lat'] = $parkInfo['lat'];
				$tmp['lng'] = $parkInfo['lng'];

				array_push($result, $tmp);
			}

			$this->ajaxOk($result);
		}


	}
	
	/*
	 * @desc 使用红包来获得折扣劵
	*/
	public  function  openGiftPack($code,$fromid=0){
        //判断来源是否有效
        $valid = $this->_validOpenid($this->uid);
        if(!$valid){
            $this->ajaxMsg("请使用微信客户端打开红包！");
        }


		$coupon = $this->_useGiftPack($this->uid, $code);
		if(is_array($coupon)){
			//log it
			$this->_saveGiftLog($code, 1, $this->uid, $fromid);
		
			$result = array();
			$result['coupon'] = array('id'=>$coupon['id'],'t'=>$coupon['type'],'m'=>$coupon['money'],'e'=>$coupon['endtime']);
			$this->ajaxOk($result);
		}else{
			//0				没有合适的红包
			//-1			已领完
			//-2			活动还没开始
			//-3			活动已结束
			if($coupon == 0){
				$this->ajaxMsg("红包不存在，或您无法领取该红包");
			}else if($coupon == -1){
				$this->ajaxMsg("该红包已被领完，谢谢！");
			}else if($coupon == -2){
				$this->ajaxMsg("该红包活动尚未开始，敬请期待！");
			}else if($coupon == -3){
				$this->ajaxMsg("该红包已过期，谢谢！");
			}else if($coupon == -4){
				$this->ajaxMsg("您已领取过该红包，谢谢！");
			}
		}
	}
	
	/*
	 * @desc 获得折扣劵列表，用于预定界面
	*/
	public  function  listMyCoupons($all=0){
		$result = array();
		$coupons = array();
		$couponArr = $this->_listCoupon($this->uid,$all);
		foreach($couponArr as $key => $value){
            $temp = array('id'=>$value['id'],'t'=>$value['type'],'m'=>$value['money'],'e'=>$value['endtime'], 'u' => $value['status']);
			array_push($coupons,$temp);
		}
        $result['coupon'] = $coupons;
		$this->ajaxOk($result);
	}

	/*
	 * @desc 查询具体订单详情
	 * @last oid 订单号
	*/

	public  function  detailOrder($oid){
		$Payment = M('PaymentRecord');
		$map = array('oid' => $oid, 'state'=>1);
		$payData = $Payment->where($map)->select();

		$preSum = 0;
        $preSum_r = 0;
		foreach($payData as $key => $value){
			$preSum = $preSum + $value['money'];
            $preSum_r = $preSum_r + $value['money_r'];
		}

		$Order = M('ParkOrder');
		$map = array();
		$map['id'] = $oid;
		$orderData = $Order->where($map)->find();
		$totalFee = $this->parkingFee(strtotime($orderData['startime']), $orderData['pid']);
		$remainFee = $totalFee - $preSum;

		$result['oid'] = $oid;
		$result['startTime'] = $orderData['startime'];
		$result['startTimeStamp'] = strtotime($orderData['startime']);
		$result['state'] = $orderData['state'];
		$result['remaintime'] = strtotime($orderData['endtime'])  - time();

		$pid = $orderData['pid'];
		$uid = $orderData['uid'];

		$ParkInfo = M('ParkInfo');
		$con = array('id' => $pid);
		$parkData = $ParkInfo->where($con)->find();
		$result['address'] = $parkData['address'];
        $result['address2'] = $parkData['address2'];
        $result['image'] = C('PARK_IMG_QINIU').'/Park/'.$parkData['image'];
		$result['lat'] = $parkData['lat'];
		$result['lng'] = $parkData['lng'];
		$result['name'] = $parkData['name'];
        $result['rule'] = $parkData['chargingrules'];

		$Driver = M('DriverInfo');
		$con = array('id' => $uid);
		$driverData = $Driver->where($con)->find();
		$result['carid'] = $driverData['carid'];

		$result['totalFee'] = round($totalFee,2);
		$result['remainFee'] = round($remainFee,2);
        $result['cost_r'] = round($preSum_r,2);
		
		$ParkAdmin = M('ParkAdmin');
		//$con = "parkid=".$pid." && jobfunction&1<>0";
        $con = "parkid=".$pid;
		$adminData = $ParkAdmin->where($con)->order('lastop desc')->field("nickname,phone")->select();
        //加入停车场销售负责人电话
        $responsible = $parkData['responsible'];
        $responsible = empty($responsible) ? C('DEFAULT_SALES') : $responsible;
        $Sales = M('SalesAuth');
        $responsTel= $Sales->where(array('id' => $responsible))->getField("telephone");
        $t = array('nickname' => "嘟嘟客服", 'phone' => $responsTel);
        array_push($adminData, $t);
        $result['admin'] = $adminData;


		//折扣卷
		$result['coupon'] = array();
		$couponArr = $this->_listCoupon($uid);
		foreach($couponArr as $key => $value){
			$result['coupon'][$value['id']] = array('t'=>$value['type'],'m'=>$value['money'],'e'=>$value['endtime']);
		}
		

		$this->ajaxOk($result);

	}

	/*
	 * @desc 车费结算借口
	 * @oid	订单id
	*/

	public  function checkOut($oid, $cid = 0, $fee = 0){

        include_once(dirname(__FILE__) . '/../Common/Weixin/WxPay/' . 'WxPayPubHelper.php');
        $jsApi = new JsApi_pub();


        //=========步骤1：网页授权获取用户openid============
//        //通过code获得openid
//        $callBackUrl = U('genOrder/pid/'.$pid.'/cid/'.$cid.'/');
//        if (!isset($_GET['code']))
//        {
//            //触发微信返回code码
//            $url = $jsApi->createOauthUrlForCode($callBackUrl);
//            Header("Location: $url");
//        }else
//        {
//            //获取code码，以获取openid
//            $code = $_GET['code'];
//            $jsApi->setCode($code);
//            $openid = $jsApi->getOpenId();
//        }
//        $openid="oMjtxuH5YZ_6TSkGGLUWvW64aiHQ";

        //根据uid到用户表里面取得openid
        $DriverInfo = M('DriverInfo');
        $map = array();
        $map['id'] = $this->uid;
        $openid = $DriverInfo->where($map)->getField('openid');
        if(empty($openid)){
            $this->ajaxMsg("请在微信中支付！");
        }

        //业务逻辑
        $Order = M('ParkOrder');
        $map = array();
        $map['id'] = $oid;
        $orderData = $Order->where($map)->find();
        $remainFee = $fee;
		$Payment = M('PaymentRecord');
        if($remainFee == 0){
            $map = array('oid' => $oid, 'state'=>1);
            $preSum = $Payment->where($map)->sum('money');
            $totalFee = $this->parkingFee(strtotime($orderData['startime']), $orderData['pid']);
            $remainFee = $totalFee - $preSum;
        }
		
		//计算折扣劵
		$remianFee_r = $remainFee;
		if($cid > 0){
			$cpamount = $this->_checkCoupon($this->uid, $cid, $remainFee);
			//0				抵用劵不存在
			//-1			已领完
			//-2			活动还没开始
			//-3			活动已结束
			//int			抵扣金额
			if($cpamount == 0){
				$this->ajaxMsg("该抵用劵信息不正确，请重新选择");
			}else if($cpamount == -1){
				$this->ajaxMsg("该抵用劵已被使用过，请重新选择");
			}else if($cpamount == -2){
				$this->ajaxMsg("该抵用劵活动尚未开始，请重新选择");
			}else if($cpamount == -3){
				$this->ajaxMsg("该抵用劵已过期，请重新选择");
			}
			$remianFee_r-=$cpamount;
		}

		$currentTime = time();
		$temp['oid'] = $oid;
		$temp['money'] = $remainFee;
		$temp['money_r'] = $remianFee_r;
		$temp['cid'] = $cid;
		$temp['state'] = 0;
		$temp['creater'] = $this->uid;
		$temp['createtime'] = date("Y-m-d H:i:s",$currentTime);
		$temp['updater'] = $this->uid;
		$prid = $Payment->add($temp);

		if(empty($prid)){
			$this->ajaxMsg("创建支付消息失败");
		}

		$trade_no = date("YmdHis",$currentTime).$prid;

		//HardCode 测试人员结算订单0.01元
		$parkid = $orderData['pid'];
		$Park = M('ParkInfo');
		$map = array();
		$map['id'] = $parkid;
		$parkinfo = $Park->where($map)->find();
		//if($parkinfo['status'] == 2){
			//$fee = 0.01;
		//}
		//else{
			$fee = $remianFee_r;
		//}

        $fee = round($fee ,2);//防止0.01浮点数精度丢失

        //=========步骤2：使用统一支付接口，获取prepay_id============
        //使用统一支付接口
        $unifiedOrder = new UnifiedOrder_pub();

        //设置统一支付接口参数
        //设置必填参数
        //appid已填,商户无需重复填写
        //mch_id已填,商户无需重复填写
        //noncestr已填,商户无需重复填写
        //spbill_create_ip已填,商户无需重复填写
        //sign已填,商户无需重复填写
        $unifiedOrder->setParameter("openid","$openid");//商品描述
        $unifiedOrder->setParameter("body","结算停车费(还需付款)：".$fee);//商品描述
        $unifiedOrder->setParameter("out_trade_no",$trade_no);//商户订单号
        $unifiedOrder->setParameter("total_fee",$fee*100);//总金额
        include_once(dirname(__FILE__) . '/../Conf/' . 'config_biz.php');
        $unifiedOrder->setParameter("notify_url",$wx_checkout_notify_url);//通知地址
        $unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);

        $jsApiParameters = $jsApi->getParameters();

        $result = json_decode($jsApiParameters);
        $this->ajaxOk($result);

	}

    /*
	 * @desc App支付结算接口
	 * @oid	订单id,cid 折扣券id
	*/

    public  function checkOutApp($oid, $cid = 0, $fee = 0){
        //业务逻辑部分
        $Order = M('ParkOrder');
        $map = array();
        $map['id'] = $oid;
        $orderData = $Order->where($map)->find();
        $remainFee = $fee;
        $Payment = M('PaymentRecord');
        if($remainFee == 0){
            $map = array('oid' => $oid, 'state'=>1);
            $preSum = $Payment->where($map)->sum('money');
            $totalFee = $this->parkingFee(strtotime($orderData['startime']), $orderData['pid']);
            $remainFee = $totalFee - $preSum;
        }
        //计算折扣劵
        $remianFee_r = $remainFee;
        if($cid > 0){
            $cpamount = $this->_checkCoupon($this->uid, $cid, $remainFee);
            //0				抵用劵不存在
            //-1			已领完
            //-2			活动还没开始
            //-3			活动已结束
            //int			抵扣金额
            if($cpamount == 0){
                $this->ajaxMsg("该抵用劵信息不正确，请重新选择");
            }else if($cpamount == -1){
                $this->ajaxMsg("该抵用劵已被使用过，请重新选择");
            }else if($cpamount == -2){
                $this->ajaxMsg("该抵用劵活动尚未开始，请重新选择");
            }else if($cpamount == -3){
                $this->ajaxMsg("该抵用劵已过期，请重新选择");
            }
            $remianFee_r-=$cpamount;
        }

        $currentTime = time();
        $temp['oid'] = $oid;
        $temp['money'] = $remainFee;
        $temp['money_r'] = $remianFee_r;
        $temp['cid'] = $cid;
        $temp['state'] = 0;
        $temp['creater'] = $this->uid;
        $temp['createtime'] = date("Y-m-d H:i:s",$currentTime);
        $temp['updater'] = $this->uid;
        $prid = $Payment->add($temp);

        if(empty($prid)){
            $this->ajaxMsg("创建支付消息失败");
        }

        $trade_no = date("YmdHis",$currentTime).$prid;

        //HardCode 测试人员结算订单0.01元
        $parkid = $orderData['pid'];
        $Park = M('ParkInfo');
        $map = array();
        $map['id'] = $parkid;
        $parkinfo = $Park->where($map)->find();
        //if($parkinfo['status'] == 2){
            //$fee = 0.01;
        //}
        //else{
            $fee = $remianFee_r;
        //}

        $fee = round($fee ,2);//防止0.01浮点数精度丢失

        //微信支付返回参数
        include_once(dirname(__FILE__) . '/../Common/Weixin/WxPay/' . 'WxPayPubHelper.php');
        $appApi = new AppApi_pub();
        $appApi->setParameter("body","结算停车费(还需付款)：".$fee);//商品描述
        $appApi->setParameter("out_trade_no",$trade_no);//商户订单号
        $appApi->setParameter("total_fee",$fee*100);//总金额
        include_once(dirname(__FILE__) . '/../Conf/' . 'config_biz.php');
        $appApi->setParameter("notify_url", $wx_checkout_notify_url);//通知地址
        $appApi->setParameter("trade_type","APP");//交易类型
        $prepay_id = $appApi->getPrepayId();
        if(empty($prepay_id)){
            $this->ajaxMsg('生成预付订单失败！');
        }
        else{
            $this->ajaxOk($appApi->getParameters());
        }
    }


	/*
     *  @desc 车辆离场
	 *  @param oid	订单id
    */
	public function setLeave($oid){
		$Order = M('ParkOrder');
		$con = array('id' => $oid, 'uid' => $this->uid, 'state'=>2);
		$updateData['state'] = 3;
		$updateData['leavetime'] = date('Y-m-d H:i:s');
		$updateData['updater'] = $this->uid;
		$updateData['driverleave'] = 1;
		$orderData = $Order->where($con)->save($updateData);
		/*
		if($orderData !== false){
			$this->ajaxOk("");
		}
		else{
			$this->ajaxMsg("手工操作离场失败！");
		}
		*/
		$this->ajaxOk("");

	}


    /*
     *  @desc 获取车主基本信息
    */
    public function getDriverInfo(){
        $DriverInfo = M('DriverInfo');
        $map = array();
        $map['id'] = $this->uid;
        $dirver = $DriverInfo->where($map)->find();
        $DriverCar = M('DriverCar');
        $map = array();
        $map['driverid'] = $this->uid;
        $cars = $DriverCar->where($map)->select();

        $result = array();
        $result['telephone'] = $dirver['telephone'];
        $result['carids'] = $cars;
        
        $nowStr = date("Y-m-d H:i:s");
        //优惠券数量
        $CouponInfo = M('DriverCoupon');
        $map = array();
        $map['uid'] = $this->uid;
        $map['status'] = 0;
        $map['endtime'] = array('gt',$nowStr);
        $count = $CouponInfo->where($map)->count();
        $result['c_count'] = $count;
        
        //最近一条订单信息
        $Order = M('ParkOrder');
        $map = array();
        $map['uid'] = $this->uid;
        $map['state'] = array(0,1,2,'OR');
        $orderData = $Order->where($map)->order('startime desc')->find();
        if($orderData){
            $tmp = array();
            $tmp['oid'] = $orderData['id'];
            $tmp['startTime'] = $orderData['startime'];
            //$tmp['startTimeStamp'] = strtotime($orderData['startime']);
            //$tmp['state'] = $orderData['state'];
            //$tmp['remaintime'] = strtotime($orderData['endtime'])  - $now;
            //$tmp['leaveTimeStamp'] = strtotime($orderData['leavetime']);
            $tmp['cost'] = $orderData['cost'];
            
            $Park = M('ParkInfo');
            $parkInfo = $Park->where('id = '.$orderData['pid'])->find();
            $tmp['parkname'] = $parkInfo['name'];
            $tmp['address'] = $parkInfo['address'];
            //$tmp['lat'] = $parkInfo['lat'];
            //$tmp['lng'] = $parkInfo['lng'];
            $result['l_order'] = $tmp;
        }

        $this->ajaxOk($result);


    }

    /*
     *  @desc 新增车牌
     *  @param carid	车牌号码
    */
    public function addCarid($carid){

        $DriverCar = M('DriverCar');
        $map = array();
        $map['driverid'] = $this->uid;
        $cars = $DriverCar->where($map)->select();

        //判断是否车牌是否重复
        foreach($cars as $key => $value){
            if($carid == $value['carid']){
                $this->ajaxMsg('您已经拥有此车牌！');
            }
        }

        //如果是第一两车，默认设置成默认车辆
        $sum = count($cars);
        if($sum == 0 ){
            $status = 1;
        }else{
            $status = 0;
        }

        //保存数据
        $temp = array();
        $temp['driverid'] = $this->uid;
        $temp['carid'] = $carid;
        $temp['status'] = $status;
        $temp['creater'] = $this->uid;
        $temp['createtime'] = date("Y-m-d H:i:s",time());
        $temp['updater'] = $this->uid;
        $DriverCar->add($temp);

        $this->getDriverInfo();


    }

    /*
     *  @desc 修改车牌
     *  @param id	车牌表id
     *  @param newCarid	新车牌号码
    */
    public function modifyCarid($id,$newCarid){

        $DriverCar = M('DriverCar');
        $map = array();
        $map['driverid'] = $this->uid;
        $cars = $DriverCar->where($map)->select();

        //判断是否车牌是否重复
        foreach($cars as $key => $value){
            if($newCarid == $value['carid']){
                $this->ajaxMsg('您已经拥有此车牌！');
            }
        }

        //保存数据
        $map = array();
        $map['id'] = $id;

        $data  = array();
        $data['carid'] = $newCarid;
        $data['updater'] = $this->uid;
        $DriverCar->where($map)->save($data);

        $this->getDriverInfo();


    }

    /*
    *  @desc 设置默认车牌
    *  @param id	车牌表id
   */
    public function setDefaultCar($id){

        //把原先默认的车设成0
        $DriverCar = M('DriverCar');
        $map = array();
        $map['driverid'] = $this->uid;
        $map['status'] = 1;
        $data['status'] = 0;
        $data['updater'] = $this->uid;
        $DriverCar->where($map)->save($data);


        //保存数据
        $map = array();
        $map['id'] = $id;

        $data  = array();
        $data['status'] = 1;
        $data['updater'] = $this->uid;
        $DriverCar->where($map)->save($data);

        $this->ajaxOk('');


    }
    
    public function addfreepark($name, $lat, $lng, $dsc, $note){
        $arr = array('name'=>$name,'lat'=>$lat,'lng'=>$lng,'dsc'=>$dsc,'note'=>$note,'creater'=>$this->uid,'createtime'=>date("Y-m-d H:i:s"));
        $ParkFree = M('ParkFreeInfo');
        $id = $ParkFree->add($arr);
        if(empty($id)){
            $this->ajaxMsg("提交数据失败，请检查您的输入格式是否有误");
        }
        $title = '[UGC-免费停车场]';
        $content = '停车场名称:'.$name .'<br>描述:'.$dsc.'<br>提交者ID:'.$this->uid.'<br>';
        sendMail('all@duduche.me', $title, $content);
        $result = array('id'=>$id);
        $this->ajaxOk($result);
    }

    //用户提交的付费停车场
    public function addpaidpark($name, $lat, $lng, $dsc, $note){
        $ParkPaid = M('ParkPaidInfo');
        $data = array();
        $data['name'] = $name;
        $data['lat'] = $lat;
        $data['lng'] = $lng;
        $data['dsc'] = $dsc;
        $data['note'] = $note;
        $data['status'] = 0;
        $data['creater'] = $this->uid;
        $data['createtime'] = date("Y-m-d H:i:s");
        $data['updater'] = $this->uid;

        $id = $ParkPaid->add($data);
        if(empty($id)){
            $this->ajaxMsg("提交数据失败，请检查您的输入格式是否有误");
        }
        $title = '[UGC-付费停车场]';
        $content = '停车场名称:'.$name .'<br>描述:'.$dsc.'<br>提交者ID:'.$this->uid.'<br>';
        sendMail('all@duduche.me', $title, $content);
        $result = array('id'=>$id);
        $this->ajaxOk($result);
    }

	//获得IP地址
	protected function get_client_ip() {
		if ($_SERVER['REMOTE_ADDR']) { $cip = $_SERVER['REMOTE_ADDR']; }
		elseif (getenv("REMOTE_ADDR")) { $cip = getenv("REMOTE_ADDR");  }
		elseif (getenv("HTTP_CLIENT_IP")) { $cip = getenv("HTTP_CLIENT_IP"); }
		else {  $cip = "127.0.0.1"; }

		return $cip;
	}

	public function test($phone){
     		$result = array(
						'code'=>100,
						'data'=>'Hello,'.$phone.'!'
				  );

    		$this->ajaxReturn($result,'jsonp');
    }
    
    
	public function open_wx_sign($url=null){
		//$url2 = "http://t.duduche.me/html/userhtml/index.html?m=myjiesuan";
		//$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    //$url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$signPackage = $this->GetSignPackage($url);
		//$signPackage['v'] = strcmp($url,$url2);
		/**/
		$result=array("appId" => $signPackage["appId"]
			, "timestamp" => $signPackage["timestamp"]
			, "nonceStr" => $signPackage["nonceStr"]
			, "signature" => $signPackage["signature"]	
		);
		$this->ajaxOk($result);
	}
}