<?php
class PluginFormcreatorQuestion extends CommonDBChild

   static public $itemtype = "PluginFormcreatorSection";
   static public $items_id = "plugin_formcreator_sections_id";

if (Ticket::isAllowedStatus(Ticket::SOLVED, Ticket::CLOSED)) {
   Ticket::showCentralList(0, "toapprove", false);
}

if ($this->input["status"] == self::CLOSED) {
   return;
}

   function add($input) {
      global $db;
      //(champs cachés ?)
      $id = 1; //$this->input["id"]
      $itemtype = 'Ticket'; //$this->input["itemtype"]
      /* Solution temporaire : Ajout via SQL */
      //TODO
      $query = ''; //$id, $itemtype
      $db->query($query);
      /* Solution définitive */
      //$id, $itemtype
   }

   function prepareInputForAdd($input) {
      $input = parent::prepareInputForAdd($input);
      // Vérification :
      $ticket = new Ticket();
      //$ticket->getFromDB($this->input['_tickets_id']);
      if (true) {
         add($input);
      }
      return $input;
   }
