<?php

/**
 * Renvoie un dropdown avec les catégories possibles en tenant compte de :
 * - la catégorie est-elle visible de l'entité
 * - la catégorie peut-elle contenir le type demandé
 * - la catégorie est-elle visible pour l'utilisateur
 */
 
if (strpos ( $_SERVER ['PHP_SELF'], "dropdownTypes.php" )) {
	include ('../../../inc/includes.php');
	header ( "Content-Type: text/html; charset=UTF-8" );
	Html::header_nocache ();
}
if (! defined ( 'GLPI_ROOT' )) {
	die ( "Can not acces directly to this file" );
}

$currententity = isset($_POST['entity']) ? $_POST['entity'] : $_SESSION['glpiactive_entity'];
$currenttype = isset($_POST['type']) ? $_POST['type'] : -1;
$currentcategory = isset($_POST['currentcategory']) ? $_POST['currentcategory'] : -1;
$allow_empty_category = isset($_POST['allow_empty_category']) ? $_POST['allow_empty_category'] : true;

PluginTickettransferTickettab::typesDropdown($currententity, $currenttype, $currentcategory, $allow_empty_category);

?>





