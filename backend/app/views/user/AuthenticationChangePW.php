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
	<a href="#" style="font-size:45px;color:white;">
	<!-- <img src="<?php echo Config::get('app.assetlocation'); ?>/admin/layout/img/logo-big.png" alt=""/> -->
	炳記行智能系統
	</a>
</div>
<!-- END LOGO -->
<!-- BEGIN LOGIN -->
<div class="content">
	<!-- BEGIN LOGIN FORM -->
	<?php //echo Form::open(array('url' => secure_url('/credential/auth'))); ?>
	<?php echo Form::open(array('url'=>('/changePassword'))); ?>
		<h3 class="form-title">更改密碼</h3>
		<?php if(Session::has('flash_error')): ?>
		<div class="alert alert-danger"><button class="close" data-close="alert"></button><span><?php echo Session::get('flash_error'); ?></span></div>
		<?php endif; ?>
		<div class="form-group">
			<?php
			     echo Form::label('oldpassword', '舊密碼', array('class'=>'control-label visible-ie8 visible-ie9'));
			     echo Form::password('oldpassword', array('class'=>'form-control form-control-solid placeholder-no-fix', 'autocomplete'=>'off', 'placeholder'=>'舊密碼'));
			?>
		</div>
		<div class="form-group">
			<?php
			     echo Form::label('newpassword', '新密碼', array('class'=>'control-label visible-ie8 visible-ie9'));
			     echo Form::password('newpassword', array('class'=>'form-control form-control-solid placeholder-no-fix', 'autocomplete'=>'off', 'placeholder'=>'新密碼'));
			?>
		</div>
		
		<div class="form-group">
			<?php
			     echo Form::label('newpassword2', '重覆密碼', array('class'=>'control-label visible-ie8 visible-ie9'));
			     echo Form::password('newpassword2', array('class'=>'form-control form-control-solid placeholder-no-fix', 'autocomplete'=>'off', 'placeholder'=>'重覆密碼'));
			?>
		</div>

		<div class="form-actions" style="text-align:right;">
			<button type="submit" class="btn btn-success uppercase">提交</button>
		</div>

	<?php echo Form::close(); ?>
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