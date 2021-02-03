<?php
if(! defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginTickettransferNotification {
   /**
    * Inscrit les évenements de notification
    * @param NotificationTargetTicket $target           
    */
   static function addEvents(NotificationTargetTicket $target) {
      $target->events['plugin_tickettransfer_requalification'] = __('Ticket requalification', 'tickettransfer');
      $target->events['plugin_tickettransfer_escalation'] = __('Ticket escalation', 'tickettransfer');
   }

   /**
    * Prépare les données pour le texte de la notification
    * @param NotificationTargetTicket $target           
    */
   static function getDatas(NotificationTargetTicket $target) {
      if ($target->raiseevent === 'plugin_tickettransfer_requalification' || $target->raiseevent === 'plugin_tickettransfer_escalation') {
         $author = new User();
         if($author->getFromDB($target->obj->__tickettransfer['author'])) {
            $target->datas['##ticket.tickettransfer.author##'] = $author->getName();
         }

         $target->datas['##ticket.tickettransfer.message##'] = $target->obj->__tickettransfer['message'];
         $target->datas['##ticket.tickettransfer.groupchanged##'] = $target->obj->__tickettransfer['groupchanged'];
      }
   }
}
