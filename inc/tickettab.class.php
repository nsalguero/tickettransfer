<?php
class PluginTickettransferTickettab extends CommonDBTM {
	static function isTabVisible() {
		return isset ( $_SESSION['plugin']['tickettransfer']['profileconfig']['transfer_toentity']) and 
			$_SESSION['plugin']['tickettransfer']['profileconfig']['transfer_toentity'] == '1' and
			Session::haveRight ( "update_ticket", "1" );
	}
	
	
	function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
		if ($item->getType () == 'Ticket' and self::isTabVisible ()) {
			return __ ( "Ticket transfer", 'tickettransfer' );
		}
		return '';
	}
	
	
	static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
		if ($item->getType () == 'Ticket' and self::isTabVisible ()) {
			// on affiche le formulaire pour le ticket sélectionné
			(new self ())->showForm ( $item->getField ( 'id' ) );
		}
		return true;
	}
	
	/**
	 * Fonction qui affiche le formulaire du plugin
	 * 
	 * @param type $id
	 *        	id du profil pour lequel on affiche les droits
	 * @param type $options        	
	 * @return boolean
	 */
	function showForm($id, $options = array()) {
		global $DB;
		global $CFG_GLPI;
		
		$rand = mt_rand ();
		
		$target = $this->getFormURL ();
		if (isset ( $options ['target'] )) {
			$target = $options ['target'];
		}
		
		$ticket = new Ticket ();
		$ticket->getFromDB ( $id );
		
		$tt = $ticket->getTicketTemplateToUse(0, $ticket->fields['type'], $ticket->fields['itilcategories_id'], $ticket->fields['entities_id']);
		$allow_empty_category = !$tt->isMandatoryField('itilcategories_id');
		
		if(isset($_SESSION['plugin']['tickettransfer']['savedPOST'])) {
			$default_values = $_SESSION['plugin']['tickettransfer']['savedPOST'];
			unset($_SESSION['plugin']['tickettransfer']['savedPOST']);
		} else {
			$default_values = array (
					'id' => $id,
					'entities_id' => $ticket->fields ['entities_id'],
					'type' => $ticket->fields ['type'],
					'itilcategories_id' => $ticket->fields ['itilcategories_id'],
					'transfer_justification' => ''
			);
		}
		
		
		echo "<form action='" . $target . "' method='post'>";
		
		
		echo "<table class='tab_cadre_fixe'>";
		
		// titre contenant le choix entité / groupe
		echo "<tr class='headerRow'><th colspan='2'>" . __ ( "Transfer to an other entity", 'tickettransfer' ) . "</th></tr>";
		
		echo "<tr class='tab_bg_2'>";
		
		//Choix de la destination
		echo"<td><table width='100%'>";
			// Dropdown entités
			echo "<tr><td width='30%'>" . __ ( 'Destination entity' ) . "</td>";
			echo "<td width='70%'>";
			self::entitiesDropdown($default_values['entities_id'], $default_values['type'], $default_values['itilcategories_id'], $allow_empty_category);
			echo "</td><td colspan='2'></td></tr>";
			
			// Dropdown type
			echo "<tr class='tab_bg_2'><td width='30%'>" . __ ( 'Type' ) . "</td>";
			echo "<td width='70%'><span id='type_select_area'>";
			self::typesDropdown($default_values['entities_id'], $default_values['type'], $default_values['itilcategories_id'], $allow_empty_category);
			echo "</span></td><td colspan='2'></td></tr>";
			
			// Dropdown catégories
			echo "<tr class='tab_bg_2'><td width='30%'>" . __ ( 'Category' ) . "</td>";
			echo "<td width='70%'><span id='category_select_area'>";
			self::categoriesDropdown($default_values['entities_id'], $default_values['type'], $default_values['itilcategories_id'], $allow_empty_category);
			echo "</span></td><td colspan='2'></td></tr>";
			
			// Dropdown désattribution
			echo "<tr class='tab_bg_2'><td width='30%'>" . __ ( 'Change my role', 'tickettransfer') . "</td>";
			echo "<td width='70%'><span id='category_select_area'>";
			
			$attribution_options = array();
			$is_assigned = $ticket->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID());
			$is_observer = $ticket->isUser(CommonITILActor::OBSERVER, Session::getLoginUserID());
			$is_group_observer = $ticket->haveAGroup(CommonITILActor::OBSERVER, $_SESSION["glpigroups"]);
			
			$tooltip = __('This option allows you to change your role on the ticket after the transfer', 'tickettransfer');
			if($is_group_observer) {
				$tooltip .= "\n".__('Because your belong to one of the observer groups, you cannot stop being an observer', 'tickettransfer');
				$attribution_options = array(
						'observer_only' => __('Observer only', 'tickettransfer'),
						'assign_and_observer' => __('Assigned and observer', 'tickettransfer')
				);
			} else {
				$attribution_options = array(
						'assign_only' => __('Assigned only', 'tickettransfer'),
						'observer_only' => __('Observer only', 'tickettransfer'),
						'assign_and_observer' => __('Assigned and observer', 'tickettransfer'),
						'none' => __('No role anymore', 'tickettransfer')
				);
			}
			
			if($is_assigned && ($is_observer || $is_group_observer)) {
				$currentrole = 'assign_and_observer';
			} else if($is_assigned) {
				$currentrole = 'assign_only';
			} else if($is_observer || $is_group_observer) {
				$currentrole = 'observer_only';
			} else {
				$currentrole = 'none';
			}
			$attribution_options[$currentrole] .= " (".__('current role', 'tickettransfer').")";
			if(!isset($default_values['attribution_options'])) {
				$default_values['attribution_options'] = $currentrole;
			}
			
			echo "<span class='info' title='$tooltip'>";
			Dropdown::showFromArray("attribution_options", $attribution_options, array('value'=>$default_values['attribution_options']));
			echo "</span>";
			echo "</span></td><td colspan='2'></td></tr>";
			
		echo "</td></tr></table></td>";
		
		// Commentaire
		echo "<td colspan='1'>";
			echo __('Transfer explanation / justification', 'tickettransfer')." :</br>";
			echo "<textarea name='transfer_justification' cols='60' rows='6'>".$default_values['transfer_justification']."</textarea>";
		echo "</td>";
		
		echo "</tr>";
		
		
		echo "<tr class='tab_bg_1'>";
		echo "<td class='center' colspan='2'>";
		echo "<input type='hidden' name='id' value=$id>";
		echo "<input type='submit' name='transfer_ticket' value='" . __ ( 'Transfer' ) . "' class='submit'>";
		echo "</td></tr>";
		echo "</table>";
		Html::closeForm ();
	}
	
	static function entitiesDropdown($currententity, $currenttype, $currentcategory, $allow_empty_category) {
		global $CFG_GLPI;
	
		$rand = Entity::dropdown ( array (
				'value' => $currententity
		) );
		Ajax::updateItemOnSelectEvent ( "dropdown_entities_id$rand", "type_select_area",
				$CFG_GLPI ["root_doc"] . "/plugins/tickettransfer/ajax/dropdownTypes.php",
				array (
						'entity' => '__VALUE__',
						'type' => $currenttype,
						'category' => $currentcategory,
						'allow_empty_category' => $allow_empty_category
				) );
		Ajax::updateItemOnSelectEvent ( "dropdown_entities_id$rand", "category_select_area",
				$CFG_GLPI ["root_doc"] . "/plugins/tickettransfer/ajax/dropdownCategories.php",
				array (
						'entity' => '__VALUE__',
						'type' => $currenttype,
						'category' => $currentcategory,
						'allow_empty_category' => $allow_empty_category
				) );
	}
	
	static function typesDropdown($currententity, $currenttype, $currentcategory, $allow_empty_category) {
		global $CFG_GLPI;
		
		$rand = Ticket::dropdownType('type', array (
				'value' => $currenttype
		) );
		Ajax::updateItemOnSelectEvent ( "dropdown_type$rand", "category_select_area",
				$CFG_GLPI ["root_doc"] . "/plugins/tickettransfer/ajax/dropdownCategories.php",
				array (
						'entity' => $currententity,
						'type' => '__VALUE__',
						'category' => $currentcategory, 
						'allow_empty_category' => $allow_empty_category
				) );
	}
	
	
	static function categoriesDropdown($currententity, $currenttype, $currentcategory, $allow_empty_category) {
		global $CFG_GLPI;
		
		$condition = "`" . ($currenttype == Ticket::INCIDENT_TYPE ? 'is_incident' : 'is_request') . "`='1'";
		if ($_SESSION ["glpiactiveprofile"] ["interface"] == "helpdesk") {
			$condition .= " AND `is_helpdeskvisible`='1'";
		}
		
		ITILCategory::dropdown ( array (
				'entity' => $currententity,
				'display_emptychoice' => $allow_empty_category,
				'value' => $currentcategory,
				'condition' => $condition,
				'emptylabel' => "-----"
		) );
	}
}






















