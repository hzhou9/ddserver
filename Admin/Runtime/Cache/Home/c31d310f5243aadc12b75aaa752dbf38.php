<?php if (!defined('THINK_PATH')) exit();?><!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo ($meta_title); ?></title>
    <script>
        /**
         * Created by Common on 14-9-12.
         */
        var deviceAgent = navigator.userAgent.toLowerCase();
        var agentID = deviceAgent.match(/(iphone|ipod|ipad|android)/) || '';
        var mobile = {
            iphone:agentID.indexOf("iphone")>=0
            ,android:agentID.indexOf("android")>=0
            ,ipad:agentID.indexOf("ipad")>=0
            ,ipad:agentID.indexOf("ipod")>=0
        }
        if(mobile.iphone){
            //alert('iphone');
            document.write('<meta name="viewport" content="width=device-width, initial-scale=01, user-scalable=0,minimal-ui">');
        }else if(mobile.android){
            //alert('android');
            document.write('<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=0">');
            //document.write('<meta name="viewport" content="target-densitydpi=device-dpi">');
        }else{
            //alert('other');
            document.write('<meta name="viewport" content="width=device-width, initial-scale=0.5, user-scalable=0,minimal-ui">');
        }
    </script>
    <link href="/ddserver/Public/favicon.ico" type="image/x-icon" rel="shortcut icon">
    <link rel="stylesheet" type="text/css" href="/ddserver/Public/Home/css/base.css" media="all">
    <link rel="stylesheet" type="text/css" href="/ddserver/Public/Home/css/common.css" media="all">
    <link rel="stylesheet" type="text/css" href="/ddserver/Public/Home/css/module.css">
    <link rel="stylesheet" type="text/css" href="/ddserver/Public/Home/css/style.css" media="all">
    <link rel="stylesheet" type="text/css" href="/ddserver/Public/Home/css/mui.min.css" media="all">
    <link rel="stylesheet" type="text/css" href="/ddserver/Public/Home/css/dudu.sales.css" media="all">
     <!--[if lt IE 9]>
    <script type="text/javascript" src="/ddserver/Public/static/jquery-1.10.2.min.js"></script>
    <![endif]--><!--[if gte IE 9]><!-->
    <script type="text/javascript" src="/ddserver/Public/static/jquery-2.0.3.min.js"></script>
    <script type="text/javascript" src="/ddserver/Public/static/jquery-ui.min.js"></script>
    <script type="text/javascript" src="/ddserver/Public/Home/js/jquery.mousewheel.js"></script>
    <!--<![endif]-->
    
    <style>
        body{padding: 0}

        .parkitem{list-style:none;width:490px;padding:5px 0 5px 10px;}

        .gray {background:#ddd;}
    </style>

</head>
<body>
    <!-- 头部 -->
    <div class="header">
        <!-- Logo -->
        <span style="color:white;margin-left:40px;font-size:25px;"><a href="<?php echo U('Index/index');?>">嘟嘟停车</a></span>
            <!-- /Logo -->
        <!-- /Logo -->

        <!-- 主导航 -->
        <ul class="main-nav">
            <?php if(is_array($__MENU__["main"])): $i = 0; $__LIST__ = $__MENU__["main"];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$menu): $mod = ($i % 2 );++$i;?><li class="<?php echo ((isset($menu["class"]) && ($menu["class"] !== ""))?($menu["class"]):''); ?>"><a href="<?php echo (U($menu["url"])); ?>"><?php echo ($menu["title"]); ?></a></li><?php endforeach; endif; else: echo "" ;endif; ?>
        </ul>
        <!-- /主导航 -->

        <!-- 用户栏 -->
        <div class="user-bar">
            <a href="javascript:;" class="user-entrance"><i class="icon-user"></i></a>
            <ul class="nav-list user-menu hidden">
                <li class="manager"><em title="<?php echo session('admin_auth.username');?>"><?php echo session('admin_auth.username');?></em></li>
                <li><a href="<?php echo U('Public/logout');?>">退出</a></li>
            </ul>
        </div>
    </div>
    <!-- /头部 -->

    <!-- 边栏 -->
    <!-- <div class="sidebar">
       <!-- 子导航 -->
        
        <!-- /子导航 -->
    </div>-->
   <!-- /边栏 -->

    <!-- 内容区 -->
    <div id="main-content">
        <div id="top-alert" class="fixed alert alert-error" style="display: none;">
            <button class="close fixed" style="margin-top: 4px;">&times;</button>
            <div class="alert-content">这是内容</div>
        </div>
        <div id="main" class="main">
            
            <!-- nav -->
            <?php if(!empty($_show_nav)): ?><div class="breadcrumb">
                <span>您的位置:</span>
                <?php $i = '1'; ?>
                <?php if(is_array($_nav)): foreach($_nav as $k=>$v): if($i == count($_nav)): ?><span><?php echo ($v); ?></span>
                    <?php else: ?>
                    <span><a href="<?php echo ($k); ?>"><?php echo ($v); ?></a>&gt;</span><?php endif; ?>
                    <?php $i = $i+1; endforeach; endif; ?>
            </div><?php endif; ?>
            <!-- nav -->
            

            
    <!-- 主体 -->
    <div id="indexMain" class="index-main">
        <a class="mui-btn mui-btn-success" href="<?php echo U('giftInfo');?>">新添礼品</a>

        <ul class="mui-table-view">
            <?php
 foreach ($gifts as $key => $value) { $show = array(0 => '否', 1 => '是'); $temp = "<li name='giftitem' class='mui-table-view-cell' alt='".U('giftInfo')."/gid/".$value['id']."/' >
                            <a class='mui-navigate-right'>
                                <p>".$value['name']."</p>
                                <p>礼品积分：".$value['score']."</p><p>是否显示：".$show[$value['valid']]."</p><p>权重(越小越靠前)：".$value['weight']."  </p>
                            </a></li>"; echo $temp; } ?>
        </ul>

    </div>

        </div>
        <div class="cont-ft">
            <div class="copyright">
                <div class="fl"></div>
                <div class="fr"></div>
            </div>
        </div>
    </div>
    <!-- /内容区 -->
    <script type="text/javascript">
    (function(){
        var ThinkPHP = window.Think = {
            "ROOT"   : "/ddserver", //当前网站地址
            "APP"    : "/ddserver/admin.php", //当前项目地址
            "PUBLIC" : "/ddserver/Public", //项目公共目录地址
            "DEEP"   : "<?php echo C('URL_PATHINFO_DEPR');?>", //PATHINFO分割符
            "MODEL"  : ["<?php echo C('URL_MODEL');?>", "<?php echo C('URL_CASE_INSENSITIVE');?>", "<?php echo C('URL_HTML_SUFFIX');?>"],
            "VAR"    : ["<?php echo C('VAR_MODULE');?>", "<?php echo C('VAR_CONTROLLER');?>", "<?php echo C('VAR_ACTION');?>"]
        }
    })();
    </script>
    <script type="text/javascript" src="/ddserver/Public/static/think.js"></script>
    <script type="text/javascript" src="/ddserver/Public/Home/js/common.js"></script>
    <script type="text/javascript">
        +function(){
            var $window = $(window), $subnav = $("#subnav"), url;
            $window.resize(function(){
                $("#main").css("min-height", $window.height() - 130);
            }).resize();

            /* 左边菜单高亮 */
            url = window.location.pathname + window.location.search;
            url = url.replace(/(\/(p)\/\d+)|(&p=\d+)|(\/(id)\/\d+)|(&id=\d+)|(\/(group)\/\d+)|(&group=\d+)/, "");
            $subnav.find("a[href='" + url + "']").parent().addClass("current");

            /* 左边菜单显示收起 */
            $("#subnav").on("click", "h3", function(){
                var $this = $(this);
                $this.find(".icon").toggleClass("icon-fold");
                $this.next().slideToggle("fast").siblings(".side-sub-menu:visible").
                      prev("h3").find("i").addClass("icon-fold").end().end().hide();
            });

            $("#subnav h3 a").click(function(e){e.stopPropagation()});

            /* 头部管理员菜单 */
            $(".user-bar").mouseenter(function(){
                var userMenu = $(this).children(".user-menu ");
                userMenu.removeClass("hidden");
                clearTimeout(userMenu.data("timeout"));
            }).mouseleave(function(){
                var userMenu = $(this).children(".user-menu");
                userMenu.data("timeout") && clearTimeout(userMenu.data("timeout"));
                userMenu.data("timeout", setTimeout(function(){userMenu.addClass("hidden")}, 100));
            });

	        /* 表单获取焦点变色 */
	        $("form").on("focus", "input", function(){
		        $(this).addClass('focus');
	        }).on("blur","input",function(){
				        $(this).removeClass('focus');
			        });
		    $("form").on("focus", "textarea", function(){
			    $(this).closest('label').addClass('focus');
		    }).on("blur","textarea",function(){
			    $(this).closest('label').removeClass('focus');
		    });

            // 导航栏超出窗口高度后的模拟滚动条
            var sHeight = $(".sidebar").height();
            var subHeight  = $(".subnav").height();
            var diff = subHeight - sHeight; //250
            var sub = $(".subnav");
            if(diff > 0){
                $(window).mousewheel(function(event, delta){
                    if(delta>0){
                        if(parseInt(sub.css('marginTop'))>-10){
                            sub.css('marginTop','0px');
                        }else{
                            sub.css('marginTop','+='+10);
                        }
                    }else{
                        if(parseInt(sub.css('marginTop'))<'-'+(diff-10)){
                            sub.css('marginTop','-'+(diff-10));
                        }else{
                            sub.css('marginTop','-='+10);
                        }
                    }
                });
            }
        }();
    </script>
    
    <script type="text/javascript">
        $(function(){
            $('.sidebar').remove();
            $('[name=giftitem]').click(function(){
                var url= $(this).attr("alt");
                location.href = url;
            });

            $(".giftitem").hover(function() {$(this).addClass("gray")}, function() {$(this).removeClass("gray")});
        });
    </script>

</body>
</html>