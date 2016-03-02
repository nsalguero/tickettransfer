<?php
include ("../../../inc/includes.php");

Session::checkLoginUser();

// Lit la configuration (pour l'utilisateur et le profil sélectionnés)
$config = PluginTickettransferConfig::getConfigValues();

/*
 * ****************************************************
 * Validation des champs (sauf tentative de hack, devrait toujours être bon)
 * *******************************************************
 */
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

if (isset($_POST['transfer_type']) && $_POST['transfer_type'] == PluginTickettransferTickettab::TRANSFER_TYPE_ENTITY) {
   // validation du champ entité
   if (!(isset($_POST['entities_id']) && // le paramètre est défini
preg_match('/^[\d]+$/', $_POST['entities_id']) && // c'est un nombre
$entity->getFromDB($_POST['entities_id']) && // l'entité existe
in_array($_POST['entities_id'], $config['allowed_entities'])) // elle faite partie des entités vers lesquelles le transfert est autorisé
) {
      onInvalidInput('entity id');
   }

   // validation du type
   if (!(isset($_POST['type']) && // le paramètre est défini
($_POST['type'] == Ticket::INCIDENT_TYPE || $_POST['type'] == Ticket::DEMAND_TYPE)) // il a une des valeurs autorisées
) {
      onInvalidInput('ticket type');
   }

   // validation du champ catégorie
   if (!(isset($_POST['itilcategories_id']) && // le paramètre est défini
preg_match('/^[\d]+$/', $_POST['itilcategories_id']) && // c'est un nombre
$itilCategory->getFromDB($_POST['itilcategories_id']) && // la catégorie existe
$itilCategory->getField($_POST['type'] == Ticket::INCIDENT_TYPE ? 'is_incident' : 'is_request') && // elle est compatible avec le type
($itilCategory->getEntityID() == $entity->getID() || // elle est dans l'entité choisie
(in_array($entity->getID(), getSonsOf("glpi_entities", $itilCategory->getEntityID())) && $itilCategory->isRecursive())) // ou bien la catégorie est réccursive et l'entité choisie est sous l'entitée de référence de la catégorie
)) {
      onInvalidInput('ticket category');
   }

   // validation de l'option de réaffectation
   if (!(isset($_POST['transfer_option']) && // le paramètre est défini
($_POST['transfer_option'] == PluginTickettransferTickettab::TRANSFER_MODE_KEEP || $_POST['transfer_option'] == PluginTickettransferTickettab::TRANSFER_MODE_AUTO) && // il a une des valeurs autorisés
(!empty($itilCategory->fields['groups_id']) || $_POST['transfer_option'] == PluginTickettransferTickettab::TRANSFER_MODE_KEEP)) // l'option transfert n'est autorisée que si la catégorie a un groupe associé
) {
      onInvalidInput('transfer option');
   }

   unset($_POST['groups_id_assign']);
} else if (isset($_POST['transfer_type']) && $_POST['transfer_type'] == PluginTickettransferTickettab::TRANSFER_TYPE_GROUP) {
   // validation du champ groupe
   $allowedGroups = array(); // TODO réccupérer les groupes autorisés
   if (!(isset($_POST['groups_id_assign']) && // le paramètre est défini
preg_match('/^[\d]+$/', $_POST['groups_id_assign']) && // c'est un nombre
$group_ticket->getFromDB($_POST['groups_id_assign']) /*&& // le groupe existe
			in_array($_POST['groups_id_assign'], $allowedGroups)*/ // il fait partie des groupes vers lesquels le transfert est autorisé
			)) {
      onInvalidInput('group');
   }

   unset($_POST['entities_id']);
   unset($_POST['type']);
   unset($_POST['itilcategories_id']);
   unset($_POST['transfer_option']);
} else {
   onInvalidInput('transfer type');
}

// validation de la checkbox observer_option
$_POST['observer_option'] = isset($_POST['observer_option']) && $_POST['observer_option'] == 'on';

// validation du champ de commentaire
if (!(isset($_POST['transfer_justification']))) {
   onInvalidInput('transfer message');
}

// Sauve les données entrées afin de reprendre au même stade si l'action ne peut pas être faite
// Cette sauvegarde a lieu après la vérification de 'hack' sinon elle pourrait être exploitée pour recharger une page avec des valeur par défaut interdites
$_SESSION['plugin']['tickettransfer']['savedPOST'] = $_POST;

/*
 * ****************************************************
 * Refus de traitement dans le cas où il n'y a pas de transfert
 * *******************************************************
 */
if ($_POST['transfer_type'] == PluginTickettransferTickettab::TRANSFER_TYPE_ENTITY && $_POST['entities_id'] == $ticket->getField('entities_id') && $_POST['type'] == $ticket->getField('type') && $_POST['itilcategories_id'] == $ticket->getField('itilcategories_id')) {
   Session::addMessageAfterRedirect(__('You must change the ticket location to do a transfer (at least one in entity, type or category)', 'tickettransfer'), false, ERROR);
   Html::back();
}

if ($_POST['transfer_type'] == PluginTickettransferTickettab::TRANSFER_TYPE_GROUP && $ticket->haveAGroup(CommonITILActor::ASSIGN, array(
      $_POST['groups_id_assign']
))) {
   Session::addMessageAfterRedirect(__('You must chose a new group to do a transfer', 'tickettransfer'), false, ERROR);
   Html::back();
}

/*
 * ****************************************************
 * Vérification des droits / préparation des modifications
 *
 * Méthode générale : on fait le bilan des modifs demandées en checkant si l'utilisateur à les droits au fur et à mesure, mais sans faire les modifs
 * Si on arrive au bout des vérifs (ie l'utilisateur a tous les droits nécessaires), on execute toutes les actions demandées.
 * De cette façon, on évite de rester bloqué alors qu'on a déjà modifié des choses
 * *******************************************************
 */

// Vérifie le droit d'utilisation du plugin
if ($_POST['transfer_type'] == PluginTickettransferTickettab::TRANSFER_TYPE_ENTITY && !$config['allow_transfer'] || $_POST['transfer_type'] == PluginTickettransferTickettab::TRANSFER_TYPE_GROUP && !$config['allow_group']) {
   Html::displayRightError();
}

$ticket_user = new Ticket_User();
$ticket_group = new Group_Ticket();

$ticket_id = $_POST['id'];
$ticket_inputs = array();
$ticket_user_delete = array();
$ticket_group_delete = array();

// Déplacement du ticket
if ($_POST['transfer_type'] == PluginTickettransferTickettab::TRANSFER_TYPE_ENTITY) {
   $input = array(
         'id' => $ticket_id,
         'entities_id' => $_POST['entities_id'],
         'type' => $_POST['type'],
         'itilcategories_id' => $_POST['itilcategories_id']
   );

   $ticket->check($ticket_id, UPDATE);
   $ticket_inputs[] = $input;

   // préparation pour le changement de groupe dans le cas où on est en mode auto-transfert
   if ($_POST['transfer_option'] == PluginTickettransferTickettab::TRANSFER_MODE_AUTO) {
      $_POST['groups_id_assign'] = $itilCategory->fields['groups_id'];
   }
}

// Transfert à un autre groupe
$groupAlreadyAssign = true; // si pas de changement de groupe => $groupAlreadyAssign = true;
if (isset($_POST['groups_id_assign'])) { // couvre le cas transfert de groupe ET le cas changement de catégorie avec transfert de groupe automatique
   $groupAlreadyAssign = false;

   // Retirer les groupe existants
   foreach ( $ticket->getGroups(CommonITILActor::ASSIGN) as $group_ticket ) { // pour tous les groupes assignés à ce ticekt
      if ($group_ticket['groups_id'] != $_POST['groups_id_assign']) { // et qui ne sont pas le groupe sélectionné lors du transfert
         $ticket_group->check($group_ticket['id'], DELETE);
         $ticket_group_delete[] = array( // on retire le groupe
               'id' => $group_ticket['id']
         );
      } else {
         $groupAlreadyAssign = true;
      }
   }

   // Déterminer les utilisateurs qui sont dans le nouveau groupe
   $new_group_users = array();
   foreach ( Group_User::getGroupUsers($_POST['groups_id_assign']) as $user ) {
      $new_group_users[] = $user['id'];
   }

   // Ajouter le nouveau groupe
   if (!$groupAlreadyAssign) {
      $input = array(
            'id' => $ticket_id,
            '_itil_assign' => array(
                  '_type' => 'group',
                  'groups_id' => $_POST['groups_id_assign']
            )
      );
      //TODO passer à la fabrication directe d'un ticket_group
      $ticket->check($ticket_id, UPDATE);
      $ticket_inputs[] = $input;

      // retirer les techniciens existants s'il ne sont pas dans le groupe de destination
      $currentAssignUsers = $ticket->getUsers(CommonITILActor::ASSIGN);
      foreach ( $currentAssignUsers as $tu ) {
         if (!in_array($tu['users_id'], $new_group_users)) {
            $ticket_user->check($tu['id'], DELETE);
            $ticket_user_delete[] = array(
                  'id' => $tu['id']
            );
         }
      }
   }
}

// Gestion de l'option observateur
$user_id = Session::getLoginUserID();

if ($_POST['observer_option'] && !$ticket->isUser(CommonITILActor::OBSERVER, $user_id)) { // S'ajouter en tant qu'observateur

   $input = array(
         'id' => $ticket_id,
         '_itil_observer' => array(
               '_type' => 'user',
               'users_id' => Session::getLoginUserID()
         )
   );

   $ticket->check($ticket_id, UPDATE);
   $ticket_inputs[] = $input;
}

if (!$_POST['observer_option'] && $ticket->isUser(CommonITILActor::OBSERVER, $user_id)) { // Se retirer en tant qu'observateur
   $found = $ticket_user->find("tickets_id = $ticket_id AND type = " . CommonITILActor::OBSERVER . " AND users_id=$user_id");

   foreach ( $found as $id => $tu ) {
      $ticket_user->check($id, DELETE);
      $ticket_user_delete[] = array(
            'id' => $id
      );
   }
}

// Ajout du suivi
$fup = new TicketFollowup();
if ($_POST['transfer_justification'] != '') {
   $fup_input = array(
         'content' => $config['justification_prefix'] . ($config['justification_prefix'] ? ' : \n' : '') . $_POST['transfer_justification'],
         'tickets_id' => $ticket_id,
         'requesttypes_id' => '1',
         'is_private' => '0'
   );
   $fup->check(-1, CREATE, $fup_input);
} else if ($config['force_justification']) {
   Session::addMessageAfterRedirect(__('You must give a transfer justification', 'tickettransfer'), false, ERROR);
   Html::back();
}

/*
 * ****************************************************
 * Modifications effectives
 * Les notifications sont désactivées de façon à grouper les modifications dans une unique notification
 * *******************************************************
 */

$save_mail = $CFG_GLPI["use_mailing"];
$CFG_GLPI["use_mailing"] = false;

foreach ( $ticket_inputs as $input ) {
   $ticket->update($input);
}
foreach ( $ticket_user_delete as $input ) {
   $ticket_user->delete($input);
}
foreach ( $ticket_group_delete as $input ) {
   $ticket_group->delete($input);
}
if ($_POST['transfer_justification'] != '') {
   $fup->add($fup_input);
}

$CFG_GLPI["use_mailing"] = $save_mail;

Event::log($ticket_id, "ticket", 4, "tracking",
      // TRANS: %s is the user login
      sprintf(__('%s transfers a ticket', 'tickettransfer'), $_SESSION["glpiname"]));

Session::addMessageAfterRedirect(__('Successful transfer', 'tickettransfer'), false, INFO);
unset($_SESSION['plugin']['tickettransfer']['savedPOST']);

// Gestion des notifications
$author = new User();
$author->getFromDB(Session::getLoginUserID());

$ticket->__tickettransfer = array(
      'author' => $author->getName(),
      'message' => $_POST['transfer_justification'],
      'groupchanged' => !$groupAlreadyAssign
);

if ($_POST['transfer_type'] == PluginTickettransferTickettab::TRANSFER_TYPE_ENTITY) {
   if ($config['notif_transfer'] === 'always' || $config['notif_transfer'] === 'ongroupchange' && !$groupAlreadyAssign) {
      NotificationEvent::raiseEvent('plugin_tickettransfer_transfer', $ticket);
   }
} else if ($_POST['transfer_type'] == PluginTickettransferTickettab::TRANSFER_TYPE_GROUP) {
   if ($config['notif_group'] === 'always' || $config['notif_group'] === 'onassinglost' && !in_array(Session::getLoginUserID(), $new_group_users)) {
      NotificationEvent::raiseEvent('plugin_tickettransfer_escalation', $ticket);
   }
}

// rediriger soit vers le ticket, soit vers le tableau des tickets si on a perdu la vision sur ce ticket en particulier.
if ($ticket->can($ticket_id, READ)) {
   Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=$ticket_id");
} else {
   Session::addMessageAfterRedirect(__('You have been redirected because you no longer have access to this ticket'), true, ERROR);
   Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.php");
}




























