<?php

/**
 * 后台配置文件
 * 所有除开系统级别的后台配置
 */
return array(
    /* 数据缓存设置 */
    'DATA_CACHE_PREFIX'    => 'dudusales_', // 缓存前缀
    'DATA_CACHE_TYPE'      => 'File', // 数据缓存类型
    

    /* 用户相关设置 */
    'USER_MAX_CACHE'     => 1000, //最大缓存用户数

    /* 数据库配置 */
    'DB_TYPE'   => 'mysqli', // 数据库类型
    'DB_HOST'   => '127.0.0.1', // 服务器地址
    'DB_NAME'   => 'dudu_parking', // 数据库名
    'DB_USER'   => 'root', // 用户名
    'DB_PWD'    => '',  // 密码
    'DB_PORT'   => '3306', // 端口
    'DB_PREFIX' => 'dudu_', // 数据库表前缀
    
    //应用类库不用命名空间
    'APP_USE_NAMESPACE'    =>    false,
    
    /* 调试配置 */
    'SHOW_PAGE_TRACE' => true,
    
    /* URL配置 */
    'URL_CASE_INSENSITIVE' => true, //默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL'            => 1, //URL模式
    'URL_HTML_SUFFIX'       =>  NULL,
    'URL_PATHINFO_DEPR'    => '/', //PATHINFO URL分割符   
    
    /* 模板相关配置 */
    'TMPL_PARSE_STRING' => array(
        '__STATIC__' => __ROOT__ . '/Public/static',
        '__ADDONS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/Addons',
        '__IMG__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/images',
        '__CSS__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/css',
        '__JS__'     => __ROOT__ . '/Public/' . MODULE_NAME . '/js',
    ),

    /* SESSION 和 COOKIE 配置 */
    'SESSION_PREFIX' => 'dudu_sales', //session前缀
    'COOKIE_PREFIX'  => 'dudu_sales_', // Cookie前缀 避免冲突
    'VAR_SESSION_ID' => 'session_id',	//修复uploadify插件无法传递session_id的bug

    /* 后台错误页面模板 */
//    'TMPL_ACTION_ERROR'     =>  MODULE_PATH.'View/default/Public/error.html', // 默认错误跳转对应的模板文件
//    'TMPL_ACTION_SUCCESS'   =>  MODULE_PATH.'View/default/Public/success.html', // 默认成功跳转对应的模板文件
//    'TMPL_EXCEPTION_FILE'   =>  MODULE_PATH.'View/default/Public/exception.html',// 异常页面的模板文件
    
    //模版主题
    'DEFAULT_THEME'  	=> 	'default',
    'THEME_LIST'		=>	'default',
    //'TMPL_DETECT_THEME' => 	true, // 自动侦测模板主题

    //停车场tag
    'PARK_STYLE' => array (
        'DM' => '地面',
        'DXK' => '地下库',
        'LTCK' => '立体车库',
        'LUB' => '路侧边',
        'XQ' => '普通小区',
        'GDXQ' => '高档小区',
        'GWZX' => '购物中心',
        'DXCS' => '大型超市',
        'FWMD' => '消费门店',//原来是服务门店
        'SYXZL' => '商业写字楼',
        'JD' => '酒店',
        'YY' => '医院',
        'DWJG' => '单位机构',
        'SYTCC' => '商业停车场',
        'LYJD' => '景点',
        'ZDZJCR' => '自动闸机进入',
        'USERFX' => '人工放行',
        'ZYSF' => '中央收费',
        'BDWKF' => '不对外开放',
        'LDCD' => '流动车多',
        'SH' => '实惠',
    ),

    //停车场tag类型
    'PARK_STYLE_CAT' => array (
        'DM' => '停车场空间',
        'XQ' => '停车场类型',
        'ZDZJCR' => '收费方式',
        'LDCD' => '停放车辆',
    ),

    //停车场合作状态
    'PARK_COR_STATE' => array (
        'PRETOUCH' => -1,   //未接触
        'TOUCH' => -2,      //在接触
        'FIND' => 0,        //找到决策人
        'TEST' => 2,        //测试中
        'CORP' => 1,        //已合作
        'INFO' => 3,        //已信息化
    ),

    //停车场合作状态
    'PARK_COR' => array (
        '4' => '已合作',
        '3' => '测试中',
        '2' => '找到决策人',
        '1' => '在接触',
        '0' => '未接触',
    ),

    //停车场信息化状态
    'PARK_INF' => array (
        '0' => '不发布',
        '1' => '发布',
    ),


    //停车场活动类型
    'PARK_AC_TYPE' => array (
        0 => null,
        1 => '5元推广补助-离场',
        2 => '5元推广补助-进场',
    ),

    //停车场驻场活动类型
    'PARK_E_TYPE' => array (
        1 => '新用户优惠',
        2 => '固定价格停车',
    ),

    //上传图片的FTP设置
    'UPLOAD_FTP'     =>    array(
        'host'     => '115.29.160.95', //服务器
        'port'     => 21, //端口
        'timeout'  => 90, //超时时间
        'username' => 'www', //用户名
        'password' => '2aed6eb9d'//密码
    ),

    //FTP上传地址
    'PARK_UPLOAD_PATH' => './default/Public/Uploads/Park/',

    //图片相对访问路径
    'PARK_IMG_PATH' =>  './Public/Uploads/Park/',

    //图片七牛访问路径
    'PARK_IMG_QINIU' =>'http://7xispd.com1.z0.glb.clouddn.com',

    //附近停车场距离，单位米
    'NEAR_DIS' => 500,

    //规则说明相对地址
    'PARK_RULES_PATH' => './Public/Uploads/parkrules/rules.html',

    'SH_OPEN_AREA'    =>  array(//1级地名，1级地名描述，2级地名列表
        array('淮海路','香港广场、百盛、环贸iapm、新天地等',array(
            array('香港广场',31.22296,121.475121),
            array('太平洋百货淮海店',31.222715,121.474144),
            array('K11购物艺术中心',31.223261,121.473666),
            array('新天地',31.220113,121.475281),
            array('环贸iapm',31.215323,121.458345),
            array('百盛购物中心',31.217175,121.459224),
            array('巴黎春天淮海店',31.216809,121.459558))),
        array('人民广场/南京东路','来福士、新世界、置地广场、宏伊广场等',array(
            array('来福士广场',31.232926,121.476764),
            array('百联世茂国际广场',31.234339,121.475708),
            array('新世界城',31.234806,121.473933),
            array('置地广场',31.236206,121.482095),
            array('第一百货',31.235766,121.475),
            array('宏伊广场',31.23707,121.484533),
            array('永安百货',31.23527,121.47834))),
        array('南京西路/静安寺','恒隆广场、梅陇镇广场、久光百货、静安嘉里中心等',array(
            array('恒隆广场',31.227728,121.453647),
            array('梅陇镇广场',31.229353,121.456881),
            array('中信泰富广场',31.228061,121.455709),
            array('玛莎百货',31.230295,121.460227),
            array('金鹰国际广场',31.228378,121.454996),
            array('久光百货',31.223399,121.446181),
            array('静安嘉里中心',31.224596,121.450203))),
        array('徐家汇','港汇广场、美罗城、东方商厦等',array(
            array('港汇恒隆广场',31.194557,121.437259),
            array('美罗城',31.193264,121.439751),
            array('太平洋百货',31.195191,121.438635),
            array('东方商厦',31.192793,121.437952),
            array('汇金百货',31.19543,121.440158))),
        array('陆家嘴','正大广场、国金中心、环球金融中心等',array(
            array('正大广场',31.237351,121.499965),
            array('国金中心商城',31.236516,121.502401),
            array('环球金融中心',31.235058,121.507411))),
        array('八佰伴','第一八佰伴、新梅联合广场、96广场等',array(
            array('第一八佰伴',31.227925,121.517),
            array('新梅联合广场',31.229239,121.516113),
            array('96广场',31.227654,121.525221))),
        array('中山公园','龙之梦、巴黎春天、长宁妇婴医院等',array(
            array('龙之梦中山公园店',31.21911,121.416672),
            array('巴黎春天长宁店',31.218451,121.421166),
            array('长宁妇婴医院',31.212893,121.414531))),
        array('五角场','万达广场、百联又一城、巴黎春天等',array(
            array('万达广场',31.300275,121.513924),
            array('百联又一城',31.301714,121.515523),
            array('巴黎春天五角场店',31.300724,121.514611),
            array('东方商厦',31.298981,121.515675))),
        array('环球港','环球港、沪西工人文化宫、普陀中心医院等',array(
            array('月星环球港',31.231633,121.411393),
            array('长城大厦',31.237912,121.415438),
            array('沪西工人文化宫',31.237549,121.418326),
            array('普陀中心医院',31.241801,121.404627)))
    )

);
