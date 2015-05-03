<?php

include ("../../../inc/includes.php");

Session::checkLoginUser();

if(!(
		isset($_POST['id']) and
		isset($_POST['entities_id']) and
		isset($_POST['type']) and
		isset($_POST['itilcategories_id']) and
		isset($_POST['attribution_options']) and
		isset($_POST['transfer_justification'])
		)) {
	Session::addMessageAfterRedirect("Something wrong just happend. Did you try to open this form in an unorthodox way?</br>
			If you think you didn't do anything wrong, please send a bug report", false, ERROR);
	Html::back();
}

// check right to use the plugin
if(!$_SESSION ['plugin']['tickettransfer']['profileconfig']['transfer_toentity']) {
	Html::displayRightError();
}

// save data in case of failure
$_SESSION['plugin']['tickettransfer']['savedPOST'] = $_POST;


$ticket_id = $_POST['id'];
$user_id = Session::getLoginUserID();

$ticket = new Ticket();
$ticket->getFromDB($ticket_id);
$ticket_inputs = array();
$ticket_user_delete = array();

// Méthode générale : on fait le bilan des modifs demandées en checkant si l'utilisateur à les droits au fur et à mesure, mais sans faire les modifs
// Si on arrive au bout des vérif (ie l'utilsiateur a tous les droits nécessaires), on execute toutes les actions demandées.
// De cette façon, on évite de rester bloqué alors qu'on a déjà modifié des choses


// Gestion du transfert
if($_POST['entities_id'] != $ticket->getField('entities_id') or 
		$_POST['type'] != $ticket->getField('type') or 
		$_POST['itilcategories_id'] != $ticket->getField('itilcategories_id')) {
	$input = array(
			'id' => $ticket_id,
			'entities_id' => $_POST['entities_id'],
			'type' => $_POST['type'],
			'itilcategories_id'=>$_POST['itilcategories_id']
		);
	
	$ticket->check($ticket_id, 'w', $input);
	$ticket_inputs[] = $input;
} else {
	// Si pas de transfert, on refuse de faire le reste, même s'il y a un reste.
	Session::addMessageAfterRedirect( __("You must change the ticket location to do a transfer (at least one in entity, type or category)", 'tickettransfer'), false, ERROR);
	Html::back();
}

// Gestion de la réattribution
$is_assigned = $ticket->isUser(CommonITILActor::ASSIGN, $user_id);
$is_observer = $ticket->isUser(CommonITILActor::OBSERVER, $user_id) || $ticket->haveAGroup(CommonITILActor::OBSERVER, $_SESSION["glpigroups"]);
$ticket_user = new Ticket_User();

if(($_POST['attribution_options'] == 'assign_only' or $_POST['attribution_options']=='none') and $is_observer) {
	// Se retirer en tant qu'observateur
	$found = $ticket_user->find("tickets_id = $ticket_id AND type = ".CommonITILActor::OBSERVER." AND users_id=$user_id");
	
	foreach ($found as $id => $tu) {
		$ticket_user->check($id, 'd');
		$ticket_user_delete[] = array(
			'id' => $id
		);
	}
}

if(($_POST['attribution_options'] == 'observer_only' or $_POST['attribution_options']=='none') and $is_assigned) {
	// Se retirer en tant qu'assign
	$found = $ticket_user->find("tickets_id = $ticket_id AND type = ".CommonITILActor::ASSIGN." AND users_id=$user_id");
	
	foreach ($found as $id => $tu) {
		$ticket_user->check($id, 'd');
		$ticket_user_delete[] = array(
				'id' => $id
		);
	}
}

if(($_POST['attribution_options'] == 'assign_only' or $_POST['attribution_options']=='assign_and_observer') and !$is_assigned) {
	// s'ajouter en tant qu'assign
	$input = array(
			'id' => $ticket_id,
			'_itil_assign' => array(
					'_type' => 'user',
					'users_id' => Session::getLoginUserID()
			));
	
	$ticket->check($ticket_id, 'w', $input);
	$ticket_inputs[] = $input;
}

if(($_POST['attribution_options'] == 'observer_only' or $_POST['attribution_options']=='assign_and_observer') and !$is_observer) {
	// s'ajouter en tant qu'observateur
	$input = array(
			'id' => $ticket_id,
			'_itil_observer' => array(
					'_type' => 'user',
					'users_id' => Session::getLoginUserID()
			));
	
	$ticket->check($ticket_id, 'w', $input);
	$ticket_inputs[] = $input;
}

// Ajout du suivi
$fup = new TicketFollowup();
if ($_POST['transfer_justification']!='') {
	
	$fup_input = array ( 'content' => __('Transfer explanation / justification', 'tickettransfer')." : \n".$_POST['transfer_justification'],
			'tickets_id' => $ticket_id,
			'requesttypes_id' => '1',
			'is_private' => '0'
	);
	$fup->check ( - 1, 'w', $fup_input );
}


foreach ($ticket_user_delete as $input) {
	$ticket_user->delete($input);
	Event::log($ticket_id, "ticket", 4, "tracking",
			//TRANS: %s is the user login
			sprintf(__('%s deletes an actor'), $_SESSION["glpiname"]));
}

foreach ($ticket_inputs as $input) {
	$ticket->update($input);
	Event::log($ticket_id, "ticket", 4, "tracking",
			//TRANS: %s is the user login
			sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
}

if($_POST['transfer_justification']!='') {
	$fup->add ( $fup_input );
	Event::log($ticket_id, "ticket", 4, "tracking",
			//TRANS: %s is the user login
			sprintf(__('%s adds a followup'), $_SESSION["glpiname"]));
}

Session::addMessageAfterRedirect( __("Successful transfer", 'transferticket'), false, INFO);

unset($_SESSION['plugin']['tickettransfer']['savedPOST']);

Html::back();





