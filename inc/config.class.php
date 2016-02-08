<?php

/**
 * Objet générique de gestion de la configuration
 * Prend en compte l'héritage de configuration
 * 
 * 
 * Usage : Définir makeConfigParams, faire le registerclass pour tous les objets intéressants, et le pluginhook
 * 
 * @author Etiennef
 */
class PluginTickettransferConfig extends CommonDBTM {
	const TYPE_GLOBAL = 'global';
	const TYPE_ENTITY = 'entity';
	const TYPE_PROFILE = 'profile';
	const TYPE_USER = 'user';
	
	//Le fait que la variable commence par un nombre abscon est important : il faut que lorsqu'elle est comparée à un nombre avec ==, elle ne renvoit jamais vrai (ne pas mettre de nombre abscon revenant à mettre 0)
	const INHERIT_VALUE = '965482.5125475__inherit__';
	
	
	
	private static $configparams_instance = NULL;
	
	/**
	 * Réccupére la conficuration courante Fonctionne avec un singleton pour éviter les appels à la bdd inutiles
	 */
	private static function getConfigParams() {
		if(! isset(self::$configparams_instance)) {
			self::makeConfigParams();
		}
		return self::$configparams_instance;
	}
	
	private static function makeConfigParams() {
		self::$configparams_instance = array(
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
		
		asort(self::$configparams_instance['allowed_entities']['values']);
	}
	

	/**
	 * Création des tables liées à cet objet
	 * Utilisée lors de l'installation du plugin
	 */
	public static function install() {
		global $DB;
		$table = self::getTable();
		$request = '';
		
		$query = "CREATE TABLE `$table` (
					`" . self::getIndexName() . "` int(11) NOT NULL AUTO_INCREMENT,
					`config__type` varchar(50) collate utf8_unicode_ci NOT NULL,
					`config__type_id` int(11) collate utf8_unicode_ci NOT NULL,";
		
		foreach(self::getConfigParams() as $param => $desc) {
			$query .= "`$param` " . $desc['dbtype'] . " collate utf8_unicode_ci,";
		}
		
		$query .= "PRIMARY KEY  (`" . self::getIndexName() . "`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		
		if(! TableExists($table)) {
			$DB->queryOrDie($query, $DB->error());
		}
		
		//TODO création d'une ligne par défaut?
	}

	/**
	 * Suppression des tables liées à cet objet
	 * Utilisé lors de la désinstallation du plugin
	 * 
	 * @return boolean
	 */
	public static function uninstall() {
		global $DB;
		$table = self::getTable();
		
		if(TableExists($table)) {
			$query = "DROP TABLE `$table`";
			$DB->queryOrDie($query, $DB->error());
		}
		return true;
	}

	private static function hasFieldsForType($type) {
		foreach(self::getConfigParams() as $param => $desc) {
			if(in_array($type, $desc['types'])) return true;
		}
		return false;
	}

	private static function getTypeForGLPIObject($glpiobjecttype) {
		switch($glpiobjecttype) {
			case 'Config' :
				return self::TYPE_GLOBAL;
			case 'Entity' :
				return self::TYPE_ENTITY;
			case 'Profile' :
				return self::TYPE_PROFILE;
			case 'User' :
				return self::TYPE_USER;
			case 'Preference' :
				return self::TYPE_USER;
			default :
				return '';
		}
	}

	private function createEmpty($type, $type_id = 0) {
		if($type == self::TYPE_GLOBAL) $type_id = 0;
		
		$input = array();
		foreach(self::getConfigParams() as $param => $desc) {
			$pos = array_search($type, $desc['types']);
			if($pos !== false && ! isset($desc['types'][$pos + 1])) {
				$input[$param] = $desc['default'];
			} else {
				$input[$param] = self::INHERIT_VALUE;
			}
		}
		
		$input['config__type'] = $type;
		$input['config__type_id'] = $type_id;
		$id = $this->add($input);
		$this->getFromDB($id);
	}

	static function canView() {
		return true;
	}

	static function canCreate() {
		return true;
	}

	function canViewItem() {
		if(! self::hasFieldsForType($this->fields['config__type'])) return false;
		
		switch($this->fields['config__type']) {
			case self::TYPE_GLOBAL :
				return Session::haveRight('config', 'r');
			case self::TYPE_ENTITY :
				return (new Entity())->can($this->fields['config__type_id'], 'r');
			case self::TYPE_PROFILE :
				return Session::haveRight('profile', 'r');
			case self::TYPE_USER :
				return Session::getLoginUserID() == $this->fields['config__type_id'] ||
						 (new User())->can($this->fields['config__type_id'], 'r');
			default :
				return false;
		}
	}

	function canCreateItem() {
		if(! self::hasFieldsForType($this->fields['config__type'])) return false;
		
		switch($this->fields['config__type']) {
			case self::TYPE_GLOBAL :
				return Session::haveRight('config', 'w');
			case self::TYPE_ENTITY :
				return (new Entity())->can($this->fields['config__type_id'], 'w');
			case self::TYPE_PROFILE :
				return Session::haveRight('profile', 'w');
			case self::TYPE_USER :
				return Session::getLoginUserID() == $this->fields['config__type_id'] ||
						 (new User())->can($this->fields['config__type_id'], 'w');
			default :
				return false;
		}
	}
	

	function prepareInputForUpdate($input) {
		foreach(self::getConfigParams() as $param => $desc) {
			if(isset($input[$param]) && self::isMultipleParam($param)) {
				if(in_array(self::INHERIT_VALUE, $input[$param])) {
					if(count($input[$param]) > 1) {
						//TODO personnaliser pour l'option
						Session::addMessageAfterRedirect(__('Warning, you defined the inherit option together with other option. Only inherit will but taken into account', 'tickettransfer'), false, ERROR);
					}
					$input[$param] = self::INHERIT_VALUE;
				} else {
					$input[$param] = exportArrayToDB($input[$param]);
				}
			}
		}
		
		return $input;
	}

	
	private static $configValues_instance = NULL;
	/**
	 * Réccupére la conficuration courante Fonctionne avec un singleton pour éviter les appels à la bdd inutiles
	 */
	public static function getConfigValues() {
		if(! isset(self::$configValues_instance)) {
			self::$configValues_instance = self::readFromDB();
		}
		return self::$configValues_instance;
	}
	
	/**
	 * Calcul de la configuration applicable dans la situation actuelle, en tenant compte des différents héritages.
	 * @return array tableau de valeurs de configuration à appliquer
	 */
	private static function readFromDB() {
		$configObject = new self();
		
		// lit dans la DB les configs susceptibles de s'appliquer
		$configTable = array();
		
		if($configObject->getFromDBByQuery("WHERE `config__type`='" . self::TYPE_GLOBAL . "'"))
			$configTable[self::TYPE_GLOBAL] = $configObject->fields;
		
		if($configObject->getFromDBByQuery("`config__type`='" . self::TYPE_ENTITY . "' AND `config__type_id`=" . $_SESSION['glpiactive_entity']))
				$configTable[self::TYPE_ENTITY] = $configObject->fields;
		
		if($configObject->getFromDBByQuery("WHERE `config__type`='" . self::TYPE_PROFILE . "' AND `config__type_id`=" .
						 $_SESSION['glpiactiveprofile']['id']))
			$configTable[self::TYPE_PROFILE] = $configObject->fields;
		
		if($configObject->getFromDBByQuery("WHERE `config__type`='" . self::TYPE_USER . "' AND `config__type_id`=" . Session::getLoginUserID()))
			$configTable[self::TYPE_USER] = $configObject->fields;
		
		// Pour chaque paramètre, cherche la config qui s'applique en partant de celle qui écrase les autres
		$config = array();
		foreach(self::getConfigParams() as $param => $desc) {
			for($i = 0, $current = self::INHERIT_VALUE ; $current == self::INHERIT_VALUE ; $i ++) {
				if(isset($configTable[$desc['types'][$i]])) {
					$current = $configTable[$desc['types'][$i]][$param];
				}
			}
			
			if(self::isMultipleParam($param)) {
				$config[$param] = importArrayFromDB($current);
			} else {
				$config[$param] = $current;
			}
			
		}
		
		return $config;
	}

	function getName($options = array()) {
		return __("Ticket transfer", 'tickettransfer');
	}

	function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
		$type = self::getTypeForGLPIObject($item->getType());
		if($type != '' && self::hasFieldsForType($type)) {
			return self::getName();
		} else {
			return '';
		}
	}

	static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
		$type = self::getTypeForGLPIObject($item->getType());
		
		switch($item->getType()) {
			case 'Config' :
				$type_id = 0;
				break;
			case 'Entity' :
				$type_id = $item->getId();
				break;
			case 'Profile' :
				$type_id = $item->getId();
				break;
			case 'User' :
				$type_id = $item->getId();
				break;
			case 'Preference' :
				$type_id = Session::getLoginUserID();
				break;
			default :
				return false;
		}
		
		$config = new self();
		if(! $config->getFromDBByQuery("WHERE `config__type`='$type' AND `config__type_id`='$type_id'")) {
			$config->createEmpty($type, $type_id);
		}
		return $config->showForm();
	}

	/**
	 * Fonction qui affiche le formulaire de configuration du plugin
	 */
	function showForm() {
		if(! $this->can($this->getID(), 'r')) {
			return false;
		}
		$can_write = $this->can($this->getID(), 'w');
		
		if($can_write) {
			echo "<form action='" . self::getFormURL() . "' method='post'>";
		}
		
		echo "<table class='tab_cadre_fixe'>";
		
		//TODO personnaliser selon le niveau de réglage
		echo "<tr><th colspan='2' class='center b'>" . __('Ticket transfer plugin settings', 'tickettransfer') .
				 "</th></tr>";
		
		foreach(self::getConfigParams() as $param => $desc) {
			$pos = array_search($this->fields['config__type'], $desc['types']);
			
			if($pos !== false) {
				$options = isset($desc['options']) ? $desc['options'] : array();
				$choices = $desc['values'];
				
				if(isset($desc['types'][$pos + 1])) {
					//TODO gérer les différents types d'héritage
					$choices[self::INHERIT_VALUE] = __('Inherit from global config', 'tickettransfer');
					//$desc['types'][$pos+1]	
					//$globalconfig = PluginTickettransferGlobalconfig::getFields()[$name];
					//. ' (' . $choices[$globalconfig] . ')';
				}
				
				if($this->fields[$param] != self::INHERIT_VALUE && self::isMultipleParam($param)) {
					$options['values'] = importArrayFromDB($this->fields[$param]);
				} else {
					$options['values'] = array($this->fields[$param]);
				}
				
				echo "<tr class='tab_bg_2'>";
				echo "<td>" . $desc['text'] . "</td><td>";
				if($can_write) {
					Dropdown::showFromArray($param, $choices, $options);
				} else {
					foreach($options['values'] as $value) {
						echo $choices[$value] . '</br>';
					}
				}
				echo "</td></tr>";
			}
		}
		
		if($can_write) {
			echo '<tr class="tab_bg_1">';
			echo '<td class="center" colspan="2">';
			echo '<input type="hidden" name="id" value="' . $this->getID() . '">';
			echo '<input type="submit" name="update"' . _sx('button', 'Upgrade') . ' class="submit">';
			echo '</td></tr>';
		}
		echo "</table>";
		Html::closeForm();
	}
	
	private static function isMultipleParam($param) {
		$desc = self::getConfigParams()[$param];
		return isset($desc['options']) && isset($desc['options']['multiple']) && $desc['options']['multiple'];
	}

}
?>


























