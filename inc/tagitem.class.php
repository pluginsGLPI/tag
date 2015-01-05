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
      return _n('Tag', 'Tags', 'tag');
   }
   
   public static function install(Migration $migration) {
      $table = getTableForItemType(__CLASS__);
      if (!TableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
            	`id` INT(11) NOT NULL AUTO_INCREMENT,
            	`plugin_tag_tags_id` INT(11) NOT NULL DEFAULT '0',
            	`items_id` TINYINT(1) NOT NULL DEFAULT '1',
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
      global $DB;
      $query = "SELECT *
               FROM `glpi_plugin_tag_tagitems`
               WHERE itemtype='$itemtype' AND items_id=$id_glpi_obj";
   
      $result = $DB->query($query);
      
      $IDs = array();
      if ($DB->numrows($result) > 0) {
         while($datas = $DB->fetch_assoc($result)) {
            $IDs[] = $datas["plugin_tag_tags_id"];
         }
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
      global $CFG_GLPI;
       
      $tab                       = array();
      $tab['common']             = __('Characteristics');
      return $tab;
       
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
   }
   
   static function showForTag(PluginTagTag $tag) {
      global $DB, $CFG_GLPI;
   
      $instID = $tag->fields['id'];
      if (!$tag->can($instID,"r")) {
         return false;
      }
      
      $canedit = $tag->can($instID,'w');
      
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
         FROM `glpi_plugin_tag_tagitems`
         WHERE `glpi_plugin_tag_tagitems`.`plugin_tag_tags_id` = '$instID'");
      $number = $DB->numrows($result);
      $rand   = mt_rand();
   
      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='tagitem_form$rand' id='tagitem_form$rand' method='post'
         action='".Toolbox::getItemTypeFormURL('PluginTagTag')."'>";
         
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add an item')."</th></tr>";
         
         echo "<tr class='tab_bg_1'><td class='right'>";
         Dropdown::showAllItems("items_id", 0, 0,
            ($tag->fields['is_recursive']?-1:$tag->fields['entities_id']),
            $itemtypes, false, true
         );
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "<input type='hidden' name='plugin_tag_tags_id' value='$instID'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }
   
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
            $itemtable = getTableForItemType($itemtype);
            $query     = "SELECT `$itemtable`.*, `glpi_plugin_tag_tagitems`.`id` AS IDD, ";
      
            switch ($itemtype) {
               //TODO : Vérifier si on doit le mettre en majuscule
               case 'knowbaseitem':
               $query .= "-1 AS entity
                  FROM `glpi_plugin_tag_tagitems`, `$itemtable`
                  ".KnowbaseItem::addVisibilityJoins()."
                  WHERE `$itemtable`.`id` = `glpi_plugin_tag_tagitems`.`items_id`
                  AND ";
                  break;
               case 'Profile':
               case 'RSSFeed':
                  //Possible to add (in code) condition to visibility :
                  $query .= "-1 AS entity
                  FROM `glpi_plugin_tag_tagitems`, `$itemtable`
                  WHERE `$itemtable`.`id` = `glpi_plugin_tag_tagitems`.`items_id`
                  AND ";
                  break;
               default:
               $query .= "`glpi_entities`.`id` AS entity
                  FROM `glpi_plugin_tag_tagitems`, `$itemtable`
                  LEFT JOIN `glpi_entities`
                  ON (`glpi_entities`.`id` = `$itemtable`.`entities_id`)
                  WHERE `$itemtable`.`id` = `glpi_plugin_tag_tagitems`.`items_id`
                  AND ";
                  break;
            }
            $query .= "`glpi_plugin_tag_tagitems`.`itemtype` = '$itemtype'
               AND `glpi_plugin_tag_tagitems`.`plugin_tag_tags_id` = '$instID' ";
   
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
   
               switch ($itemtype) {
                  //TODO : Vérifier aussi ici majuscule ?
                  case 'knowbaseitem':
                  case 'Profile':
                  case 'RSSFeed':
                     $query .= " ORDER BY `$itemtable`.`$column`";
                     break;
                  default:
                     $query .= " ORDER BY `glpi_entities`.`completename`, `$itemtable`.`$column`";
                     break;
               }
      
               if ($result_linked = $DB->query($query)) {
                  if ($DB->numrows($result_linked)) {
                     
                     if ($itemtype == 'softwarelicense') {
                        $soft = new Software();
                     }
      
                     while ($data = $DB->fetch_assoc($result_linked)) {
      
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
                        if ($item->canUpdate()) {
                           Html::showMassiveActionCheckBox(__CLASS__, $data["IDD"]);
                        }
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
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions(__CLASS__, $massiveactionparams);
            Html::closeForm();
         }
         echo "</div>";
   
      }

}