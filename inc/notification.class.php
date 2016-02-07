<?php
if(! defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}
class PluginTickettransferNotification {
	
	// static function getDatasForTemplate($target) {
	// global $CFG_GLPI; // var_dump($target->obj); if(get_class($target->obj) == 'Ticket' and ($id =
	// $target->obj->getField('id'))) { $baseStr = $CFG_GLPI["url_base"]."/index.php".
	// "?redirect=plugin_smartredirect_ticket_".$id;
	// $target->datas['##ticket.smartredirect.url##'] = urldecode($baseStr);
	// $target->datas['##ticket.smartredirect.urlapprove##'] = urldecode($baseStr . "_Ticket$2");
	// $target->datas['##ticket.smartredirect.urlvalidation##'] = urldecode($baseStr . "_TicketValidation$1");
	// $target->datas['##ticket.smartredirect.urldocument##'] = urldecode($baseStr . "_DocumentItem$1");
	// }
	// }
	
	/**
	 * Inscrit les évenements de notification.class
	 *
	 * @param NotificationTargetTicket $target        	
	 */
	static function addEvents(NotificationTargetTicket $target) {
		// TODO check if the loadlang is needed
		// Plugin::loadLang('tickettransfer');
		$target->events['plugin_tickettransfer_transfer'] = __("Ticket requalification", 'tickettransfer');
		$target->events['plugin_tickettransfer_escalation'] = __("Ticket escalation", 'tickettransfer');
	}

	/**
	 * Prépare les datas pour la notification.class
	 *
	 * @param NotificationTargetTicket $target        	
	 */
	static function getDatas(NotificationTargetTicket $target) {
		if($target->raiseevent === 'plugin_tickettransfer_transfer' ||
				 $target->raiseevent === 'plugin_tickettransfer_escalation') {
			$target->datas['##ticket.tickettransfer.author##'] = $target->obj->__tickettransfer['author'];
			$target->datas['##ticket.tickettransfer.message##'] = $target->obj->__tickettransfer['message'];
			$target->datas['##ticket.tickettransfer.groupchanged##'] = $target->obj->__tickettransfer['groupchanged'];
		}
	}
}