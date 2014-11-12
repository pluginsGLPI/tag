<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginTagTag extends Ticket {

   static function canCreate() {
      return true;
   }

   static function canView() {
      return true;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      //TODO : Translate
      return __('Références externes', 'iframe');
   }

   static function plugin_iframe_item_add(Ticket $ticket) {
      global $DB;
      $ticketid = $ticket->getField('id');
      $update = $DB->query("INSERT INTO glpi_plugin_iframe_tickets VALUES " . $ticketid);
   }

   static function getAllConfig() {
      return array("BlueMind" => "https://forge.blue-mind.net/jira/browse/",
            "Redmine" => "http://fr.wikipedia.org/",
            'Fusion' => "http://example.com/",
            'Github' => 'http://github.com/');
   }

   static function form($external_ref, $id_ticket) {
      global $CFG_GLPI;

      echo "<form action='".$CFG_GLPI['root_doc']."/plugins/iframe/front/ticket.php' method='POST'>";

      echo __("Référence externe", 'iframe'). " : ";
      Dropdown::showFromArray("type", self::getAllConfigName(), array("value" => 2));
      echo " <input type='text' name='external_type' value='".$external_ref['external_type']."' />";
      echo " <input type='hidden' name='external_txt' value='".$external_ref['external_type']."' />";

      $str = "add"; //"update";
      echo ' <input type="submit" class="submit" name="'.$str.'" value="'.__('Save').'">';
      Html::closeForm();
   }
   
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI, $DB;
       
      $id_ticket = $item->getField('id');
       
      if ($item->getType() == 'Ticket') {
         echo "<div style='text-align:left;'>";
          
         $ticket = new self();
         $data = $ticket->find("ticket_id = 2");
         print_r($data);
          
         if (count($data) == 0) {
            __('Aucune référence externe', 'iframe');
         }
          
         //Tickets can have many externals references :
         foreach ($data as $external_ref) {
            self::form($external_ref, $id_ticket);
            //$config = self::getAllConfig();
            //self::iframe($config['BlueMind'], $refeditor);
            echo "<br><hr><br>";
         }
          
         echo "</div>";
      }
      return true;
   }
}