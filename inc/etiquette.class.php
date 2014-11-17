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
                     
                     /*
                     exit;
                     
                     //canEditItem :
                     //echo $item->getTypeName(1).", ".$data['IDD'];
                     $s = $item->getTypeName(1)."";
                     $t = new $s();
                     //$t = new Computer();
                     $t->getFromDB($data['IDD']);
                     //$t->canUpdate()
      
                     if ($canedit && $t->canUpdate()) {*/
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
      
      /*
   
      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false; // implicit field is id
      $tab[2]['datatype']        = 'number';
   
      $tab += Location::getSearchOptionsToAdd();
   
      $tab[4]['table']           = 'glpi_computertypes';
      $tab[4]['field']           = 'name';
      $tab[4]['name']            = __('Type');
      $tab[4]['datatype']        = 'dropdown';
   
      $tab[40]['table']          = 'glpi_computermodels';
      $tab[40]['field']          = 'name';
      $tab[40]['name']           = __('Model');
      $tab[40]['datatype']       = 'dropdown';
   
      $tab[31]['table']          = 'glpi_states';
      $tab[31]['field']          = 'completename';
      $tab[31]['name']           = __('Status');
      $tab[31]['datatype']       = 'dropdown';
   
      $tab[45]['table']          = 'glpi_operatingsystems';
      $tab[45]['field']          = 'name';
      $tab[45]['name']           = __('Operating system');
      $tab[45]['datatype']       = 'dropdown';
   
      $tab[46]['table']          = 'glpi_operatingsystemversions';
      $tab[46]['field']          = 'name';
      $tab[46]['name']           = __('Version of the operating system');
      $tab[46]['datatype']       = 'dropdown';
   
      $tab[41]['table']          = 'glpi_operatingsystemservicepacks';
      $tab[41]['field']          = 'name';
      $tab[41]['name']           = __('Service pack');
      $tab[41]['datatype']       = 'dropdown';
   
      $tab[42]['table']          = 'glpi_autoupdatesystems';
      $tab[42]['field']          = 'name';
      $tab[42]['name']           = __('Update Source');
      $tab[42]['datatype']       = 'dropdown';
   
      $tab[43]['table']          = $this->getTable();
      $tab[43]['field']          = 'os_license_number';
      $tab[43]['name']           = __('Serial of the operating system');
      $tab[43]['datatype']       = 'string';
   
      $tab[44]['table']          = $this->getTable();
      $tab[44]['field']          = 'os_licenseid';
      $tab[44]['name']           = __('Product ID of the operating system');
      $tab[44]['datatype']       = 'string';
   
      $tab[47]['table']          = $this->getTable();
      $tab[47]['field']          = 'uuid';
      $tab[47]['name']           = __('UUID');
      $tab[47]['datatype']       = 'string';
   
      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'serial';
      $tab[5]['name']            = __('Serial number');
      $tab[5]['datatype']        = 'string';
   
      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'otherserial';
      $tab[6]['name']            = __('Inventory number');
      $tab[6]['datatype']        = 'string';
   
      $tab[16]['table']          = $this->getTable();
      $tab[16]['field']          = 'comment';
      $tab[16]['name']           = __('Comments');
      $tab[16]['datatype']       = 'text';
   
      $tab[90]['table']          = $this->getTable();
      $tab[90]['field']          = 'notepad';
      $tab[90]['name']           = __('Notes');
      $tab[90]['massiveaction']  = false;
      $tab[90]['datatype']       = 'text';
   
      $tab[7]['table']          = $this->getTable();
      $tab[7]['field']          = 'contact';
      $tab[7]['name']           = __('Alternate username');
      $tab[7]['datatype']       = 'string';
   
      $tab[8]['table']          = $this->getTable();
      $tab[8]['field']          = 'contact_num';
      $tab[8]['name']           = __('Alternate username number');
      $tab[8]['datatype']       = 'string';
   
      $tab[70]['table']          = 'glpi_users';
      $tab[70]['field']          = 'name';
      $tab[70]['name']           = __('User');
      $tab[70]['datatype']       = 'dropdown';
      $tab[70]['right']          = 'all';
   
      $tab[71]['table']          = 'glpi_groups';
      $tab[71]['field']          = 'completename';
      $tab[71]['name']           = __('Group');
      $tab[71]['condition']      = '`is_itemgroup`';
      $tab[71]['datatype']       = 'dropdown';
   
      $tab[19]['table']          = $this->getTable();
      $tab[19]['field']          = 'date_mod';
      $tab[19]['name']           = __('Last update');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;
   
      $tab[32]['table']          = 'glpi_networks';
      $tab[32]['field']          = 'name';
      $tab[32]['name']           = __('Network');
      $tab[32]['datatype']       = 'dropdown';
   
      $tab[33]['table']          = 'glpi_domains';
      $tab[33]['field']          = 'name';
      $tab[33]['name']           = __('Domain');
      $tab[33]['datatype']       = 'dropdown';
   
      $tab[23]['table']          = 'glpi_manufacturers';
      $tab[23]['field']          = 'name';
      $tab[23]['name']           = __('Manufacturer');
      $tab[23]['datatype']       = 'dropdown';
   
      $tab[24]['table']          = 'glpi_users';
      $tab[24]['field']          = 'name';
      $tab[24]['linkfield']      = 'users_id_tech';
      $tab[24]['name']           = __('Technician in charge of the hardware');
      $tab[24]['datatype']       = 'dropdown';
      $tab[24]['right']          = 'own_ticket';
   
      $tab[49]['table']          = 'glpi_groups';
      $tab[49]['field']          = 'completename';
      $tab[49]['linkfield']      = 'groups_id_tech';
      $tab[49]['name']           = __('Group in charge of the hardware');
      $tab[49]['condition']      = '`is_assign`';
      $tab[49]['datatype']       = 'dropdown';
   
      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');
      $tab[80]['datatype']       = 'dropdown';
   
   
      $tab['periph']             = _n('Component', 'Components', 2);
   
      $items_device_joinparams   = array('jointype'          => 'itemtype_item',
            'specific_itemtype' => 'Computer');
   
      $tab[17]['table']           = 'glpi_deviceprocessors';
      $tab[17]['field']           = 'designation';
      $tab[17]['name']            = __('Processor');
      $tab[17]['forcegroupby']    = true;
      $tab[17]['usehaving']       = true;
      $tab[17]['massiveaction']   = false;
      $tab[17]['datatype']        = 'string';
      $tab[17]['joinparams']      = array('beforejoin'
            => array('table'      => 'glpi_items_deviceprocessors',
                  'joinparams' => $items_device_joinparams));
   
      $tab[36]['table']          = 'glpi_items_deviceprocessors';
      $tab[36]['field']          = 'frequency';
      $tab[36]['name']           = __('Processor frequency');
      $tab[36]['unit']           = __('MHz');
      $tab[36]['forcegroupby']   = true;
      $tab[36]['usehaving']      = true;
      $tab[36]['datatype']       = 'number';
      $tab[36]['width']          = 100;
      $tab[36]['massiveaction']  = false;
      $tab[36]['joinparams']     = $items_device_joinparams;
   
      $tab[10]['table']          = 'glpi_devicememories';
      $tab[10]['field']          = 'designation';
      $tab[10]['name']           = __('Memory type');
      $tab[10]['forcegroupby']   = true;
      $tab[10]['usehaving']      = true;
      $tab[10]['massiveaction']  = false;
      $tab[10]['datatype']       = 'string';
      $tab[10]['joinparams']     = array('beforejoin'
            => array('table'      => 'glpi_items_devicememories',
                  'joinparams' => $items_device_joinparams));
   
      $tab[35]['table']          = 'glpi_items_devicememories';
      $tab[35]['field']          = 'size';
      $tab[35]['name']           = sprintf(__('%1$s (%2$s)'),__('Memory'),__('Mio'));
      $tab[35]['forcegroupby']   = true;
      $tab[35]['usehaving']      = true;
      $tab[35]['datatype']       = 'number';
      $tab[35]['width']          = 100;
      $tab[35]['massiveaction']  = false;
      $tab[35]['joinparams']     = $items_device_joinparams;
   
   
      $tab[11]['table']          = 'glpi_devicenetworkcards';
      $tab[11]['field']          = 'designation';
      $tab[11]['name']           = _n('Network interface', 'Network interfaces', 1);
      $tab[11]['forcegroupby']   = true;
      $tab[11]['massiveaction']  = false;
      $tab[11]['datatype']       = 'string';
      $tab[11]['joinparams']     = array('beforejoin'
            => array('table'      => 'glpi_items_devicenetworkcards',
                  'joinparams' => $items_device_joinparams));
   
      $tab[12]['table']          = 'glpi_devicesoundcards';
      $tab[12]['field']          = 'designation';
      $tab[12]['name']           = __('Soundcard');
      $tab[12]['forcegroupby']   = true;
      $tab[12]['massiveaction']  = false;
      $tab[12]['datatype']       = 'string';
      $tab[12]['joinparams']     = array('beforejoin'
            => array('table'      => 'glpi_items_devicesoundcards',
                  'joinparams' => $items_device_joinparams));
   
      $tab[13]['table']          = 'glpi_devicegraphiccards';
      $tab[13]['field']          = 'designation';
      $tab[13]['name']           = __('Graphics card');
      $tab[13]['forcegroupby']   = true;
      $tab[13]['massiveaction']  = false;
      $tab[13]['datatype']       = 'string';
      $tab[13]['joinparams']     = array('beforejoin'
            => array('table'      => 'glpi_items_devicegraphiccards',
                  'joinparams' => $items_device_joinparams));
   
      $tab[14]['table']          = 'glpi_devicemotherboards';
      $tab[14]['field']          = 'designation';
      $tab[14]['name']           = __('System board');
      $tab[14]['forcegroupby']   = true;
      $tab[14]['massiveaction']  = false;
      $tab[14]['datatype']       = 'string';
      $tab[14]['joinparams']     = array('beforejoin'
            => array('table'      => 'glpi_items_devicemotherboards',
                  'joinparams' => $items_device_joinparams));
   
   
      $tab[15]['table']          = 'glpi_deviceharddrives';
      $tab[15]['field']          = 'designation';
      $tab[15]['name']           = __('Hard drive type');
      $tab[15]['forcegroupby']   = true;
      $tab[15]['usehaving']      = true;
      $tab[15]['massiveaction']  = false;
      $tab[15]['datatype']       = 'string';
      $tab[15]['joinparams']     = array('beforejoin'
            => array('table'      => 'glpi_items_deviceharddrives',
                  'joinparams' => $items_device_joinparams));
   
      $tab[34]['table']          = 'glpi_items_deviceharddrives';
      $tab[34]['field']          = 'capacity';
      $tab[34]['name']           = __('Hard drive size');
      $tab[34]['forcegroupby']   = true;
      $tab[34]['usehaving']      = true;
      $tab[34]['datatype']       = 'number';
      $tab[34]['width']          = 1000;
      $tab[34]['massiveaction']  = false;
      $tab[34]['joinparams']     = $items_device_joinparams;
   
   
      $tab[39]['table']          = 'glpi_devicepowersupplies';
      $tab[39]['field']          = 'designation';
      $tab[39]['name']           = __('Power supply');
      $tab[39]['forcegroupby']   = true;
      $tab[39]['usehaving']      = true;
      $tab[39]['massiveaction']  = false;
      $tab[39]['datatype']       = 'string';
      $tab[39]['joinparams']     = array('beforejoin'
            => array('table'      => 'glpi_items_devicepowersupplies',
                  'joinparams' => $items_device_joinparams));
   
      $tab['disk']               = _n('Volume', 'Volumes', 2);
   
      $tab[156]['table']         = 'glpi_computerdisks';
      $tab[156]['field']         = 'name';
      $tab[156]['name']          = __('Volume');
      $tab[156]['forcegroupby']  = true;
      $tab[156]['massiveaction'] = false;
      $tab[156]['datatype']      = 'dropdown';
      $tab[156]['joinparams']    = array('jointype' => 'child');
   
      $tab[150]['table']         = 'glpi_computerdisks';
      $tab[150]['field']         = 'totalsize';
      $tab[150]['name']          = __('Global size');
      $tab[150]['forcegroupby']  = true;
      $tab[150]['usehaving']     = true;
      $tab[150]['datatype']      = 'number';
      $tab[150]['width']         = 1000;
      $tab[150]['massiveaction'] = false;
      $tab[150]['joinparams']    = array('jointype' => 'child');
   
      $tab[151]['table']         = 'glpi_computerdisks';
      $tab[151]['field']         = 'freesize';
      $tab[151]['name']          = __('Free size');
      $tab[151]['forcegroupby']  = true;
      $tab[151]['datatype']      = 'number';
      $tab[151]['width']         = 1000;
      $tab[151]['massiveaction'] = false;
      $tab[151]['joinparams']    = array('jointype' => 'child');
   
      $tab[152]['table']         = 'glpi_computerdisks';
      $tab[152]['field']         = 'freepercent';
      $tab[152]['name']          = __('Free percentage');
      $tab[152]['forcegroupby']  = true;
      $tab[152]['datatype']      = 'decimal';
      $tab[152]['width']         = 2;
      $tab[152]['computation']   = "ROUND(100*TABLE.freesize/TABLE.totalsize)";
      $tab[152]['unit']          = '%';
      $tab[152]['massiveaction'] = false;
      $tab[152]['joinparams']    = array('jointype' => 'child');
   
      $tab[153]['table']         = 'glpi_computerdisks';
      $tab[153]['field']         = 'mountpoint';
      $tab[153]['name']          = __('Mount point');
      $tab[153]['forcegroupby']  = true;
      $tab[153]['massiveaction'] = false;
      $tab[153]['datatype']      = 'string';
      $tab[153]['joinparams']    = array('jointype' => 'child');
   
      $tab[154]['table']         = 'glpi_computerdisks';
      $tab[154]['field']         = 'device';
      $tab[154]['name']          = __('Partition');
      $tab[154]['forcegroupby']  = true;
      $tab[154]['massiveaction'] = false;
      $tab[154]['datatype']      = 'string';
      $tab[154]['joinparams']    = array('jointype' => 'child');
   
      $tab[155]['table']         = 'glpi_filesystems';
      $tab[155]['field']         = 'name';
      $tab[155]['name']          = __('File system');
      $tab[155]['forcegroupby']  = true;
      $tab[155]['massiveaction'] = false;
      $tab[155]['datatype']      = 'dropdown';
      $tab[155]['joinparams']    = array('beforejoin'
            => array('table'      => 'glpi_computerdisks',
                  'joinparams' => array('jointype' => 'child')));
   
      $tab['virtualmachine']     = _n('Virtual machine', 'Virtual machines', 2);
   
      $tab[160]['table']         = 'glpi_computervirtualmachines';
      $tab[160]['field']         = 'name';
      $tab[160]['name']          = __('Virtual machine');
      $tab[160]['forcegroupby']  = true;
      $tab[160]['massiveaction'] = false;
      $tab[160]['datatype']      = 'dropdown';
      $tab[160]['joinparams']    = array('jointype' => 'child');
   
      $tab[161]['table']         = 'glpi_virtualmachinestates';
      $tab[161]['field']         = 'name';
      $tab[161]['name']          = __('State of the virtual machine');
      $tab[161]['forcegroupby']  = true;
      $tab[161]['massiveaction'] = false;
      $tab[161]['datatype']      = 'dropdown';
      $tab[161]['joinparams']    = array('beforejoin'
            => array('table'      => 'glpi_computervirtualmachines',
                  'joinparams' => array('jointype' => 'child')));
   
      $tab[162]['table']         = 'glpi_virtualmachinetypes';
      $tab[162]['field']         = 'name';
      $tab[162]['name']          = __('Virtualization model');
      $tab[162]['forcegroupby']  = true;
      $tab[162]['massiveaction'] = false;
      $tab[162]['datatype']      = 'dropdown';
      $tab[162]['joinparams']    = array('beforejoin'
            => array('table'      => 'glpi_computervirtualmachines',
                  'joinparams' => array('jointype' => 'child')));
   
      $tab[163]['table']         = 'glpi_virtualmachinetypes';
      $tab[163]['field']         = 'name';
      $tab[163]['name']          = __('Virtualization system');
      $tab[163]['datatype']      = 'dropdown';
      $tab[163]['forcegroupby']  = true;
      $tab[163]['massiveaction'] = false;
      $tab[163]['joinparams']    = array('beforejoin'
            => array('table'      => 'glpi_computervirtualmachines',
                  'joinparams' => array('jointype' => 'child')));
   
      $tab[164]['table']         = 'glpi_computervirtualmachines';
      $tab[164]['field']         = 'vcpu';
      $tab[164]['name']          = __('Virtual machine processor number');
      $tab[164]['datatype']      = 'number';
      $tab[164]['forcegroupby']  = true;
      $tab[164]['massiveaction'] = false;
      $tab[164]['joinparams']    = array('jointype' => 'child');
   
      $tab[165]['table']         = 'glpi_computervirtualmachines';
      $tab[165]['field']         = 'ram';
      $tab[165]['name']          = __('Memory of virtual machines');
      $tab[165]['datatype']      = 'number';
      $tab[165]['unit']          = __('Mio');
      $tab[165]['forcegroupby']  = true;
      $tab[165]['massiveaction'] = false;
      $tab[165]['joinparams']    = array('jointype' => 'child');
   
      $tab[166]['table']         = 'glpi_computervirtualmachines';
      $tab[166]['field']         = 'uuid';
      $tab[166]['name']          = __('Virtual machine UUID');
      $tab[165]['datatype']      = 'string';
      $tab[166]['forcegroupby']  = true;
      $tab[166]['massiveaction'] = false;
      $tab[166]['joinparams']    = array('jointype' => 'child');
   */
      return $tab;
   }
}
