<?php

/**
 * Traite les requètes ajax pour le menu de transfert
 */
if(strpos($_SERVER['PHP_SELF'], "transferOptions.php")) {
	include ('../../../inc/includes.php');
	header("Content-Type: text/html; charset=UTF-8");
	Html::header_nocache();
}

Session::checkLoginUser();

if(isset($_POST['request'])) {
	switch ($_POST['request']) {
		case 'itilcategories' :
			if(!isset($_POST['entities_id']) || !isset($_POST['type'])) return;
			PluginTickettransferTickettab::categoriesDropdown($_POST);
			return;
		case 'transfer_options' :
			if(!isset($_POST['itilcategories_id'])) return;
			PluginTickettransferTickettab::showTransferOptions($_POST);
			return;
	}
}

?>





