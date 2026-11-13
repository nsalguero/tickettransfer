<?php

/**
 * Objet décrivant la configuration du plugin
 * 
 * @author Etiennef
 */
class PluginTickettransferConfig extends PluginConfigmanagerConfig {
   static function makeConfigParams() {
      $tmp = array(
         '_title' => array(
            'type' => 'readonly text',
            'types' => array(self::TYPE_USER, self::TYPE_PROFILE, self::TYPE_GLOBAL),
            'text' => self::makeHeaderLine(__('Configuration for TicketTransfer', 'tickettransfer'))
         ),
         'allow_requalification' => array(
            'type' => 'dropdown',
            'types' => array(self::TYPE_PROFILE, self::TYPE_GLOBAL),
            'maxlength' => 25,
            'text' => __('Allow requalification', 'tickettransfer'),
            'values' => array(
               '1' => Dropdown::getYesNo('1'),
               '0' => Dropdown::getYesNo('0')
            ),
            'default' => '0'
         ),
         'allow_escalation' => array(
            'type' => 'dropdown',
            'types' => array(self::TYPE_PROFILE, self::TYPE_GLOBAL),
            'maxlength' => 25,
            'text' => __('Allow escalation', 'tickettransfer'),
            'values' => array(
               '1' => Dropdown::getYesNo('1'),
               '0' => Dropdown::getYesNo('0')
            ),
            'default' => '0'
         ),
         'notif_requalification' => array(
            'type' => 'dropdown',
            'types' => array(self::TYPE_GLOBAL),
            'maxlength' => 25,
            'text' => __('Notification on requalification', 'tickettransfer'),
            'values' => array(
               'always' => __('Always', 'tickettransfer'),
               'ongroupchange' => __('Only when assign group changes', 'tickettransfer'),
               'never' => __('Never', 'tickettransfer') 
            ),
            'default' => 'never'
         ),
         'notif_escalation' => array(
            'type' => 'dropdown',
            'types' => array(self::TYPE_GLOBAL),
            'maxlength' => 25,
            'text' => __('Notification on escalation', 'tickettransfer'),
            'values' => array(
               'always' => __('Always', 'tickettransfer'),
               'onassinglost' => __('Only when the user doing the transfer is not in destination group', 'tickettransfer'),
               'never' => __('Never', 'tickettransfer') 
            ),
            'default' => 'never'
         ),
         'allowed_entities' => array(
            'type' => 'dropdown',
            'types' => array(self::TYPE_PROFILE, self::TYPE_GLOBAL),
            'maxlength' => 60000,
            'text' => __('Allowed entities', 'tickettransfer'),
            'values' => Dropdown::getDropdownArrayNames(Entity::getTable(), $_SESSION['glpiactiveentities']),
            'default' => '[]',
             'multiple' => true,
             'size' => 5,
             'mark_unmark_all' => true
         ),
         'force_justification' => array(
            'type' => 'dropdown',
            'types' => array(self::TYPE_PROFILE, self::TYPE_GLOBAL),
            'maxlength' => 25,
            'text' => __('Force transfer justification', 'tickettransfer'),
            'values' => array(
               '1' => Dropdown::getYesNo('1'),
               '0' => Dropdown::getYesNo('0')
            ),
            'default' => '1'
         ),
         'justification_prefix' => array(
            'type' => 'text input',
            'types' => array(self::TYPE_PROFILE, self::TYPE_GLOBAL),
            'maxlength' => 250,
            'text' => __('Transfer justification prefix', 'tickettransfer'),
            'default' => __('Transfer justification', 'tickettransfer')
         ),
         'default_observer_option' => array(
            'type' => 'dropdown',
            'types' => array(self::TYPE_USER, self::TYPE_GLOBAL),
            'maxlength' => 25,
            'text' => __('Default \'stay observer\' value', 'tickettransfer'),
            'values' => array(
               'yes' => __('Yes'),
               'no' => __('No'),
               'nochange' => __('Keep current status', 'tickettransfer'),
               'yesifnotingroup' => __('Yes, but only if I\'m not already in an observer group', 'tickettransfer') 
            ),
            'default' => 'nochange'
         ),
         'default_transfer_mode' => array(
            'type' => 'dropdown',
            'types' => array(self::TYPE_USER, self::TYPE_PROFILE, self::TYPE_GLOBAL),
            'maxlength' => 25,
            'text' => __('Default \'transfer mode\' for group on requalification', 'tickettransfer'),
            'values' => array(
               'keep' => __('Keep current group', 'tickettransfer'),
               'auto' => __('Automatic transfer', 'tickettransfer') 
            ),
            'default' => 'auto'
         )
      );
      
      asort($tmp['allowed_entities']['values']);
      return $tmp;
   }

   static function getConfigValues($values=array()) {
      $config = parent::getConfigValues($values);

      /*
       * for when config will be glpiactiveentities + add - remove
       $addEntities = array(0, 3);
       $removeEntities = array(5);
       $allowed_entities = array_diff(array_unique(array_merge($addEntities, $_SESSION['glpiactiveentities'])), $removeEntities);*/

      $allowed_entities = array();
      foreach ($config['allowed_entities'] as $value) {
         $allowed_entities[] = intval($value);
      }
      $config['allowed_entities'] = $allowed_entities;

      return $config;
   }

    public static function getTypeName($nb = 0)
    {
        return __s('Ticket transfer', 'tickettransfer');
    }

    public static function getIcon()
    {
        return "ti ti-transfer-vertical";
    }

   /**
    * Determine the title of the config tab.
    * Defaults to the plugion name, but can be overridden by the user.
    * @param string $type configuration type
    * @return String title of the tab
    */
   protected static function getTabNameForConfigType($type) {
         return self::createTabEntry(self::getTypeName(), 0, $type, self::getIcon());
   }
}
