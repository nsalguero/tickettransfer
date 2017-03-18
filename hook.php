<?php

/**
 * Fonction d'installation du plugin
 * @return boolean
 */
function plugin_tickettransfer_install() {
   include 'inc/config.class.php';
   PluginTickettransferConfig::install();
   
   return true;
}

/**
 * Fonction de désinstallation du plugin
 * @return boolean
 */
function plugin_tickettransfer_uninstall() {
   include 'inc/config.class.php';
   PluginTickettransferConfig::uninstall();
   
   return true;
}
