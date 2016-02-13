<?php
include ("../../../inc/includes.php");
require_once('../inc/config.class.php');

if(isset($_POST['update'])) {
	$config = new PluginTickettransferConfig();
	$config->check($_POST['id'],'w');
	$config->update($_POST);
	Html::back();
} else {
	Html::redirect($CFG_GLPI["root_doc"]."/front/config.form.php?forcetab=".urlencode('PluginTickettransferConfig$0'));
}
