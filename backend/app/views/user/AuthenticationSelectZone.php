<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<!-- BEGIN HEAD -->
<head>
<meta charset="utf-8"/>
<title>用戶登入 | <?php echo Config::get('app.appname'); ?></title>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<meta content="" name="description"/>
<meta content="" name="author"/>
<!-- BEGIN GLOBAL MANDATORY STYLES -->
<link href="//fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&subset=all" rel="stylesheet" type="text/css"/>
<link href="<?php echo Config::get('app.assetlocation'); ?>/global/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css"/>
<link href="<?php echo Config::get('app.assetlocation'); ?>/global/plugins/simple-line-icons/simple-line-icons.min.css" rel="stylesheet" type="text/css"/>
<link href="<?php echo Config::get('app.assetlocation'); ?>/global/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
<link href="<?php echo Config::get('app.assetlocation'); ?>/global/plugins/uniform/css/uniform.default.css" rel="stylesheet" type="text/css"/>
<!-- END GLOBAL MANDATORY STYLES -->
<!-- BEGIN PAGE LEVEL STYLES -->
<link href="<?php echo Config::get('app.assetlocation'); ?>/admin/pages/css/login.css" rel="stylesheet" type="text/css"/>
<!-- END PAGE LEVEL SCRIPTS -->
<!-- BEGIN THEME STYLES -->
<link href="<?php echo Config::get('app.assetlocation'); ?>/global/css/components.css" id="style_components" rel="stylesheet" type="text/css"/>
<link href="<?php echo Config::get('app.assetlocation'); ?>/global/css/plugins.css" rel="stylesheet" type="text/css"/>
<link href="<?php echo Config::get('app.assetlocation'); ?>/admin/layout/css/layout.css" rel="stylesheet" type="text/css"/>
<link href="<?php echo Config::get('app.assetlocation'); ?>/admin/layout/css/themes/default.css" rel="stylesheet" type="text/css" id="style_color"/>
<link href="<?php echo Config::get('app.assetlocation'); ?>/admin/layout/css/custom.css" rel="stylesheet" type="text/css"/>
<!-- END THEME STYLES -->
<link rel="shortcut icon" href="favicon.ico"/>
</head>
<!-- END HEAD -->
<!-- BEGIN BODY -->
<body class="login">
<!-- BEGIN SIDEBAR TOGGLER BUTTON -->
<div class="menu-toggler sidebar-toggler">
</div>
<!-- END SIDEBAR TOGGLER BUTTON -->
<!-- BEGIN LOGO -->
<div class="logo">
	<a href="index.html">
	<!-- <img src="<?php echo Config::get('app.assetlocation'); ?>/admin/layout/img/logo-big.png" alt=""/> -->
	</a>
</div>
<!-- END LOGO -->
<!-- BEGIN LOGIN -->
<div class="content">
	<!-- BEGIN LOGIN FORM -->

		<h3 class="form-title">選擇路線</h3>
        <div class="row">        
            <?php foreach($zoneD as $z):?>
            <div class="col-md-4">
    		  <a href="/setZone?id=<?php echo $z->zoneId;?>" class="btn btn-lg default"><?php echo $z->zoneDetail->zoneName;?> <i class="fa fa-location-arrow"></i></a>
    		</div>
    		<?php endforeach;?>
		</div>

	<!-- END LOGIN FORM -->
	
</div>
<div class="copyright">
	 <?php echo date('Y'); ?> © <?php echo Config::get('app.companyname'); ?>
</div>
<!-- END LOGIN -->
<!-- BEGIN JAVASCRIPTS(Load javascripts at bottom, this will reduce page load time) -->
<!-- BEGIN CORE PLUGINS -->
<!--[if lt IE 9]>
<script src="<?php echo Config::get('app.assetlocation'); ?>/global/plugins/respond.min.js"></script>
<script src="<?php echo Config::get('app.assetlocation'); ?>/global/plugins/excanvas.min.js"></script> 
<![endif]-->
<script src="<?php echo Config::get('app.assetlocation'); ?>/global/plugins/jquery.min.js" type="text/javascript"></script>
<script src="<?php echo Config::get('app.assetlocation'); ?>/global/plugins/jquery-migrate.min.js" type="text/javascript"></script>
<script src="<?php echo Config::get('app.assetlocation'); ?>/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="<?php echo Config::get('app.assetlocation'); ?>/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
<script src="<?php echo Config::get('app.assetlocation'); ?>/global/plugins/jquery.cokie.min.js" type="text/javascript"></script>
<script src="<?php echo Config::get('app.assetlocation'); ?>/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
<!-- END CORE PLUGINS -->
<!-- BEGIN PAGE LEVEL PLUGINS -->
<script src="<?php echo Config::get('app.assetlocation'); ?>/global/plugins/jquery-validation/js/jquery.validate.min.js" type="text/javascript"></script>
<!-- END PAGE LEVEL PLUGINS -->
<!-- BEGIN PAGE LEVEL SCRIPTS -->
<script src="<?php echo Config::get('app.assetlocation'); ?>/global/scripts/metronic.js" type="text/javascript"></script>
<script src="<?php echo Config::get('app.assetlocation'); ?>/admin/layout/scripts/layout.js" type="text/javascript"></script>
<script src="<?php echo Config::get('app.assetlocation'); ?>/admin/pages/scripts/login.js" type="text/javascript"></script>
<!-- END PAGE LEVEL SCRIPTS -->
<script>
jQuery(document).ready(function() {     
	Metronic.init(); // init metronic core components
	Layout.init(); // init current layout
	Login.init();
});
</script>
<!-- END JAVASCRIPTS -->
</body>
<!-- END BODY -->
</html>