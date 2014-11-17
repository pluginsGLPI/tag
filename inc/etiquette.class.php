<?php
class PluginTagEtiquette extends CommonDropdown {

   public static function getTypeName($nb=1) {
      return _n('Etiquette', 'Etiquettes', 'tag'); //_n('Header', 'Headers', $nb, 'formcreator');
   }
   
   public static function getTagName($id_etiquette) {
      $etiquette_obj = new self();
      $etiquette_obj->getFromDB($id_etiquette);
      return $etiquette_obj->fields['name'];
   }
   
   //public static function canUpdate() {
      //parent::canUpdate();
      
   //}

   public function showForm($ID, $options = array()) {
      if (!$this->isNewID($ID)) {
         $this->check($ID, 'r');
      } else {
         $this->check(-1, 'w');
      }
      $options['colspan'] = 2;
      $options['target']  = Toolbox::getItemTypeFormURL(__CLASS__);
      $this->showTabs($options);
      $this->showFormHeader($options);
      echo '<table class="tab_cadre_fixe">';

      echo "<tr class='line0'><td>" . __('Name') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "</tr>";

      echo "<tr class='line1'><td>" . __('Description') . "</td>";
      echo "<td>";
      echo "<textarea name='comment' id ='comment' cols='45' rows='2' >" . $this->fields['comment'] . "</textarea>";
      //Html::initEditorSystem('comment');
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }

   public static function install(Migration $migration) {
      $table = getTableForItemType(__CLASS__);
      if (!TableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int(11) NOT NULL auto_increment,
                     `entities_id` int(11) NOT NULL DEFAULT '0',
                     `is_recursive` tinyint(1) NOT NULL DEFAULT '1',
                     `name` varchar(255) NOT NULL DEFAULT '',
                     `comment` text collate utf8_unicode_ci,
                     PRIMARY KEY (`id`),
                     KEY `name` (`name`)
                     ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $GLOBALS['DB']->query($query) or die($GLOBALS['DB']->error());
      }
      
      return true;
      }

   public static function uninstall() {
      $query = "DROP TABLE IF EXISTS `" . getTableForItemType(__CLASS__) . "`";
      return $GLOBALS['DB']->query($query) or die($GLOBALS['DB']->error());
   }
   
   /**
    * Définition du nom de l'onglet
    **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
   
      //if ($item->getType() == 'ObjetDuCoeur') {
         return _n('Associated item', 'Associated items', 2);
      //}
      //return '';
   }
   
   /**
    * Définition du contenu de l'onglet
    **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
   
      //if ($item->getType() == 'ObjetDuCoeur') {
         $monplugin = new PluginTagEtiquetteItem();
         $ID = $item->getField('id');
         $monplugin->showForTag($item);
      //}
      return true;
   }
   
   function defineTabs($options=array()){
      
      $ong = array();
      //$ong[0]=_n('Associated item', 'Associated items', 2);
      
      //$this->addStandardTab('PluginTagEtiquetteitem', $ong, $options);
      $this->addStandardTab('PluginTagEtiquette', $ong, $options);
   
      return $ong;
   }
   
   public function cleanDBonPurge() {
      global $DB;
      
      $query = "DELETE FROM `glpi_plugin_tag_etiquetteitems`
                WHERE `plugin_tag_etiquettes_id`=".$this->fields['id'];
      $DB->query($query);
   }
   
   /**
    * @see CommonDBTM::getSpecificMassiveActions()
    **/
   function getSpecificMassiveActions($checkitem=NULL) {
       
      $isadmin = static::canUpdate();
      //$actions = parent::getSpecificMassiveActions($checkitem);
      $actions = CommonDBTM::getSpecificMassiveActions($checkitem);
       
      if ($isadmin) {
         //$actions['edit'] = _x('button', 'Edit'); //COOL
      }
      
      return $actions;
   }
   
   /**
    * Return the linked items (in computers_items)
    *
    * @return an array of linked items  like array('Computer' => array(1,2), 'Printer' => array(5,6))
    * @since version 0.84.4
    **/
   function getLinkedItems() {
      global $DB;
   
      $query = "SELECT `itemtype`, `items_id`
              FROM `glpi_computers_items`
              WHERE `computers_id` = '" . $this->fields['id']."'";
      $tab = array();
      foreach ($DB->request($query) as $data) {
         $tab[$data['itemtype']][$data['items_id']] = $data['items_id'];
      };
      return $tab;
   }
   
   /**
    * @see CommonDBTM::showSpecificMassiveActionsParameters()
    **/
   function showSpecificMassiveActionsParameters($input=array()) {
   
      switch ($input['action']) {
         /*
         case "install" :
            Software::dropdownSoftwareToInstall("softwareversions_id",
            $_SESSION["glpiactive_entity"], 1);
            echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                  __s('Install')."'>";
            return true;
   
         case "connect" :
            $ci = new Computer_Item();
            return $ci->showSpecificMassiveActionsParameters($input);
   */
         default :
            return parent::showSpecificMassiveActionsParameters($input);
      }
      return false;
   }
   
   
   /**
    * @see CommonDBTM::doSpecificMassiveActions()
    **/
   function doSpecificMassiveActions($input=array()) {
   
      $res = array('ok'      => 0,
            'ko'      => 0,
            'noright' => 0);
      switch ($input['action']) {
         /*
         case "connect" :
            $ci = new Computer_Item();
            return $ci->doSpecificMassiveActions($input);
   
         case "install" :
            if (isset($input['softwareversions_id']) && ($input['softwareversions_id'] > 0)) {
               $inst = new Computer_SoftwareVersion();
               foreach ($input['item'] as $key => $val) {
                  if ($val == 1) {
                     $input2 = array('computers_id'        => $key,
                           'softwareversions_id' => $input['softwareversions_id']);
                     if ($inst->can(-1, 'w', $input2)) {
                        if ($inst->add($input2)) {
                           $res['ok']++;
                        } else {
                           $res['ko']++;
                        }
                     } else {
                        $res['noright']++;
                     }
                  }
               }
            } else {
               $res['ko']++;
            }
            break;
   */
         default :
            return parent::doSpecificMassiveActions($input);
      }
      return $res;
   }
   
   
   function getSearchOptions() {
      global $CFG_GLPI;
   
      $tab                       = array();
      $tab['common']             = __('Characteristics');
   
      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['massiveaction']   = true; // implicit key==1
      $tab[1]['datatype']        = 'itemlink';
      
      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'comment';
      $tab[2]['name']            = __('Description');
      $tab[2]['massiveaction']   = true; // implicit field is id
      $tab[2]['datatype']        = 'string';
      return $tab;
   }
}
