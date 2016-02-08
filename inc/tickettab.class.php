<?php
class PluginTickettransferTickettab extends CommonDBTM {
	const TRANSFER_TYPE_ENTITY = 'entity';
	const TRANSFER_TYPE_GROUP = 'group';
	const TRANSFER_MODE_KEEP = 'keep';
	const TRANSFER_MODE_AUTO = 'auto';

	/**
	 * Détermine si le ticket peut être transféré par cet utilisateur. Cela nécessite les droits pour : - modifier le
	 * ticket - créer un suivi - la config autorise le transfert
	 *
	 * @param Ticket $ticket
	 *        	le ticket à tester
	 */
	static function canTansfer(Ticket $ticket) {
		$config = PluginTickettransferConfig::getConfigValues();
		
		$fup = new TicketFollowup();
		$fuptestinput = array(
			'content' => 'test',
			'tickets_id' => $ticket->getID(),
			'requesttypes_id' => '1',
			'is_private' => '0' 
		);
		
		return $ticket->can($ticket->getID(), 'w') && $fup->can(- 1, 'w', $fuptestinput) && (
				 ($config['allow_transfer'] || $config['allow_group'])) ;
	}

	function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
		if($item->getType() == 'Ticket' && self::canTansfer($item)) {
			return __("Ticket transfer", 'tickettransfer');
		}
		return '';
	}

	static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
		if($item->getType() == 'Ticket' && self::canTansfer($item)) {
			self::showForm($item);
		}
		return true;
	}

	/**
	 * Calcule les valeurs à afficher par défaut dans l'onglet de transfert
	 *
	 * @param Ticket $ticket        	
	 * @return array tableau de valeurs par défaut
	 */
	static function getFormValues($ticket) {
		$config = PluginTickettransferConfig::getConfigValues();
		
		// Calcul des valeurs par défaut
		$form_values = array(
			'transfer_type' => $config['allow_transfer'] ? self::TRANSFER_TYPE_ENTITY : self::TRANSFER_TYPE_GROUP,
			'entities_id' => $ticket->fields['entities_id'],
			'current_entities_id' => $ticket->fields['entities_id'],
			'type' => $ticket->fields['type'],
			'itilcategories_id' => $ticket->fields['itilcategories_id'],
			'is_group_observer' => $ticket->haveAGroup(CommonITILActor::OBSERVER, $_SESSION["glpigroups"]),
			'is_user_observer' => $ticket->isUser(CommonITILActor::OBSERVER, Session::getLoginUserID()),
			'transfer_justification' => '',
			'allowed_entities' => $config['allowed_entities']
		);
		
		// défini un groupe au hasard parmis ceux auxquels le ticket est affecté
		$groups = $ticket->getGroups(CommonITILActor::ASSIGN);
		if(count($groups) >= 1) {
			reset($groups);
			$form_values['groups_id_assign'] = current($groups)['groups_id'];
		}
		
		switch($config['default_observer_option']) {
			case 'nochange' :
				$form_values['observer_option'] = $form_values['is_user_observer'];
				break;
			case 'yesifnotingroup' :
				$form_values['observer_option'] = !$form_values['is_group_observer'];
				break;
			case 'yes' :
				$form_values['observer_option'] = true;
				break;
			case 'no' :
				$form_values['observer_option'] = false;
				break;
		}
		
		// Ecrase les valeurs par défaut là où on a une valeur sauvegardée
		if(isset($_SESSION['plugin']['tickettransfer']['savedPOST'])) {
			foreach($_SESSION['plugin']['tickettransfer']['savedPOST'] as $key => $val) {
				$form_values[$key] = $val;
			}
			unset($_SESSION['plugin']['tickettransfer']['savedPOST']);
		}
		
		return $form_values;
	}

	/**
	 * Affiche l'onglet de transfert du plugin
	 *
	 * @param Ticket $ticket
	 *        	ticket pour lequel on affiche l'onglet
	 */
	static function showForm(Ticket $ticket) {
		$form_values = self::getFormValues($ticket);
		
		?>

<form action="<?php echo self::getFormURL();?>" method="post">
	<table class="tab_cadre_fixe">
		<tr class="headerRow">
			<th colspan="2"><?php self::tranfertypeDropdown($form_values);?></th>
		</tr>

		<tr class="tab_bg_2">
			<td id="zone_transfer_type_tickettransfer">
				<table width="100%">
					<tr class="tab_bg_1 tickettransferentity">
						<td width="30%"><?php echo __('Destination entity'); ?></td>
						<td width="70%"><?php
		PluginTickettransferEntitiesdropdown::entitiesDropdown(implode(',', $form_values['allowed_entities']), 
				array(
					'value' => $form_values['entities_id'],
					'name' => 'entities_id',
					'rand' => '_tickettransfer'
				));
		?></td>
					</tr>

					<tr class="tab_bg_2 tickettransferentity">
						<td width="30%"><?php echo __('Type'); ?></td>
						<td width="70%"><?php
		Ticket::dropdownType('type', 
				array(
					'rand' => '_tickettransfer',
					'value' => $form_values['type'] 
				));
		?></td>
					</tr>

					<tr class="tab_bg_1 tickettransferentity">
						<td width="30%"><?php echo __('Category'); ?></td>
						<td width="70%"><span
							id='selectZone_itilcategories_id_tickettransfer'>
									<?php self::categoriesDropdown($form_values); ?>
								</span></td>
					</tr>

					<tr class="tab_bg_2 tickettransferentity">
						<td width="30%"><?php echo __('Transfer mode', 'tickettransfer'); ?></td>
						<td width="70%"><span
							id="selectZone_transfer_options_tickettransfer">
									<?php self::showTransferOptions($form_values); ?>
								</span></td>
					</tr>

					<tr class="tab_bg_2 tickettransfergroup">
						<td width="30%"><?php echo __('Destination group'); ?></td>
						<td width="70%"><?php
		Group::dropdown(
				array(
					'name' => 'groups_id_assign',
					'rand' => '_tickettransfer',
					'entity' => $form_values['current_entities_id'],
					'display_emptychoice' => false,
					'value' => $form_values['groups_id_assign'],
					'condition' => '`is_assign`' 
				));
		?></td>
					</tr>

					<tr class="tab_bg_1">
						<td width="30%">
									<?php echo $form_values['is_user_observer'] ? __('Keep me observer', 'tickettransfer'):__('Add me as observer', 'tickettransfer'); ?>
								</td>
						<td width="70%"><input type="checkbox" name="observer_option"
							<?php 
								echo $form_values['observer_option'] ? ' checked>' : '>';
								if($form_values['is_group_observer']) {
									echo ' <span title="'.__('This option only sets if you are personnaly observer, but it will not change observer groups. This means you will stay observer no matter what', 'tickettransfer').'">('.__('You are in an observer group', 'tickettransfer').')</span>';
								}
							?>
							 
						</td>
					</tr>
				</table>
			</td>

			<td>
						<?php echo __('Transfer explanation / justification', 'tickettransfer'); ?> :</br>
				<textarea name="transfer_justification" cols="60" rows="6"><?php echo $form_values['transfer_justification']; ?></textarea>
			</td>
		</tr>

		<tr class="tab_bg_1">
			<td class="center" colspan="2"><input type="hidden" name="id"
				value=<?php echo $ticket->getID(); ?>> <input type="submit"
				name="transfer_ticket" value="<?php echo __('Transfer'); ?>"
				class="submit"></td>
		</tr>
	</table>
		<?php
		Html::closeForm();
		include GLPI_ROOT . "/plugins/tickettransfer/scripts/tickettab.js.php";
	}

	/**
	 * Affiche le dropdown de sélection du type de transfert
	 */
	static function tranfertypeDropdown($form_values) {
		$config = PluginTickettransferConfig::getConfigValues();
		$value = $form_values['transfer_type'];
		
		$has2options = $config['allow_transfer'] && $config['allow_group'];
		
		$transfer_type_options = array(
			self::TRANSFER_TYPE_ENTITY => __('Transfer somewhere else', 'tickettransfer'),
			self::TRANSFER_TYPE_GROUP => __('Transfer to an other group', 'tickettransfer') 
		);
		
		if(! $has2options) {
			echo $transfer_type_options[$value];
			echo '<input id="dropdown_transfer_type_tickettransfer" name="transfer_type" type="hidden" value="' . $value .
					 '">';
		} else {
			Dropdown::showFromArray("transfer_type", $transfer_type_options, 
					array(
						'rand' => '_tickettransfer',
						'value' => $value 
					));
		}
	}

	/**
	 * Affiche le dropdown de sélection des catégories
	 *
	 * @param array $input
	 *        	tableau des valeurs à choisir par défaut - entities_id entité sélectionnée - type type de catégorie
	 *        	sélectionné - itilcategories_id id de la catégorie à sélectionner par défaut, ou ''
	 */
	static function categoriesDropdown($input) {
		$condition = "`" . ($input['type'] == Ticket::INCIDENT_TYPE ? 'is_incident' : 'is_request') . "`='1'";
		if($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
			$condition .= " AND `is_helpdeskvisible`='1'";
		}
		
		ITILCategory::dropdown(
				array(
					'name' => 'itilcategories_id',
					'rand' => '_tickettransfer',
					'entity' => $input['entities_id'],
					'display_emptychoice' => false,
					'value' => isset($input['itilcategories_id']) ? $input['itilcategories_id'] : '',
					'condition' => $condition 
				));
	}

	/**
	 * Affiche le dropdown permettant de choisir le mode de transfert
	 *
	 * @param array $input
	 *        	tableau des valeurs à choisir par défaut - itilcategories_id id de la catégorie sélectionnée -
	 *        	transfer_option option à choisir par défaut, ou ''
	 */
	static function showTransferOptions($input) {
		$config = PluginTickettransferConfig::getConfigValues();
		
		$category = new ITILCategory();
		$category->getFromDB($input['itilcategories_id']);
		$hasgroup = ! empty($category->fields['groups_id']);
		
		$transfer_mode_options = array(
			self::TRANSFER_MODE_KEEP => __('Keep attribution', 'tickettransfer'),
			self::TRANSFER_MODE_AUTO => __('Automatic transfer', 'tickettransfer') 
		);
		
		if(! $hasgroup) {
			echo '<span title="' . __('This category does not allow automatic transfer', 'tickettransfer') . '">';
			echo $transfer_mode_options[self::TRANSFER_MODE_KEEP];
			echo '</span>';
			echo '<input name="transfer_option" type="hidden" value="' . self::TRANSFER_MODE_KEEP . '">';
		} else {
			Dropdown::showFromArray("transfer_option", $transfer_mode_options, 
					array(
						'value' => $config['default_transfer_mode'] 
					));
		}
	}
}

?>




















