<?php
include ('../../../inc/includes.php');

class PluginTagEtiquetteItem extends CommonDBTM {
   
   static function canCreate() {
      return Session::haveRight('config', 'w');
   }
   
   static function canView() {
      return Session::haveRight('config', 'r');
   }
   
   public static function getTypeName($nb=1) {
      return _n('Etiquette', 'Etiquettes', 'tag'); //_n('Header', 'Headers', $nb, 'formcreator');
   }
   
   public static function getValue($id, $itemtype) {
      $names = array();
      $etiquette_items = PluginTagEtiquetteItem::getEtiquette_items($id, $itemtype);
      foreach ($etiquette_items as $key => $id_etiquette) {
         $names[] = PluginTagEtiquette::getTagName($id_etiquette);
      }
      return implode(", ", $names);   
   }
   
   public static function install(Migration $migration) {
      $table = getTableForItemType(__CLASS__);
      if (!TableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
            	`id` INT(11) NOT NULL AUTO_INCREMENT,
            	`plugin_tag_etiquettes_id` INT(11) NOT NULL DEFAULT '0',
            	`items_id` TINYINT(1) NOT NULL DEFAULT '1',
            	`itemtype` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            	`comment` TEXT NULL COLLATE 'utf8_unicode_ci',
            	`last_edited` DATETIME NULL DEFAULT NULL,
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
      /*
      while (list($etiquette_item) = $DB->fetch_array($result)) {
         $etiquette_items[] = $etiquette_item;
      }
   
      return $etiquette_items;*/
   }
   
   public function prepareInputForAdd($input) {
   
      $tab_tag = explode(',', $this->input['_plugin_tag_etiquette_value']);
   
      //TODO : Coder l'enregistrement de $tag_str
      foreach ($tab_tag as $tag_str) {
         $tag_str = trim($tag_str);
         //Si le tag n'existe pas, le crée, sinon récupérer l'id du tag.
         $etiquette = new PluginTagEtiquette();
         $etiquettes = $etiquette->find("itemtype=".$this->input['itemtype']."
               AND id=".$this->input['id']." AND name='");
          
         switch (count($etiquettes)) {
            case 0:
               //Création du tag :
               //$etiquette->sav
            default :
               //$obj->getFromDB($etiquettes[0]['id']);
               //$obj->save($this, $datas);
         }
          
      }
      return $input;
   }
   
   function plugin_tag_getAddSearchOptions($itemtype) {
      global $db;
      
      //https://mail.gna.org/public/glpi-dev/2012-03/msg00017.html
      //Jointure entre les deux tables
      $query = "SELECT * FROM glpi_plugin_tag_etiquette"; //glpi_plugin_tag_etiquetteitem
      $db->query($query);
      
      $sopt = array();
      
      $i = 0;
      $sopt[$i+2000]['table']       = ''; //plugin_tag_table($itemtype);
      $sopt[$i+2000]['field']       = $search['system_name'];
      $sopt[$i+2000]['linkfield']   = $search['system_name'];
      $sopt[$i+2000]['name']        = $search['label'];
      $sopt[$i+2000]['nosearch']    = true;
      $sopt[$i+2000]['nosort']      = true;
      
      /*
      // Part header
      $sopt[PLUGIN_EXAMPLE_TYPE]['common']="Header Needed";
      
      
      $sopt[PLUGIN_EXAMPLE_TYPE][1]['table']='glpi_plugin_example';
      $sopt[PLUGIN_EXAMPLE_TYPE][1]['field']='name';
      $sopt[PLUGIN_EXAMPLE_TYPE][1]['linkfield']='name';
      $sopt[PLUGIN_EXAMPLE_TYPE][1]['name']=$LANGEXAMPLE["name"];
      
      $sopt[PLUGIN_EXAMPLE_TYPE][2]['table']='glpi_dropdown_plugin_example';
      $sopt[PLUGIN_EXAMPLE_TYPE][2]['field']='name';
      $sopt[PLUGIN_EXAMPLE_TYPE][2]['linkfield']='FK_dropdown';
      $sopt[PLUGIN_EXAMPLE_TYPE][2]['name']='Dropdown';
      */
   }

}