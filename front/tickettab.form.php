<?php
include ("../../../inc/includes.php");

Session::checkLoginUser();

// Read configuration (for current user & profile)
$config = PluginTickettransferConfig::getConfigValues();

/* ****************************************************
 * Basic field validation (only check well-formed inputs)
 * Should always pass, user errors are treated further
 * **************************************************** */
function onInvalidInput($param = '') {
   Session::addMessageAfterRedirect(__('Invalid input : ', 'tickettransfer') . $param, false, ERROR);
   Html::back();
}

$ticket = new Ticket();
$entity = new Entity();
$itilCategory = new ITILCategory();
$group_ticket = new Group();

if (!(isset($_POST['id']) && $ticket->getFromDB($_POST['id']))) {
   onInvalidInput('ticket id');
}


if (isset($_POST['transfertype']) && $_POST['transfertype'] == PluginTickettransferTickettab::TRANSFER_TYPE_REQUALIFICATION) {
   // entity validation
   if (!(isset($_POST['entities_id']) /*entities_id is defined*/
         && $entity->getFromDB($_POST['entities_id']) /*entity exists*/
         && in_array($_POST['entities_id'], $config['allowed_entities']) /*and is allowed*/
         )) {
      onInvalidInput('entity id');
   }

   // type validation
   if (!(isset($_POST['type']) /*type is defined*/
         && isset(Ticket::getType()[$_POST['type']]) /*and is valid*/
         )) {
      onInvalidInput('ticket type');
   }

   // category validation
   if (!(isset($_POST['itilcategories_id']) /*itilcategories_id is defined*/
         && $itilCategory->getFromDB($_POST['itilcategories_id']) /*category exists*/
         && PluginTickettransferTickettab::isCategoryValid($_POST) /*category is valid considering entity and type*/
         )) {
      onInvalidInput('ticket category');
   }

   // transfermode validation
   if (!(isset($_POST['transfermode']) /*transfermode is defined*/
         && ($_POST['transfermode'] == PluginTickettransferTickettab::TRANSFER_MODE_KEEP || $_POST['transfermode'] == PluginTickettransferTickettab::TRANSFER_MODE_AUTO && !empty($itilCategory->fields['groups_id'])) /*has a valid value*/
         )) {
      onInvalidInput('transfer option');
   }

   unset($_POST['groups_id_assign']);
} else if (isset($_POST['transfertype']) && $_POST['transfertype'] == PluginTickettransferTickettab::TRANSFER_TYPE_ESCALATION) {
   // group validation
   // TODO $allowedGroups = array(); manage limited group
   if (!(isset($_POST['groups_id_assign']) /*groups_id_assign is defined*/
         && $group_ticket->getFromDB($_POST['groups_id_assign']) /*group exists*/
         /*&& in_array($_POST['groups_id_assign'], $allowedGroups) /*and is allowed*/
			)) {
      onInvalidInput('group');
   }

   unset($_POST['entities_id']);
   unset($_POST['type']);
   unset($_POST['itilcategories_id']);
   unset($_POST['transfermode']);
} else {
   onInvalidInput('transfer type');
}

// observer_option checkbox validation
$_POST['observer_option'] = isset($_POST['observer_option']) && $_POST['observer_option'] == 'on';

// transfer_justification validation
if (!(isset($_POST['transfer_justification']))) {
   onInvalidInput('transfer message');
}

/* *********************************
 * User input validation
 * (may fail depending on what user did)
 * ************************************* */

// Save user input in order to re-display the form with already set inputs in case of failure
// This save must be made AFTER basic input validation, otherwise it could be exploited to reload the page with normally unallowed parameters
$_SESSION['plugin']['tickettransfer']['savedPOST'] = $_POST;

// Check user rights
if (!PluginTickettransferTickettab::canTansfer($ticket, $_POST['transfertype'])) {
   Html::displayRightError();
}

// Refuses transfer if nothing has changed
if ($_POST['transfertype'] == PluginTickettransferTickettab::TRANSFER_TYPE_REQUALIFICATION /*for requalification*/
      && $_POST['entities_id'] == $ticket->getField('entities_id') /*entity has not changed*/
      && $_POST['type'] == $ticket->getField('type') /*type has not changed*/
      && $_POST['itilcategories_id'] == $ticket->getField('itilcategories_id') /*category has not changed*/
      ) {
   Session::addMessageAfterRedirect(__('You must change the ticket location to do a transfer (at least one in entity, type or category)', 'tickettransfer'), false, ERROR);
   Html::back();
}

if ($_POST['transfertype'] == PluginTickettransferTickettab::TRANSFER_TYPE_ESCALATION /*for escalation*/
      && $ticket->haveAGroup(CommonITILActor::ASSIGN, array(
      $_POST['groups_id_assign']
)) /*ticket is already assigned to this group*/
      ) {
   Session::addMessageAfterRedirect(__('You must chose a new group to do a transfer', 'tickettransfer'), false, ERROR);
   Html::back();
}

if (empty($_POST['transfer_justification']) && $config['force_justification']) {
   Session::addMessageAfterRedirect(__('You must give a transfer justification', 'tickettransfer'), false, ERROR);
   Html::back();
}

/* *************
 * Real transfer
 * ************** */
// save notification setting then disable it
$save_mail = $CFG_GLPI["use_mailing"];
$CFG_GLPI["use_mailing"] = false;

$ticket_id = $_POST['id'];
$ticket_user = new Ticket_User();
$ticket_group = new Group_Ticket();

$ticket_inputs = array();
$ticket_user_delete = array();
$ticket_group_delete = array();

// Requalification
if ($_POST['transfertype'] == PluginTickettransferTickettab::TRANSFER_TYPE_REQUALIFICATION) {
   $ticket->update(array(
         'id' => $ticket_id,
         'entities_id' => $_POST['entities_id'],
         'type' => $_POST['type'],
         'itilcategories_id' => $_POST['itilcategories_id']
   ));

   // if auto-escalation, set group to assign
   if ($_POST['transfermode'] == PluginTickettransferTickettab::TRANSFER_MODE_AUTO) {
      $_POST['groups_id_assign'] = $itilCategory->fields['groups_id'];
   }
}

// Escalation
$groupAlreadyAssign = true; // $groupAlreadyAssign tracks if the assign group is changed
if (isset($_POST['groups_id_assign'])) {
   $groupAlreadyAssign = false;

   // Remove all existing groups, except the target group if it was already assigned
   foreach ( $ticket->getGroups(CommonITILActor::ASSIGN) as $group_ticket ) {
      if ($group_ticket['groups_id'] != $_POST['groups_id_assign']) {
         $ticket_group->delete(array(
               'id' => $group_ticket['id']
         ));
      } else {
         $groupAlreadyAssign = true;
      }
   }

   // List dest group users
   $dest_group_users = array();
   foreach ( Group_User::getGroupUsers($_POST['groups_id_assign']) as $user ) {
      $dest_group_users[] = $user['id'];
   }

   if (!$groupAlreadyAssign) {
      // add destination group
      $ticket_group->add(array(
            'type' => CommonITILActor::ASSIGN,
            'groups_id' => $_POST['groups_id_assign'],
            'tickets_id' => $ticket_id
      ));

      // remove all assign user not in dest group
      $currentAssignUsers = $ticket->getUsers(CommonITILActor::ASSIGN);
      foreach ( $currentAssignUsers as $tu ) {
         if (!in_array($tu['users_id'], $dest_group_users)) {
            $ticket_user->delete(array(
                  'id' => $tu['id']
            ));
         }
      }
   }
}

// Manage 'stay opserver' option
$user_id = Session::getLoginUserID();

// Add self as observer
if ($_POST['observer_option'] && !$ticket->isUser(CommonITILActor::OBSERVER, $user_id)) {
   $ticket_user->add(array(
         'type' => CommonITILActor::ASSIGN,
         'users_id' => Session::getLoginUserID(),
         'tickets_id' => $ticket_id
   ));
}

// Remove self from observers
if (!$_POST['observer_option'] && $ticket->isUser(CommonITILActor::OBSERVER, $user_id)) {
   $found = $ticket_user->find("tickets_id = $ticket_id AND type = " . CommonITILActor::OBSERVER . " AND users_id=$user_id");

   foreach ( $found as $id => $tu ) {
      $ticket_user->delete(array(
            'id' => $id
      ));
   }
}

// Ajout du suivi
$fup = new TicketFollowup();
if ($_POST['transfer_justification'] != '') {
   $fup->add(array(
         'content' => $config['justification_prefix'] . ($config['justification_prefix'] ? ' : \n' : '') . $_POST['transfer_justification'],
         'tickets_id' => $ticket_id,
         'requesttypes_id' => '1',
         'is_private' => '0'
   ));
}

// restore notificaiton setting
$CFG_GLPI["use_mailing"] = $save_mail;

//Log transfer
Event::log($ticket_id, "ticket", 4, "tracking",
      // TRANS: %s is the user login
      sprintf(__('%s transfers a ticket', 'tickettransfer'), $_SESSION["glpiname"]));

Session::addMessageAfterRedirect(__('Successful transfer', 'tickettransfer'), false, INFO);
unset($_SESSION['plugin']['tickettransfer']['savedPOST']);

// Manage notifications
$author = new User();
$author->getFromDB(Session::getLoginUserID());

$ticket->__tickettransfer = array(
      'author' => $author->getName(),
      'message' => $_POST['transfer_justification'],
      'groupchanged' => !$groupAlreadyAssign
);

if ($_POST['transfertype'] == PluginTickettransferTickettab::TRANSFER_TYPE_REQUALIFICATION /*requalification*/
       && ($config['notif_requalification'] === 'always' || /*always notify*/
             $config['notif_requalification'] === 'ongroupchange' && !$groupAlreadyAssign/*or only when group changed*/
             )) {
   NotificationEvent::raiseEvent('plugin_tickettransfer_requalification', $ticket);
} else if ($_POST['transfertype'] == PluginTickettransferTickettab::TRANSFER_TYPE_ESCALATION /*escalation*/
      && ($config['notif_escalation'] === 'always' ||  /*always notify*/
            $config['notif_escalation'] === 'onassinglost' && !in_array(Session::getLoginUserID(), $dest_group_users) /*or only when current user is not assign anymore*/
            )) {
   NotificationEvent::raiseEvent('plugin_tickettransfer_escalation', $ticket);
}

// Redirect
if ($ticket->can($ticket_id, READ)) {
   Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=$ticket_id");
} else {
   Session::addMessageAfterRedirect(__('You have been redirected because you no longer have access to this ticket'), true, ERROR);
   $ticket->redirectToList();
}

