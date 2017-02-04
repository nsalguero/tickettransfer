<?php

include ("../../../inc/includes.php");
Session::checkLoginUser();

if(!isset($_GET['tickets_id']) || !is_numeric($_GET['tickets_id'])) {
    Session::addMessageAfterRedirect(__('Wrong parameter', 'tickettransfer'),
            true, ERROR);
    Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.php");
} else if(!isset($_GET['groups_id']) || !is_numeric($_GET['groups_id'])) {
    Session::addMessageAfterRedirect(__('Wrong parameter', 'tickettransfer'),
            true, ERROR);
    Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=$ticket_id");
} else {
    // Set default values for Ticket transfer tab => pre-set for clicked escalation
    $_SESSION['plugin']['tickettransfer']['savedPOST'] = array(
            'transfer_type' => PluginTickettransferTickettab::TRANSFER_TYPE_GROUP,
            'groups_id_assign' => $_GET['groups_id'],
    );

    $href = $CFG_GLPI["root_doc"]."/front/ticket.form.php?id=$_GET[tickets_id]&forcetab=PluginTickettransferTickettab%241";
    if (!isset($_REQUEST['full_history'])) {
        // if clicked from the ticket page
        Html::redirect($href);
    }
    else {
        // if clicked from the full history popup page
        // redirect parent window and close popup
        echo "<script type='text/javascript'>
            if (window.opener && !window.opener.closed) {
               window.opener.location.href='$href';
            }
            window.close();
            </script>";
    }



}


