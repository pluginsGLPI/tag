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
      echo "<textarea name='comment' id ='comment' >" . $this->fields['comment'] . "</textarea>";
      Html::initEditorSystem('comment');
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
         $monplugin = new self();
         $ID = $item->getField('id');
         $monplugin->showForTag($item);
      //}
      return true;
   }
   
   static function showForTag(PluginTagEtiquette $etiquette) {
      global $DB, $CFG_GLPI;
   
      $instID = $etiquette->fields['id'];
      if (!$etiquette->can($instID,"r")) {
         return false;
      }
      
      $canedit = $etiquette->can($instID,'w');
      
      $itemtypes = getItemtypes();
      
      foreach ($itemtypes as $key => $itemtype) {
         if ($itemtype == 'Notes') {
            $itemtype = 'Reminder';
         }
         $obj = new $itemtype();
         if (! $obj->canUpdate()) {
            unset($itemtypes[$key]);
         }
      }
   
      $result = $DB->query("SELECT DISTINCT `itemtype`
         FROM `glpi_plugin_tag_etiquetteitems`
         WHERE `glpi_plugin_tag_etiquetteitems`.`plugin_tag_etiquettes_id` = '$instID'
         ORDER BY `itemtype`");
      $number = $DB->numrows($result);
      $rand   = mt_rand();
   
      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='tagitem_form$rand' id='tagitem_form$rand' method='post'
         action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add an item')."</th></tr>";
         
         echo "<tr class='tab_bg_1'><td class='right'>";
                  Dropdown::showAllItems("items_id", 0, 0,
                  ($etiquette->fields['is_recursive']?-1:$etiquette->fields['entities_id']),
                        $itemtypes, false, true);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "<input type='hidden' name='plugin_tag_etiquettes_id' value='$instID'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }
      
      //TODO : Implement 'save' of massive actions and right verification
      $canedit = false;
   
      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array();
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";

      if ($canedit && $number) {
         echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
      }
   
      echo "<th>".__('Type')."</th>";
      echo "<th>".__('Name')."</th>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>".__('Serial number')."</th>";
      echo "<th>".__('Inventory number')."</th>";
      echo "</tr>";
      
      for ($i=0 ; $i < $number ; $i++) {
         $itemtype=$DB->result($result, $i, "itemtype");
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
   
         if ($item->canView()) {
            $column = "name";
            //if ($itemtype == 'ticket') {
            //$column = "id";
            //}
            
            $itemtable = getTableForItemType($itemtype);
            $query     = "SELECT `$itemtable`.*, `glpi_plugin_tag_etiquetteitems`.`id` AS IDD, ";
      
            if ($itemtype == 'knowbaseitem') {
               $query .= "-1 AS entity
                  FROM `glpi_plugin_tag_etiquetteitems`, `$itemtable`
                  ".KnowbaseItem::addVisibilityJoins()."
                  WHERE `$itemtable`.`id` = `glpi_plugin_tag_etiquetteitems`.`items_id`
                  AND ";
            } else {
               $query .= "`glpi_entities`.`id` AS entity
                  FROM `glpi_plugin_tag_etiquetteitems`, `$itemtable`
                  LEFT JOIN `glpi_entities`
                  ON (`glpi_entities`.`id` = `$itemtable`.`entities_id`)
                  WHERE `$itemtable`.`id` = `glpi_plugin_tag_etiquetteitems`.`items_id`
                  AND ";
            }
            $query .= "`glpi_plugin_tag_etiquetteitems`.`itemtype` = '$itemtype'
               AND `glpi_plugin_tag_etiquetteitems`.`plugin_tag_etiquettes_id` = '$instID' ";
   
            if ($itemtype =='knowbaseitem') {
               if (Session::getLoginUserID()) {
                  $where = "AND ".KnowbaseItem::addVisibilityRestrict();
                  } else {
                     // Anonymous access
                     if (Session::isMultiEntitiesMode()) {
                        $where = " AND (`glpi_entities_knowbaseitems`.`entities_id` = '0'
                        AND `glpi_entities_knowbaseitems`.`is_recursive` = '1')";
                     }
                  }
               } else {
                  $query .= getEntitiesRestrictRequest(" AND ", $itemtable, '', '', $item->maybeRecursive());
               }
   
               if ($item->maybeTemplate()) {
                  $query .= " AND `$itemtable`.`is_template` = '0'";
               }
   
               if ($itemtype == 'knowbaseitem') {
                  $query .= " ORDER BY `$itemtable`.`$column`";
               } else {
                  $query .= " ORDER BY `glpi_entities`.`completename`, `$itemtable`.`$column`";
               }
   
               if ($itemtype == 'softwarelicense') {
                  $soft = new Software();
               }
      
               if ($result_linked = $DB->query($query)) {
                  if ($DB->numrows($result_linked)) {
      
                     while ($data = $DB->fetch_assoc($result_linked)) {
                  
                     //if ($itemtype == 'ticket') {
                     //$data["name"] = sprintf(__('%1$s: %2$s'), __('Ticket'), $data["id"]);
                     //}
      
                     if ($itemtype == 'softwarelicense') {
                        $soft->getFromDB($data['softwares_id']);
                        $data["name"] = sprintf(__('%1$s - %2$s'), $data["name"],
                              $soft->fields['name']);
                     }
                     $linkname = $data["name"];
                     if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                        $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $data["id"]);
                     }
      
                     $link = Toolbox::getItemTypeFormURL($itemtype);
                     $name = "<a href=\"".$link."?id=".$data["id"]."\">".$linkname."</a>";
      
                     echo "<tr class='tab_bg_1'>";
      
                     if ($canedit) {
                        echo "<td width='10'>";
                        Html::showMassiveActionCheckBox(__CLASS__, $data["IDD"]);
                        echo "</td>";
                     }
                     echo "<td class='center'>".$item->getTypeName(1)."</td>";
                     echo "<td ".
                     (isset($data['is_deleted']) && $data['is_deleted']?"class='tab_bg_2_2'":"").
                     ">".$name."</td>";
                     echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities", $data['entity']);
                     echo "</td>";
                     echo "<td class='center'>".
                            (isset($data["serial"])? "".$data["serial"]."" :"-")."</td>";
                     echo "<td class='center'>".
                            (isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
                     echo "</tr>";
                     }
                  }
               }
            }
         }
         echo "</table>";
         if ($canedit && $number) {
            $massiveactionparams['ontop'] =false;
            Html::showMassiveActions(__CLASS__, $massiveactionparams);
            Html::closeForm();
         }
         echo "</div>";
   
      }
   
   function defineTabs($options=array()){
      
      $ong = array();
      //$ong[0]=_n('Associated item', 'Associated items', 2);
      
      //$this->addStandardTab('PluginTagEtiquetteitem', $ong, $options);
      $this->addStandardTab('PluginTagEtiquette', $ong, $options);
   
      return $ong;
   }
   
   /*
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
   
      echo "displayTabContentForItem()"; //DEBUG
      if ($item->getType() == 'Profile') {
         $prof = new self();
         $ID = $item->getField('id');
         // j'affiche le formulaire
         $prof->showForm($ID);
      }
      return true;
   }*/
   
   public function cleanDBonPurge() {
      global $DB;
      
      $query = "DELETE FROM `glpi_plugin_tag_etiquetteitems`
                WHERE `plugin_tag_etiquettes_id`=".$this->fields['id'];
      $DB->query($query);
   }
}
