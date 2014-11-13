<?php
class PluginTagEtiquetteItem extends CommonDBRelation {
   
   // From CommonDBRelation
   static public $itemtype_1    = 'PluginTagEtiquette';
   static public $items_id_1    = 'plugin_tag_etiquettes_id';
   static public $take_entity_1 = true;
   
   static public $itemtype_2    = 'itemtype';
   static public $items_id_2    = 'items_id';
   static public $take_entity_2 = false;
    
   
   public static function getTypeName($nb=1) {
      return _n('Etiquette', 'Etiquettes', 'tag'); //_n('Header', 'Headers', $nb, 'formcreator');
   }
   
   /* //Old :
   public static function getValue($id, $itemtype) {
      $names = array();
      $etiquette_items = PluginTagEtiquetteItem::getEtiquette_items($id, $itemtype);
      foreach ($etiquette_items as $key => $id_etiquette) {
         $names[] = PluginTagEtiquette::getTagName($id_etiquette);
      }
      return implode(", ", $names);   
   }
   */
   
   public static function install(Migration $migration) {
      $table = getTableForItemType(__CLASS__);
      if (!TableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
            	`id` INT(11) NOT NULL AUTO_INCREMENT,
            	`plugin_tag_etiquettes_id` INT(11) NOT NULL DEFAULT '0',
            	`items_id` TINYINT(1) NOT NULL DEFAULT '1',
            	`itemtype` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            	`comment` TEXT NULL COLLATE 'utf8_unicode_ci',
            	PRIMARY KEY (`id`),
            	INDEX `name` (`itemtype`)
            )
            COLLATE='utf8_unicode_ci'
            ENGINE=MyISAM";
         $GLOBALS['DB']->query($query) or die($GLOBALS['DB']->error());
      }
      return true;
   }
   
   public static function uninstall() {
      $query = "DROP TABLE IF EXISTS `" . getTableForItemType(__CLASS__) . "`";
      return $GLOBALS['DB']->query($query) or die($GLOBALS['DB']->error());
   }
   
   static function getEtiquette_items($id_glpi_obj, $itemtype) {
      global $DB;
      $etiquette_items = array();
      $query = "SELECT *
               FROM `glpi_plugin_tag_etiquetteitems`
               WHERE itemtype='$itemtype' AND items_id=$id_glpi_obj";
   
      $result = $DB->query($query);
      
      $IDs = array();
      if ($DB->numrows($result) > 0) {
         while($datas = $DB->fetch_assoc($result)) {
            $IDs[] = $datas["plugin_tag_etiquettes_id"];
         }
      }
      return $IDs;
   }
   /*
   public function prepareInputForAdd($input) {
      return $input;
   }*/

}