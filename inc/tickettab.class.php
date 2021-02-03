<?php
class PluginTickettransferTickettab extends CommonDBTM {
   const TRANSFER_TYPE_REQUALIFICATION = 'requalification';
   const TRANSFER_TYPE_ESCALATION = 'escalation';
   const TRANSFER_MODE_KEEP = 'keep';
   const TRANSFER_MODE_AUTO = 'auto';

   /**
    * Determine if the ticket can be transfered by this user.
    * Needs to be able to update the ticket (actions induced by the transfer (actor modification, ticket followup) will be allowed even if corresponding rights are not set in the profile), and to have the specific right in tickettransfer config.
    * @param Ticket $ticket
    * @param $type transfer type to test (default 'any', tests if one of the transfers is allowed)
    * @return boolean true iff the current user is allowed to update this ticket
    */
   static function canTansfer(Ticket $ticket, $type='any') {
      $config = PluginTickettransferConfig::getConfigValues();

      if(!$ticket->can($ticket->getID(), UPDATE))
         return false;

      switch($type) {
         case self::TRANSFER_TYPE_REQUALIFICATION :
            return $config['allow_requalification'] && !empty($config['allowed_entities']);
         case self::TRANSFER_TYPE_ESCALATION :
            return $config['allow_escalation'];
         case 'any' :
            return $config['allow_requalification'] && !empty($config['allowed_entities']) || $config['allow_escalation'];
         default :
            return false;
      }
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if($item->getType() == 'Ticket' && self::canTansfer($item)) {
         return __('Ticket transfer', 'tickettransfer');
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if($item->getType() == 'Ticket') {
         self::showForm($item);
      }
      return true;
   }

   /**
    * Compute values to print in the tab. Takes into account global default values, current ticket values, and failed POST values if it exists
    * @param Ticket $ticket ticket for which the values are computed
    * @return array values to use in tab
    */
   static function getFormValues(Ticket $ticket) {
      $config = PluginTickettransferConfig::getConfigValues();

      // Calcul des valeurs par défaut
      $form_values = array(
         'transfertype' => $config['allow_requalification'] ? self::TRANSFER_TYPE_REQUALIFICATION : self::TRANSFER_TYPE_ESCALATION,
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
    * Show tickettransfer tab
    * @param Ticket $ticket ticket for which we print the tab
    */
   static function showForm(Ticket $ticket) {
      global $CFG_GLPI;
      $form_values = self::getFormValues($ticket);

      $action_url = self::getFormURL();

      $transfertypeId = 'dropdown_transfertype_tickettransfer';
      $transfertype_dd = self::makeTranfertypeDropdown($form_values);

      $entitiesId = 'dropdown_entities_id_tickettransfer';
      $entities_dd = Entity::dropdown(array(
            'entity' => $form_values['allowed_entities'],
            'value' => $form_values['entities_id'],
            'name' => 'entities_id',
            'rand' => '_tickettransfer',
            'display' => false
      ));


      $typeId = 'dropdown_type_tickettransfer';
      $type_dd = Ticket::dropdownType('type', array(
            'value' => $form_values['type'],
            'rand' => '_tickettransfer',
            'display' => false
      ));


      $zone_itilcategories = 'zone_itilcategories_tickettransfer';
      $categoriesId = 'dropdown_itilcategories_id_tickettransfer';
      $categories_dd = self::makeCategoriesDropdown($form_values);


      $zone_transfermode = 'zone_transfermode_tickettransfer';
      $transfermode_dd = self::makeTransfermodeDropdown($form_values);

      $groups_dd = Group::dropdown(array(
            'name' => 'groups_id_assign',
            'entity' => $form_values['current_entities_id'],
            'display_emptychoice' => false,
            'value' => $form_values['groups_id_assign'],
            'condition' => ['is_assign'],
            'rand' => '_tickettransfer',
            'display' => false
      ));


      $observer_msg = $form_values['is_user_observer'] ? __('Keep me observer', 'tickettransfer'):__('Add me as observer', 'tickettransfer');
      $observer_chkbox = '<input type="checkbox" name="observer_option"' . ($form_values['observer_option'] ? ' checked>' : '>');
      if ($form_values['is_group_observer']) {
         $observer_chkbox .= ' <span title="'.__('This option only sets if you are personnaly observer, but it will not change observer groups. This means you will stay observer no matter what', 'tickettransfer').'">('.__('You are in an observer group', 'tickettransfer').')</span>';
      }
      $form_values['transfer_justification'] = Html::cleanPostForTextArea($form_values['transfer_justification']);

      $translations = array(
            'dest_entity' => __('Destination entity'),
            'type' => __('Type'),
            'category' => __('Category'),
            'transfermode' =>__('Transfer mode', 'tickettransfer'),
            'dest_group' => __('Destination group', 'tickettransfer'),
            'transfer_justification' => __('Transfer explanation / justification', 'tickettransfer'),
            'transfer' => Html::cleanInputText(__('Transfer'))
         );

      echo <<<HTML
      <form action="$action_url" method="post">
         <table class="tab_cadre_fixe">
            <tr class="headerRow">
               <th colspan="2">$transfertype_dd</th>
            </tr>
            <tr class="tab_bg_2">
               <td>
                  <table width="100%">
                     <tr class="tab_bg_1 tickettransferrequalification">
                        <td width="30%">$translations[dest_entity]</td>
                        <td width="70%">$entities_dd</td>
                     </tr>
                     <tr class="tab_bg_2 tickettransferrequalification">
                        <td width="30%">$translations[type]</td>
                        <td width="70%">$type_dd</td>
                     </tr>
                     <tr class="tab_bg_1 tickettransferrequalification">
                        <td width="30%">$translations[category]</td>
                        <td id="$zone_itilcategories" width="70%">$categories_dd</td>
                     </tr>
                     <tr class="tab_bg_2 tickettransferrequalification">
                        <td width="30%">$translations[transfermode]</td>
                        <td id="$zone_transfermode" width="70%">$transfermode_dd</td>
                     </tr>
                     <tr class="tab_bg_2 tickettransferescalation">
                        <td width="30%">$translations[dest_group]</td>
                        <td width="70%">$groups_dd</td>
                     </tr>
                     <tr class="tab_bg_1">
                        <td width="30%">$observer_msg</td>
                        <td width="70%">$observer_chkbox</td>
                     </tr>
                  </table>
               </td>
               <td>
                  $translations[transfer_justification] :</br>
                  <textarea name="transfer_justification" cols="60" rows="6">$form_values[transfer_justification]</textarea>
               </td>
            </tr>
            <tr class="tab_bg_1">
               <td class="center" colspan="2"><input type="hidden" name="id"
                  value="{$ticket->getID()}"> <input type="submit"
                  name="transfer_ticket" value="$translations[transfer]"
                  class="submit"></td>
            </tr>
         </table>
HTML;
      Html::closeForm();
      echo <<<JS
      <script type='text/javascript'>
      $(function() {
         var ajaxUrl = '$CFG_GLPI[root_doc]/plugins/tickettransfer/ajax/transferOptions.php';

         $('#$transfertypeId').on('change', refreshTransferZone);
         $('#$entitiesId').on('change', refreshCategories);
         $('#$typeId').on('change', refreshCategories);
         $('#$categoriesId').on('change', refreshTransferOptions);

         refreshTransferZone();

         function refreshTransferZone() {
            var type = $('#$transfertypeId').val();
            var ntype = type === 'requalification' ? 'escalation' : 'requalification';
            $('.tickettransfer'+ntype).hide();
            $('.tickettransfer'+type).show();
         }

         function refreshCategories() {
            var currentcategory = $('#$categoriesId').val();
            $('#$zone_itilcategories').load(
                  ajaxUrl,
                  {
                     'request': 'itilcategories',
                     'entities_id': $('#$entitiesId').val(),
                     'type': $('#$typeId').val(),
                     'itilcategories_id': currentcategory,
                  },
                  function() {
                     if(currentcategory !== $('#$categoriesId').val()) {
                        refreshTransferOptions();
                     }
                     $('#$categoriesId').on('change', refreshTransferOptions);
                  }
               );
         }

         function refreshTransferOptions() {
            $('#$zone_transfermode').load(
                  ajaxUrl,
                  {
                     'request': 'transfermode',
                     'itilcategories_id': $('#$categoriesId').val(),
                  }
               );
         }

      });
      </script>
JS;

      //include GLPI_ROOT . "/plugins/tickettransfer/scripts/tickettab.js.php";
   }

   /**
    * Prepare HTML string for the transfertype dropdown.
    * @param array $form_values default form values. Must contain transfertype
    * @return string string HTML output to echo
    */
   static function makeTranfertypeDropdown($form_values) {
      $config = PluginTickettransferConfig::getConfigValues();
      $value = $form_values['transfertype'];

      $has2options = $config['allow_requalification'] && $config['allow_escalation'];

      $transfertype_options = array(
         self::TRANSFER_TYPE_REQUALIFICATION => __('Transfer somewhere else', 'tickettransfer'),
         self::TRANSFER_TYPE_ESCALATION => __('Transfer to an other group', 'tickettransfer')
      );

      if(! $has2options) {
         return $transfertype_options[$value].'<input id="dropdown_transfertype_tickettransfer" name="transfertype" type="hidden" value="' . $value . '">';
      } else {
         return Dropdown::showFromArray("transfertype", $transfertype_options, array(
               'value' => $value,
               'rand' => '_tickettransfer',
               'display' => false
            ));
      }
   }

   /**
    * Prepare HTML string for the itilcategory dropdown.
    * @param array $input input values. Must contain
    *    - entities_id
    *    - type
    *    - itilcategories_id
    * @return string HTML output to echo
    */
   static function makeCategoriesDropdown($input) {
      $opt = array(
            'name' => 'itilcategories_id',
            'entity' => $input['entities_id'],
            'display_emptychoice' => false,
            'rand' => '_tickettransfer',
            'display' => false
      );

      $condition = [($input['type'] == Ticket::INCIDENT_TYPE ? 'is_incident' : 'is_request') => 1];
      if($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
         $condition['is_helpdeskvisible'] = 1;
      }
      $opt['condition'] = $condition;

      if(self::isCategoryValid($input)) {
         $opt['value'] = $input['itilcategories_id'];
      }

      return ITILCategory::dropdown($opt);
   }

   /**
    * Prepare HTML string for the transfermode dropdown
    * @param array $input Must contain itilcategories_id
    * @return string HTML output to echo
    */
   static function makeTransfermodeDropdown($input) {
      $config = PluginTickettransferConfig::getConfigValues();

      $category = new ITILCategory();
      $category->getFromDB($input['itilcategories_id']);
      $hasgroup = ! empty($category->fields['groups_id']);

      $transfermode_options = array(
         self::TRANSFER_MODE_KEEP => __('Keep attribution', 'tickettransfer'),
         self::TRANSFER_MODE_AUTO => __('Automatic transfer', 'tickettransfer')
      );

      if(! $hasgroup) {
         return '<span title="' . __('This category does not allow automatic transfer', 'tickettransfer') . '">' .
               $transfermode_options[self::TRANSFER_MODE_KEEP] .
               '</span>' .
               '<input name="transfermode" type="hidden" value="' . self::TRANSFER_MODE_KEEP . '">';
      } else {
         return Dropdown::showFromArray("transfermode", $transfermode_options, array(
               'value' => $config['default_transfer_mode'],
               'display' => false
		   ));
      }
   }

   /**
    * Tests if a given conbination of entity/type/category is valid
    *
    * @param array $input input values. Must contain
    *        - entities_id
    *        - type
    *        - itilcategories_id
    * @param ItilCategory $itilCategory objet representing the category. Optionnal, you can provide it to avoid reconstructing it here. If provided, you MUST have checked that the getFromDB succeded.
    * @return boolean true if the category is compatible with the entity and type provided
    */
   static function isCategoryValid($input, ItilCategory $itilCategory = NULL) {
      if ($itilCategory === NULL) {
         $itilCategory = new ItilCategory();
         if (!$itilCategory->getFromDB($input['itilcategories_id']))
            return false;
      }

      if ($itilCategory->getEntityID() != $input['entities_id'] && !(in_array($input['entities_id'],
            getSonsOf("glpi_entities", $itilCategory->getEntityID())) && $itilCategory->isRecursive())) {
         return false;
      }

      if ($input['type'] == Ticket::INCIDENT_TYPE && $itilCategory->getField('is_incident') != 1)
         return false;

      if ($input['type'] == Ticket::DEMAND_TYPE && $itilCategory->getField('is_request') != 1)
         return false;

      return true;
   }
}
