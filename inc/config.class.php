<?php

/**
 * Objet décrivant la configuration du plugin
 * 
 * @author Etiennef
 */
class PluginTickettransferConfig extends PluginConfigmanagerConfig {
	function getName($options = array()) {
		return __("Ticket transfer", 'tickettransfer');
	}
	
	static function makeConfigParams() {
		$tmp = array(
			'allow_transfer' => array(
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
				'text' => __('Transfer justification prefix', 'tickettransfer'),
				'values' => 'text input',
				'types' => array(self::TYPE_PROFILE, self::TYPE_GLOBAL),
				'dbtype' => 'varchar(250)',
				'default' => __('Transfer justification', 'tickettransfer')
			),
			'default_observer_option' => array(
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


























