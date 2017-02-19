<?php

/**
 * Fonction de définition de la version du plugin
 * @return array description du plugin
 */
function plugin_version_tickettransfer() {
   return array(
      'name' => "Ticket transfer",
      'version' => '0.84+1.1.0',
      'author' => 'Etiennef',
      'license' => 'GPLv2+',
      'homepage' => 'https://github.com/Etiennef/tickettransfer',
      'minGlpiVersion' => '0.84.8'
   );
}

/**
 * Fonction de vérification des prérequis
 * @return boolean le plugin peut s'exécuter sur ce GLPI
 */
function plugin_tickettransfer_check_prerequisites() {
   if(version_compare(GLPI_VERSION, '0.84.8', 'lt') || version_compare(GLPI_VERSION, '0.85', 'ge')) {
      echo __("Plugin has been tested only for GLPI 0.84.8", 'tickettransfer');
      return false;
   }

   //Vérifie la présence de ConfigManager
   if(!(new Plugin())->isActivated('configmanager')) {
      echo __("Plugin requires ConfigManager 1.x.x", 'tickettransfer');
      return false;
   }
   $configmanager_version = Plugin::getInfo('configmanager', 'version');
   if(version_compare($configmanager_version, '1.0.0', 'lt') || version_compare($configmanager_version, '2.0.0', 'ge')) {
      echo __("Plugin requires ConfigManager 1.x.x", 'tickettransfer');
      return false;
   }

   return true;
}

/**
 * Fonction de vérification de la configuration initiale
 * @param type $verbose
 * @return boolean la config est faite
 */
function plugin_tickettransfer_check_config($verbose = false) {
   return true;
}

/**
 * Fonction d'initialisation du plugin.
 * @global array $PLUGIN_HOOKS
 */
function plugin_init_tickettransfer() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['tickettransfer'] = true;

   Plugin::registerClass('PluginTickettransferConfig', array('addtabon' => array(
         'User',
         'Preference',
         'Config',
         'Profile'
      )));

   if((new Plugin())->isActivated('tickettransfer')) {
      $PLUGIN_HOOKS['config_page']['tickettransfer'] = "../../front/config.form.php?forcetab=" . urlencode('PluginTickettransferConfig$1');
   }

   // Onglet transfert pour les tickets
   Plugin::registerClass('PluginTickettransferTickettab', array(
      'addtabon' => array('Ticket')
   ));

   // Réécriture des liens escalade
   if((new Plugin())->isActivated('escalade')) {
       $PLUGIN_HOOKS['add_javascript']['tickettransfer'] = 'scripts/escalade.js';
   }
   // Notifications
   $PLUGIN_HOOKS['item_get_events']['tickettransfer'] = array(
      'NotificationTargetTicket' => array('PluginTickettransferNotification', 'addEvents')
   );
   $PLUGIN_HOOKS['item_get_datas']['tickettransfer'] = array(
      'NotificationTargetTicket' => array('PluginTickettransferNotification', 'getDatas')
   );
}
