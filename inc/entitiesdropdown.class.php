<?php
class PluginTickettransferEntitiesdropdown {

	/**
	 * Affiche le dropdown de sélection des entités sur me modèle de ce qui se fait dans le coeur de GLPI, sauf que permet de sélectionner une entité normalement inaccessible à ce profil.
	 * Très fortement inspiré du fichier ajax/dropdownValue.php du coeur de GLPI (sans la restriction aux entités visibles, et simplifié car moins générique). Aussi bien $entities_list que $value ont intérêt à avoir été contrôlés car ils sont utilisés dans une requète SQL
	 *
	 * @param string $entities_list
	 *        	liste des entité sélectionnables (array d'ids)
	 * @param array options options facultatives: 
	 * 		- name => nom du dropdown (champ "name") (défaut 'entities_id') 
	 *		- value => entité sélectionnée au départ (défaut -1) 
	 *		- rand => valeur à concaténer à l'id du dropdown ('dropdown_$name'.$rand doit être unique) (defaut => valeur aléatoire)
	 */
	static function entitiesDropdown($entities_list, $options = array()) {
		global $DB;
		
		$params = array(
			'name' => 'entities_id',
			'value' => - 1,
			'rand' => mt_rand() 
		);
		
		if(is_array($options) && count($options)) {
			foreach($options as $key => $val) {
				$params[$key] = $val;
			}
		}
		
		$entity = new Entity();
		$entities_table = Entity::getTable();
		$entities_list = implode(',', $entities_list);
		
		// récupération de la liste des entités à afficher (sauf value si elle est définie)
		$query = "SELECT *
			FROM `" . $entities_table . "`
			WHERE `id` IN ($entities_list) AND NOT `id`=" . $params['value'] . "
			ORDER BY `completename`";
		
		if($result = $DB->query($query)) {
			echo '<select id="dropdown_' . $params['name'] . $params['rand'] . '" name="' . $params['name'] .
					 '" size="1">';
			
			// affiche la valeur sélectionnée en premier
			if($params['value'] != - 1) {
				$entity->getFromDB($params['value']);
				echo self::getOptionString($entity->fields, true, false);
			}
			
			$last_level_displayed = array();
			
			if($DB->numrows($result)) {
				while($data = $DB->fetch_assoc($result)) {
					self::printParentContext($last_level_displayed, $data);
					$last_level_displayed[$data['level']] = $data['id'];
					echo self::getOptionString($data);
				}
			}
			echo "</select>";
		}
	}


	private static function printParentContext(&$last_level_displayed, $data) {
		if($data['level'] > 1) {
			$level = $data['level'];
			$entity = new Entity();
			
			// Last parent is not the good one need to display arbo
			if(! isset($last_level_displayed[$level - 1]) || ($last_level_displayed[$level - 1] != $data['entities_id'])) {
				
				$work_level = $level - 1;
				$work_parentID = $data['entities_id'];
				$to_display = '';
				
				do {
					// Get parent
					if($entity->getFromDB($work_parentID)) {
						$to_display = self::getOptionString($entity->fields, false, true) . $to_display;
						
						$last_level_displayed[$work_level] = $entity->fields['id'];
						$work_level --;
						$work_parentID = $entity->fields['entities_id'];
					} else { // Error getting item : stop
						$work_level = - 1;
					}
				} while(($work_level >= 1) && (! isset($last_level_displayed[$work_level]) ||
						 ($last_level_displayed[$work_level] != $work_parentID)));
				
				echo $to_display;
			}
		}
	}

	private static function getOptionString($entityfields, $selected = false, $disabled = false) {
		if(isset($entityfields['comment'])) {
			$title = sprintf(__('%1$s - %2$s'), $entityfields['completename'], $entityfields['comment']);
		} else {
			$title = $entityfields['completename'];
		}
		
		if($entityfields['level'] == 1) {
			$class = " class='treeroot'";
			$raquo = "";
		} else if($entityfields['level'] == 2) {
			$class = " class='tree b' ";
			$raquo = "&raquo;";
		} else {
			$class = " class='tree' ";
			$raquo = "&raquo;";
		}
		
		$ret = '<option ' . ($disabled ? 'disabled ' : '') . ($selected ? 'selected ' : '') . 'value="' .
				 $entityfields['id'] . '" ' . $class . ' title="' . Html::cleanInputText($title) . '">';
		$ret .= str_repeat("&nbsp;&nbsp;&nbsp;", $entityfields['level']) . $raquo . $entityfields['name'];
		$ret .= "</option>";
		
		return $ret;
	}
}




