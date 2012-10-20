<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));
//Loader::packageElement($existing_tmpl_name, $pkgHandle, $default_args);
try{
	$task = $this->controller->getTask();
	$html = Loader::helper('html');
	$form = Loader::helper('form');
	$date_time = Loader::helper('form/date_time');
	$core_ui = Loader::helper('concrete/interface');
	$config_tmpl_name = 'config_form';
	$form_tmpl_name = 'event_form';
	$default_args = array(
						'form'=>$form, 
						'html'=>$html, 
						'date_time'=>$date_time, 
						'core_ui'=>$core_ui, 
						'admin_url_base'=>$admin_url_base,
						'pkg'=> $pkg
						);
	//print_r($this->controller);
}
catch(Exception $error){
	$this->controller->handleError();
}
$actions = array('view'=>'General', 'config'=>'Configure');
if($task == 'view'){
	Loader::packageElement($config_tmpl_name, $pkgHandle, $default_args);
}
if($task == 'save_config'){
	?>
	<a href="<?php echo View::url($admin_url_base);?>"><?php echo t('&larr; Back to settings.');?></a>
	<?php 
}

