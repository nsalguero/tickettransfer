<?php

/**
 * Fonction de définition de la version du plugin
 * @return array description du plugin
 */
function plugin_version_tickettransfer()
{
	return array('name'           => "Ticket transfer",
			'version'        => '0.0.1',
			'author'         => 'Etiennef',
			'license'        => 'GPLv2+',
			'homepage'       => 'https://github.com/Etiennef/tickettransfer',
			'minGlpiVersion' => '0.84');
}

/**
 * Fonction de vérification des prérequis
 * @return boolean le plugin peut s'exécuter sur ce GLPI
 */
function plugin_tickettransfer_check_prerequisites()
{
	if (version_compare(GLPI_VERSION,'0.84.8','lt') || version_compare(GLPI_VERSION,'0.85','ge')) {
		echo __("Plugin has been tested only for GLPI 0.84.8", 'tickettransfer');
		return false;
	}
	// ajouter éventuellement la présence d'autres plugins
	
	return true;
}


/**
 * Fonction de vérification de la configuration initiale
 * @param type $verbose
 * @return boolean la config est faite
 */
function plugin_tickettransfer_check_config($verbose=false)
{
	if (true) { //TODO faire un vrai test
		return true;
	}
	if ($verbose) {
		echo 'Installed / not configured';
	}
	return false;
}


/**
 * Fonction d'initialisation du plugin.
 * @global array $PLUGIN_HOOKS
 */
function plugin_init_tickettransfer()
{
	global $PLUGIN_HOOKS;

	$PLUGIN_HOOKS['csrf_compliant']['tickettransfer'] = true;
	
	Plugin::registerClass('PluginTickettransferConfig', array(
		'addtabon' => array('User', 'Preference','Config', 'Entity', 'Profile')));
	$PLUGIN_HOOKS['config_page']['tickettransfer'] = 'front/config.form.php';
	
	// Onglet transfert pour les tickets
	Plugin::registerClass('PluginTickettransferTickettab', array('addtabon' => array('Ticket')));
	
	// Notifications
	$PLUGIN_HOOKS['item_get_events']['tickettransfer'] = 
			array('NotificationTargetTicket' => array('PluginTickettransferNotification', 'addEvents'));
	$PLUGIN_HOOKS['item_get_datas']['tickettransfer'] = 
			array('NotificationTargetTicket' => array('PluginTickettransferNotification', 'getDatas'));
	
}









