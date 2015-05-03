<?php

/**
 * Fonction d'installation du plugin
 * @return boolean
 */
function plugin_tickettransfer_install() {
	global $DB;
	
	if (! TableExists ( "glpi_plugin_tickettransfer_profiles" )) {
		// table pour stoquer quels profils ont accès aux fonctions du plugin
		// id = id du profil
		// transfer_togroup = droit de transférer des tickets vers un autre groupe dans une même entité
		// transfer_toentity = droit de transférer des tickets vers une autre entité
		// transfer_toallentities = droit de transférer des tickets vers toutes les entités, pas seulement celles visible avec ce profil
		
		$query = "CREATE TABLE `glpi_plugin_tickettransfer_profileconfigs` (
                    `id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_profiles (id)',
                    `transfer_toentity` tinyint(1) collate utf8_unicode_ci default 0,
                    PRIMARY KEY  (`id`)
                  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		
		$DB->query ( $query ) or die ( $DB->error () );
	}
	
	
	
	return true;
}


/**
 * Fonction de désinstallation du plugin
 * @return boolean
 */
function plugin_tickettransfer_uninstall()
{
	global $DB;

	$tables = array("glpi_plugin_tickettransfer_profileconfigs");

	foreach($tables as $table) {
		$DB->query("DROP TABLE IF EXISTS `$table`;");
	}
	
	return true;
}