<?php


include ("../../../inc/includes.php");
Session::checkLoginUser();
Session::checkRight('profile', "w");

if (isset($_POST['update_user_profile'])) {
	$prof = new PluginTickettransferProfileconfig();
	$prof->update($_POST);
	$prof->onProfileChange();
	Html::back();
}

