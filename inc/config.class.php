<?php

/**
 * Objet décrivant la configuration du plugin
 * 
 * @author Etiennef
 */
class PluginTickettransferConfig extends PluginConfigmanagerConfig {
	static function getPluginName() {
		return __("Ticket transfer", 'tickettransfer');
	}
	
	static function makeConfigParams() {
		$tmp = array(
			'allow_transfer' => array(
				'type' => 'dropdown',
				'text' => __('Allow requalification', 'tickettransfer'),
				'values' => array(
					'1' => Dropdown::getYesNo('1'),
					'0' => Dropdown::getYesNo('0')
				),
				'types' => array(self::TYPE_PROFILE, self::TYPE_GLOBAL),
				'dbtype' => 'varchar(25)',
				'default' => '0'
			),
			'allow_group' => array(
				'type' => 'dropdown',
				'text' => __('Allow escalation', 'tickettransfer'),
				'values' => array(
					'1' => Dropdown::getYesNo('1'),
					'0' => Dropdown::getYesNo('0')
				),
				'types' => array(self::TYPE_PROFILE, self::TYPE_GLOBAL),
				'dbtype' => 'varchar(25)',
				'default' => '0'
			),
			'notif_transfer' => array(
				'type' => 'dropdown',
				'text' => __('Notification on requalification', 'tickettransfer'),
				'values' => array(
					'always' => __('Always', 'tickettransfer'),
					'ongroupchange' => __('Only when assign group changes', 'tickettransfer'),
					'never' => __('Never', 'tickettransfer') 
				),
				'types' => array(self::TYPE_GLOBAL),
				'dbtype' => 'varchar(25)',
				'default' => 'never'
			),
			'notif_group' => array(
				'type' => 'dropdown',
				'text' => __('Notification on requalification', 'tickettransfer'),
				'values' => array(
					'always' => __('Always', 'tickettransfer'),
					'onassinglost' => __('Only when the user doing the transfer is not in destination group', 'tickettransfer'),
					'never' => __('Never', 'tickettransfer') 
				),
				'types' => array(self::TYPE_GLOBAL),
				'dbtype' => 'varchar(25)',
				'default' => 'never'
			),
			'allowed_entities' => array(
				'type' => 'dropdown',
				'text' => __('Allowed entities', 'tickettransfer'),
				'values' => Dropdown::getDropdownArrayNames(Entity::getTable(), $_SESSION['glpiactiveentities']),
				'types' => array(self::TYPE_PROFILE, self::TYPE_GLOBAL),
				'dbtype' => 'varchar(2500)',
				'default' => '[]',
			 	'options' => array(
			 		'multiple' => true,
			 		'size' => 5,
			 		'mark_unmark_all' => true
			 	)
			),
			'force_justification' => array(
				'type' => 'dropdown',
				'text' => __('Force transfer justification', 'tickettransfer'),
				'values' => array(
					'1' => Dropdown::getYesNo('1'),
					'0' => Dropdown::getYesNo('0')
				),
				'types' => array(self::TYPE_PROFILE, self::TYPE_GLOBAL),
				'dbtype' => 'varchar(25)',
				'default' => '1'
			),
			'justification_prefix' => array(
				'type' => 'text input',
				'text' => __('Transfer justification prefix', 'tickettransfer'),
				'types' => array(self::TYPE_PROFILE, self::TYPE_GLOBAL),
				'dbtype' => 'varchar(250)',
				'default' => __('Transfer justification', 'tickettransfer')
			),
			'default_observer_option' => array(
				'type' => 'dropdown',
				'text' => __('Default \'stay observer\' value', 'tickettransfer', 'tickettransfer'),
				'values' => array(
					'yes' => __('Yes'),
					'no' => __('No'),
					'nochange' => __('Keep current status', 'tickettransfer'),
					'yesifnotingroup' => __('Yes, but only if I\'m not already in an observer group', 'tickettransfer') 
				),
				'types' => array(self::TYPE_USER, self::TYPE_GLOBAL),
				'dbtype' => 'varchar(25)',
				'default' => 'nochange'
			),
			'default_transfer_mode' => array(
				'type' => 'dropdown',
				'text' => __('Default \'transfer mode\' for group on requalification', 'tickettransfer', 'tickettransfer'),
				'values' => array(
					'keep' => __('Keep current group', 'tickettransfer'),
					'auto' => __('Automatic transfer', 'tickettransfer') 
				),
				'types' => array(self::TYPE_USER, self::TYPE_PROFILE, self::TYPE_GLOBAL),
				'dbtype' => 'varchar(25)',
				'default' => 'auto'
			)
		);
		
		asort($tmp['allowed_entities']['values']);
		return $tmp;
	}
}
?>


























