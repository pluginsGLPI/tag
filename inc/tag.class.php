<?php
class PluginTagTag extends CommonDropdown {

   // From CommonDBTM
   public $dohistory = true;
   
   const MNBSP = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
   
   const TAG_SEARCH_NUM = 10500;

   public static function getTypeName($nb=1) {
      return _n('Tag', 'Tags', $nb, 'tag');
   }
   
   public function showForm($ID, $options = array()) {
      global $CFG_GLPI;
      
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo '<table class="tab_cadre_fixe">';
      echo "<tr class='line0 tab_bg_2'><td><label for='name'>" . __('Name') . " <span class='red'>*</span></label></td>";
      echo "<td>";
      echo '<input type="text" id="name" name="name" value="'.$this->fields['name'].'" size="40" required>';
      echo "</td>";
      echo "</tr>";

      echo "<tr class='line1 tab_bg_2'><td><label for='comment'>" . __('Description') . "</label></td>";
      echo "<td>";
      echo "<textarea name='comment' id ='comment' cols='45' rows='3'>" . $this->fields['comment'] . "</textarea>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='line1 tab_bg_2'><td><label>" . __('HTML color', 'tag') . "</label></td>"; 
      echo "<td>";
      //Note : create some bugs
      Html::showColorField('color', array('value' => $this->fields['color']));
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='line0 tab_bg_2'>";
      echo "<td colspan='2'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='line1 tab_bg_2'>";
      echo "<th>"._n('Associated item type', 'Associated item types',2)."</th>";
      echo "</tr>";
      
      echo "<tr class='line1 tab_bg_2'>";
      echo "<td class='center'>";
      echo _n('Tag type', 'Tag types', 1, 'tag')." ";
      $values = array(0 => Dropdown::EMPTY_VALUE);
      $menus  = Html::getMenuInfos();
      foreach ($menus as $key => $value) {
         if ($key != 'plugins' && $key != 'preference') {
            $values[$key] = $menus[$key]['title'];
         }
      }
      $rand = Dropdown::showFromArray("type_menu", $values, array('value'     => $this->fields['type_menu'],
                                                                  'width'     => '50%',
                                                                  'on_change' => 'pluginTagSubType();'));

      echo "<div id='plugin_tag_itemtype'></div><br>";
      $JS = 'function pluginTagSubType(){';
      $JS .= Ajax::updateItemJsCode('plugin_tag_itemtype', 
                                    $CFG_GLPI['root_doc']."/plugins/tag/ajax/tag.php", 
                                    array('action' => 'add_subtypes', 'type_menu' => '__VALUE__', 'rand' => $rand), 
                                    "dropdown_type_menu$rand", false);
      $JS .= '}';
      $JS .= 'pluginTagSubType();';
      echo Html::scriptBlock($JS);
      
      // Sub type choice
      $itemtypes = array();
      $selected  = array();
      if (!empty($this->fields['type_menu'])) {
         foreach (json_decode($this->fields['type_menu'], true) as $itemtype) {
            $item                 = getItemForItemtype($itemtype);
            $itemtypes[$itemtype] = $item->getTypeName();
            $selected[]           = $itemtype;
         }
      }
      Dropdown::showFromArray("subtypes", $itemtypes,
                              array('values' => $selected, 'multiple' => true, 'rand' => $rand, 'width' => '100%'));
      echo "</td>";
      echo "</tr>";
      echo "</table>";
      echo "</td>";
      echo "</tr>";
      
      $this->showFormButtons($options);

      return true;
   }
   
   public static function install(Migration $migration) {
      global $DB;
      
      $table = getTableForItemType(__CLASS__);
      if (!TableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int(11) NOT NULL auto_increment,
                     `entities_id` int(11) NOT NULL DEFAULT '0',
                     `is_recursive` tinyint(1) NOT NULL DEFAULT '1',
                     `name` varchar(255) NOT NULL DEFAULT '',
                     `comment` text collate utf8_unicode_ci,
                     `color` varchar(50) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
                     PRIMARY KEY (`id`),
                     KEY `name` (`name`)
                     ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $GLOBALS['DB']->query($query) or die($GLOBALS['DB']->error());
      }
      
      if (!FieldExists($table, 'type_menu')) {
         $migration->addField($table, 'type_menu', "varchar(50) NOT NULL DEFAULT ''");
         $migration->addKey($table, 'type_menu');
         $migration->migrationOneTable($table);
      }

      // Version 0.90-1.1
      $result = $DB->query("SHOW FIELDS FROM `$table` where Field ='type_menu'");
      if ($result && $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            if (stristr($data["Type"], 'varchar') !== FALSE) {
               $DB->query("ALTER TABLE `$table` DROP INDEX `type_menu`;");
               $DB->query("ALTER TABLE `$table` MODIFY `type_menu` text COLLATE utf8_unicode_ci;");
               
               $datas = getAllDatasFromTable($table, "`type_menu` IS NOT NULL");
               if (!empty($datas)) {
                  foreach ($datas as $data) {
                     $itemtypes = PluginTagTagItem::getItemtypes($data['type_menu']);
                     $DB->query("UPDATE `$table` SET `type_menu` = '".json_encode($itemtypes)."' WHERE `id` = '".$data['id']."'");
                  }
               }
               break;
            }
         }
      }

      return true;
   }

   public static function uninstall() {
      $query = "DELETE FROM glpi_logs WHERE itemtype_link='".__CLASS__."' OR itemtype = '".__CLASS__."'";
      $GLOBALS['DB']->query($query);
      
      $query = "DELETE FROM glpi_bookmarks WHERE itemtype='".__CLASS__."'";
      $GLOBALS['DB']->query($query);

      $query = "DELETE FROM glpi_bookmarks_users WHERE itemtype='".__CLASS__."'";
      $GLOBALS['DB']->query($query);
      
      $query = "DELETE FROM glpi_displaypreferences WHERE itemtype='".__CLASS__."' OR num=".PluginTagTag::TAG_SEARCH_NUM;
      $GLOBALS['DB']->query($query);
      
      $query = "DROP TABLE IF EXISTS `" . getTableForItemType(__CLASS__) . "`";
      return $GLOBALS['DB']->query($query) or die($GLOBALS['DB']->error());
   }
   
   /**
    * Définition du nom de l'onglet
    **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      $tab = array();
      $tab[2] = _n('Associated item', 'Associated items', 2); //Note : can add nb_element here
      return $tab;
   }
   
   /**
    * Définition du contenu de l'onglet
    **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      switch ($item->getType()) {
         case __CLASS__ :
            switch ($tabnum) {
               case 2 :
                  $tagitem = new PluginTagTagItem();
                  $tagitem->showForTag($item);
                  break;

            }
      }
      return true;
   }
   
   function defineTabs($options=array()) {
      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      return $ong;
   }
   
   public function cleanDBonPurge() {
      $GLOBALS['DB']->query("DELETE FROM `glpi_plugin_tag_tagitems`
                WHERE `plugin_tag_tags_id`=".$this->fields['id']);
   }

    /**
    * Return the linked items (in computers_items)
    *
    * @return an array of linked items  like array('Computer' => array(1,2), 'Printer' => array(5,6))
    * @since version 0.84.4
    **/
   function getLinkedItems() {
      $query = "SELECT `itemtype`, `items_id`
              FROM `glpi_computers_items`
              WHERE `computers_id` = '" . $this->fields['id']."'";
      $tab = array();
      foreach ($GLOBALS['DB']->request($query) as $data) {
         $tab[$data['itemtype']][$data['items_id']] = $data['items_id'];
      }
      return $tab;
   }
   
   // for massive actions
   function haveChildren() {
      $tagitems = new PluginTagTagItem();
      $data = $tagitems->find("plugin_tag_tags_id = ".$this->fields['id']);
      if (empty($data)) {
         return false;
      }
      return true;
   }
   
   /**
    * @see CommonDBTM::getSpecificMassiveActions()
    **/
   function getSpecificMassiveActions($checkitem=NULL) {
      return CommonDBTM::getSpecificMassiveActions($checkitem);
   }
   
   /**
    * @see CommonDBTM::showSpecificMassiveActionsParameters()
    **/
   function showSpecificMassiveActionsParameters($input=array()) {
      switch ($input['action']) {
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
         default :
            return parent::doSpecificMassiveActions($input);
      }
      return $res;
   }
   
   function getSearchOptions() {
      $tab                       = array();
      
      $tab['common']             = __('Characteristics');

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
      
      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'id';
      $tab[3]['name']            = __('ID');
      $tab[3]['massiveaction']   = false;
      $tab[3]['datatype']        = 'number';

      $tab[4]['table']           = 'glpi_entities';
      $tab[4]['field']           = 'completename';
      $tab[4]['linkfield']       = 'entities_id';
      $tab[4]['name']            = __('Entity');
      $tab[4]['datatype']        = 'dropdown';
      
      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'is_recursive';
      $tab[5]['name']            = __('Child entities');
      $tab[5]['datatype']        = 'bool';
      
      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'type_menu';
      $tab[6]['searchtype']      = array('equals', 'notequals');
      $tab[6]['name']            = _n('Associated item type', 'Associated item types',2);

      // For History tab (quick fix)
      $tab[7]['table']           = $this->getTable();
      $tab[7]['field']           = 'color';
      $tab[7]['name']            = __('HTML color', 'tag');
      $tab[7]['searchtype']      = 'contains';

      return $tab;
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name            (default '')
    * @param $values          (default '')
    * @param $options   array
    **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;
      $options['value']   = $values[$field];
      switch ($field) {
         case 'type_menu':
            $tab = array('' => Dropdown::EMPTY_VALUE);

            $itemtypes = PluginTagTagitem::getItemtypes('all');
            foreach ($itemtypes as $itemtype) {
               $item           = getItemForItemtype($itemtype);
               $tab[$itemtype] = $item->getTypeName();

            }

            return Dropdown::showFromArray($name, $tab, $options);
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }
   
   /**
    * For fixed the issue #1 on Github
    */
   static function getItemtype($itemtype, $id) {
      // Specific for a webpage in GLPI
      if ($itemtype == 'rule.generic') {
         $rule = new Rule();
         $rule->getFromDB($id);
         return $rule->fields["sub_type"];
      }
      return $itemtype;
   }

   static function showMoreButton($rand) {
      global $CFG_GLPI;

      echo "&nbsp;<img title=\"".__s('Add')."\" alt=\"".__s('Add')."\" src='".$CFG_GLPI["root_doc"].
           "/pics/add_dropdown.png' style='cursor:pointer;margin-left:2px;'
           onClick=\"var w = window.open('".
            $CFG_GLPI['root_doc']."/plugins/tag/front/tag.form.php?popup=1&amp;rand=".$rand."', ".
            "'glpipopup', 'height=400, width=1000, top=100, left=100, scrollbars=yes' );w.focus();\">";
   }
   
   static function tagDropdownMultiple($options = array()) {
      
      $itemtype = self::getItemtype($_REQUEST['itemtype'], $_REQUEST['id']);
      $obj = new $itemtype();

      // Object must be an instance of CommonDBTM (or inherint of this)
      if (!$obj instanceof CommonDBTM) {
        return;
      }

      $obj->getFromDB($_REQUEST['id']);
      $sel_attr = $obj->canUpdateItem() ? '' : ' disabled ';

      echo "<select data-placeholder='".__('Choose tags...', 'tag').self::MNBSP."' name='_plugin_tag_tag_values[]'
          id='tag_select' multiple class='chosen-select-no-results' ".$sel_attr." style='width:80%;' >";

      $selected_id = array();
      $tag_item = new PluginTagTagItem();
      foreach ($tag_item->find('items_id='.$_REQUEST['id'].' 
                                AND itemtype="'.$itemtype.'"') as $found_item) {
         $selected_id[] = $found_item['plugin_tag_tags_id'];
      }

      // Restrict tag by entity if current object has entity
      $where = "1 ";
      if (isset($obj->fields['entities_id'])) {
         $field = $obj->getType() == 'Entity' ? 'id' : 'entities_id';
         $where .= getEntitiesRestrictRequest("AND", '', '', $obj->fields[$field], true);
      }
      
      $tag   = new self();
      $items = $tag->find($where, 'name');
      foreach ($items as $item) {
         $param = in_array($item['id'], $selected_id) ? ' selected ' : '';
         $param .= 'data-color-option="'.$item['color'].'"';
         if (!empty($item['type_menu'])) {
            // Allowed types
            foreach (json_decode($item['type_menu'], true) as $subtype) {
               if (strtolower($subtype) == $itemtype) {
                  echo '<option value="'.$item['id'].'" '.$param.'>'.$item['name'].'</option>';
                  break;
               }
            }
            
         } else {
            echo '<option value="'.$item['id'].'" '.$param.'>'.$item['name'].'</option>';
         }
      }
      echo "</select>";

      if (self::canCreate()) {
         $rand = mt_rand();

         /*
         echo "<script type='text/javascript'>\n
            window.updateTagSelectResults_".$rand." = function () {
               
            }
         </script>";
         */

         // Show '+' button :
         self::showMoreButton($rand);
      }
   }
   
  /** 
   * Actions done before add
   * 
   * @param type $input
   * @return type
   */
   function prepareInputForAdd($input) {
      if (!$this->checkMandatoryFields($input)) {
         return false;
      }
      
      $input = $this->encodeSubtypes($input);
      
      return $input;
   }
   
  /** 
   * Actions done before update
   * 
   * @param type $input
   * @return type
   */
   function prepareInputForUpdate($input) {
      if (!$this->checkMandatoryFields($input)) {
         return false;
      }

      $input = $this->encodeSubtypes($input);
      
      return $input;
   }
   
  /** 
   * Encode sub types
   * 
   * @param type $input
   */
   function encodeSubtypes($input) {
      if (!empty($input['subtypes'])) {
         $input['type_menu'] = json_encode($input['subtypes']);
      }
      
      return $input;
   }

   /** 
   * checkMandatoryFields 
   * 
   * @param type $input
   * @return boolean
   */
   function checkMandatoryFields($input){
      $msg     = array();
      $checkKo = false;

      $mandatory_fields = array('name'     => __('Name'));

      foreach ($input as $key => $value) {
         if (isset($mandatory_fields[$key])) {
            if (empty($value)) {
               $msg[]   = $mandatory_fields[$key];
               $checkKo = true;
            }
         }
      }

      if ($checkKo) {
         Session::addMessageAfterRedirect(sprintf(__("Mandatory fields are not filled. Please correct: %s"), implode(', ', $msg)), true, ERROR);
         return false;
      }
      return true;
   }
}
