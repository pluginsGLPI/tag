<?php
class PluginTagTagItem extends CommonDBRelation {
   
   // From CommonDBRelation
   static public $itemtype_1    = 'PluginTagTag';
   static public $items_id_1    = 'plugin_tag_tags_id';
   static public $take_entity_1 = true;
   
   static public $itemtype_2    = 'itemtype';
   static public $items_id_2    = 'items_id';
   static public $take_entity_2 = false;
    
   
   public static function getTypeName($nb=1) {
      return _n('Tag', 'Tags', $nb, 'tag');
   }
   
   public static function install(Migration $migration) {
      $table = getTableForItemType(__CLASS__);
      if (!TableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
            	`id` INT(11) NOT NULL AUTO_INCREMENT,
            	`plugin_tag_tags_id` INT(11) NOT NULL DEFAULT '0',
            	`items_id` INT(11) NOT NULL DEFAULT '1',
            	`itemtype` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
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
   
   static function getTag_items($id_glpi_obj, $itemtype) {
      $IDs = array(); //init
      
      $tagitems = new self();
      foreach ($tagitems->find("itemtype = '$itemtype' AND items_id = $id_glpi_obj") as $tagitem) {
         $IDs[] = $tagitem["plugin_tag_tags_id"];
      }
      
      return $IDs;
   }
   
   /**
    * @see CommonDBTM::doSpecificMassiveActions()
    **/
   function doSpecificMassiveActions($input=array()) {
      $res = array('ok'      => 0,
                  'ko'      => 0,
                  'noright' => 0);
      switch ($input['action']) {
         default :
            return parent::doSpecificMassiveActions($input);
      }
      return $res;
   }
   
   function getAllMassiveActions($is_deleted=0, $checkitem=NULL) {
      if ($this->maybeDeleted()
            && !$this->useDeletedToLockIfDynamic()) {
               $actions['delete'] = _x('button', 'Put in dustbin');
      } else {
         $actions['purge'] = _x('button', 'Delete permanently');
      }
      
      return $actions;
   }
   
   function getSpecificMassiveActions($checkitem=NULL) {
      return array();
   }
   
   function getSearchOptions() {
      $tab                       = array();
      
      $tab['common']             = __('Characteristics');
      return $tab;
      /*
      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['massiveaction']   = true;
      $tab[1]['datatype']        = 'itemlink';
   
      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'comment';
      $tab[2]['name']            = __('Description');
      $tab[2]['massiveaction']   = true;
      $tab[2]['datatype']        = 'string';
      return $tab;
      */
   }

   static function getItemtypes() {
      return array('Computer', 'Monitor', 'Software', 'Peripheral', 'Printer', 'SLA', 'Link', 
                  'Cartridgeitem', 'Consumableitem', 'Phone', 'Ticket', 'Problem', 'TicketRecurrent', 
                  'Budget', 'Supplier', 'Contact', 'Contract', 'Document', 'Reminder', 'RSSFeed', 'User',
                  'Group', 'Profile', 'Location', 'ITILCategory', 'NetworkEquipment', ); //, 'KnowbaseItem'
   }
   
   /**
    * 
    * Note : can separe code of view list
    * @param PluginTagTag $tag
    * @return boolean
    */
   static function showForTag(PluginTagTag $tag) {
      global $DB;
   
      $instID = $tag->fields['id'];
      if (!$tag->can($instID, READ)) {
         return false;
      }
      
      $canedit = $tag->can($instID, UPDATE);
      
      $itemtypes = self::getItemtypes();
      
      foreach ($itemtypes as $key => $itemtype) {
         $obj = new $itemtype();
         if (! $obj->canUpdate()) {
            unset($itemtypes[$key]);
         }
      }
   
      $table = getTableForItemType(__CLASS__);
      $result = $DB->query("SELECT DISTINCT `itemtype`
         FROM `$table`
         WHERE `plugin_tag_tags_id` = '$instID'");
      $result2 = $DB->query("SELECT `itemtype`, items_id
            FROM `$table`
            WHERE `plugin_tag_tags_id` = '$instID'");
      $number = $DB->numrows($result);
      $rand   = mt_rand();
   
      if ($canedit) {
         echo "<div class='firstbloc'>";
         //can use standart GLPI function
         echo "<form name='tagitem_form$rand' id='tagitem_form$rand' method='post'
         action='".Toolbox::getItemTypeFormURL('PluginTagTag')."'>";
         
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add an item')."</th></tr>";
         
         echo "<tr class='tab_bg_1'><td class='right'>";
         Dropdown::showAllItems("items_id", 0, 0,
            ($tag->fields['is_recursive'] ? -1 : $tag->fields['entities_id']),
            $itemtypes, false, true
         );
         echo "<style>.select2-container { text-align: left; } </style>"; //minor
         echo "</td><td class='center'>";
         echo "<input type='hidden' name='plugin_tag_tags_id' value='$instID'>";
         //Note : can use standart GLPI method
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }
   
      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         Html::showMassiveActions();
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";

      if ($canedit && $number) {
         echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand) . "</th>";
      }
   
      echo  "<th>" . __('Type') . "</th>";
      echo  "<th>" . __('Name') . "</th>";
      echo  "<th>" . __('Entity') . "</th>";
      echo  "<th>" . __('Serial number') . "</th>";
      echo  "<th>" . __('Inventory number') . "</th>";
      echo "</tr>";
      
      for ($i=0; $i < $number; $i++) {
         $itemtype=$DB->result($result, $i, "itemtype");
         $item_id =$DB->result($result2, $i, "items_id");
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         
         if ($item->canView()) {
            $column = (strtolower(substr($itemtype, 0, 6)) == "device") ? "designation" : "name";
            
            // For rules itemtypes (example : ruledictionnaryphonemodel)
            if (strtolower(substr($itemtype, 0, 4)) == 'rule' || $itemtype == "PluginResourcesRulechecklist") {
               $itemtable = getTableForItemType('Rule');
            } else {
               $itemtable = getTableForItemType($itemtype);
            }
            $query = "SELECT `$itemtable`.*, `glpi_plugin_tag_tagitems`.`id` AS IDD, ";
            
            $obj = new $itemtype();
            $obj->getFromDB($item_id);
            
            switch ($itemtype) {
               case 'KnowbaseItem':
               $query .= "-1 AS entity
                  FROM `glpi_plugin_tag_tagitems`, `$itemtable`
                  ".KnowbaseItem::addVisibilityJoins()."
                  WHERE `$itemtable`.`id` = `glpi_plugin_tag_tagitems`.`items_id`
                  AND ";
               /*
               if (Session::getLoginUserID()) {
                  $where = "AND ".KnowbaseItem::addVisibilityRestrict();
               } elseif (Session::isMultiEntitiesMode()) { // Anonymous access
                  $where = " AND (`glpi_entities_knowbaseitems`.`entities_id` = '0'
                              AND `glpi_entities_knowbaseitems`.`is_recursive` = '1')";
               }
               */
                  break;
               case 'Profile':
               case 'RSSFeed':
               case 'Reminder':
               case 'Entity':
                  //Possible to add (in code) condition to visibility :
                  $query .= "-1 AS entity
                  FROM `glpi_plugin_tag_tagitems`, `$itemtable`
                  WHERE `$itemtable`.`id` = `glpi_plugin_tag_tagitems`.`items_id`
                  AND ";
                  break;
               default:
                  if (isset($obj->fields['entities_id'])) {
                     $query .= "`glpi_entities`.`id` AS entity
                        FROM `glpi_plugin_tag_tagitems`, `$itemtable`
                        LEFT JOIN `glpi_entities`
                        ON (`glpi_entities`.`id` = `$itemtable`.`entities_id`)
                        WHERE `$itemtable`.`id` = `glpi_plugin_tag_tagitems`.`items_id`
                        AND ";
                  } else {
                     $query .= "-1 AS entity
                        FROM `glpi_plugin_tag_tagitems`, `$itemtable`
                        WHERE `$itemtable`.`id` = `glpi_plugin_tag_tagitems`.`items_id`
                        AND ";
                  }
                  break;
            }
            
            $query .= "`glpi_plugin_tag_tagitems`.`itemtype` = '$itemtype'
               AND `glpi_plugin_tag_tagitems`.`plugin_tag_tags_id` = '$instID' ";
   
            $query .= getEntitiesRestrictRequest(" AND ", $itemtable, '', '', $item->maybeRecursive());
   
            if ($item->maybeTemplate()) {
               $query .= " AND `$itemtable`.`is_template` = '0'";
            }
   
            switch ($itemtype) {
               case 'KnowbaseItem':
               case 'Profile':
               case 'RSSFeed':
               case 'Reminder':
               case 'Entity':
                  $query .= " ORDER BY `$itemtable`.`$column`";
                  break;
               default:
                  if (isset($obj->fields['entities_id'])) {
                     $query .= " ORDER BY `glpi_entities`.`completename`, `$itemtable`.`$column`";
                  } else {
                     $query .= " ORDER BY `$itemtable`.`$column`";
                  }
                  break;
            }
            
            if ($result_linked = $DB->query($query)) {
               if ($DB->numrows($result_linked)) {
                  
                  while ($data = $DB->fetch_assoc($result_linked)) {
                  
                  if ($itemtype == 'Softwarelicense') {
                     $soft = new Software();
                     $soft->getFromDB($data['softwares_id']);
                     $data["name"] .= ' - ' . $soft->getName(); //This add name of software
                  } elseif ($itemtype == "PluginResourcesResource") {
                     $data["name"] = formatUserName($data["id"], "", $data["name"],
                                           $data["firstname"]);
                  }
                  
                  $linkname = $data[$column];
                  
                  if ($_SESSION["glpiis_ids_visible"] || empty($data[$column])) {
                     $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $data["id"]);
                  }
                  
                  $name = "<a href=\"".Toolbox::getItemTypeFormURL($itemtype)."?id=".$data["id"]."\">".$linkname."</a>";
                  
                  if ($itemtype == 'PluginMreportingConfig' 
                        || $itemtype == 'PluginProjetProjet'
                        || $itemtype == 'PluginResourcesResource') {
                     $pieces = preg_split('/(?=[A-Z])/', $itemtype);
                     $plugin_name = $pieces[2];
                     
                     $datas = array(
                           //"currentuser" => "glpi",
                           "entities_id" => $data["entity"],
                           "ITEM_0" => $data["name"],
                           "ITEM_0_2" => $data["id"],
                           "id" => $data["id"],
                           "META_0" => $data["name"], //for PluginResourcesResource
                     );
                     if (isset($data["is_recursive"])) {
                        $datas["is_recursive"] = $data["is_recursive"];
                     }
                     
                     Plugin::load(strtolower($plugin_name), true);
                     $function_giveitem = 'plugin_'.strtolower($plugin_name).'_giveItem';
                     if (function_exists($function_giveitem)) { // For security
                        $name = call_user_func($function_giveitem, $itemtype, 1, $datas, 0);
                     }
                     
                  }
   
                  echo "<tr class='tab_bg_1'>";
                  
                  if ($canedit) {
                     echo "<td width='10'>";
                     if ($item->canUpdate()) {
                        Html::showMassiveActionCheckBox(__CLASS__, $data["IDD"]);
                     }
                     echo "</td>";
                  }
                  echo "<td class='center'>";
                  
                  // Show plugin name (is to delete remove any ambiguity) :
                  $pieces = preg_split('/(?=[A-Z])/', $itemtype);
                  if ($pieces[1] == 'Plugin') {
                     $plugin_name = $pieces[2];
                     if (function_exists("plugin_version_".$plugin_name)) { // For security
                        $tab = call_user_func("plugin_version_".$plugin_name);
                        echo $tab["name"]." : ";
                     }
                  }
                  
                  echo $item->getTypeName(1)."</td>";
                  echo "<td ".(isset($data['is_deleted']) && $data['is_deleted']?"class='tab_bg_2_2'":"").">".$name."</td>";
                  echo "<td class='center'>";
                  
                  $entity = $data['entity'];
                  
                  //for Plugins :
                  if ($data["entity"] == -1) {
                     $item->getFromDB($data['id']);
                     if (isset($item->fields["entities_id"])) {
                        $entity = $item->fields["entities_id"];
                     }
                  }
                  echo Dropdown::getDropdownName("glpi_entities", $entity);
                  
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
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";

   }

}