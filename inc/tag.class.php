<?php
class PluginTagTag extends CommonDropdown {

   // From CommonDBTM
   public $dohistory = true;

   const S_OPTION = 10500;

   public static function getTypeName($nb=1) {
      return _n('Tag', 'Tags', $nb, 'tag');
   }

   /**
    * Return the list of blackisted itemtype
    * We don't want tag system on theses
    *
    * @return array of string itemtypes
    */
   public static function getBlacklistItemtype() {
      return [
         'PluginTagTag',
         'Notification',
         'Crontask',
         'PluginFormcreatorFormanswer',
         'QueuedMail',
      ];
   }

   /**
    * Check if the passed itemtype is in the blacklist
    *
    * @param  string $itemtype
    *
    * @return bool
    */
   public static function canItemtype($itemtype = '') {
      return (!class_exists($itemtype)
              || !in_array($itemtype, self::getBlacklistItemtype()));
   }

   public function showForm($ID, $options = array()) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo '<table class="tab_cadre_fixe">';
      echo "<tr class='line0 tab_bg_2'>";
      echo "<td><label for='name'>".__('Name')." <span class='red'>*</span></label></td>";
      echo "<td>";
      echo '<input type="text" id="name" name="name" value="'.$this->fields['name'].'" size="40" required>';
      echo "</td>";
      echo "</tr>";

      echo "<tr class='line1 tab_bg_2'>";
      echo "<td><label for='comment'>".__('Description')."</label></td>";
      echo "<td>";
      echo "<textarea name='comment' id ='comment' cols='45' rows='3'>".
            $this->fields['comment'].
            "</textarea>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='line1 tab_bg_2'>";
      echo "<td><label>".__('HTML color', 'tag')."</label></td>";
      echo "<td>";
      Html::showColorField('color', ['value' => $this->fields['color']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='line0 tab_bg_2'>";
      echo "<td colspan='2'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='line1 tab_bg_2'>";
      echo "<th>"._n('Associated item type', 'Associated item types', 2)."</th>";
      echo "</tr>";

      echo "<tr class='line1 tab_bg_2'>";
      echo "<td class='center'>";
      echo _n('Tag type', 'Tag types', 1, 'tag')." ";
      $values = [0 => Dropdown::EMPTY_VALUE];
      $menus  = Html::getMenuInfos();
      foreach ($menus as $key => $value) {
         if ($key != 'plugins' && $key != 'preference') {
            $values[$key] = $menus[$key]['title'];
         }
      }
      $rand = Dropdown::showFromArray("type_menu", $values, ['value'     => $this->fields['type_menu'],
                                                             'width'     => '50%',
                                                             'on_change' => 'pluginTagSubType();']);

      echo "<div id='plugin_tag_itemtype'></div><br>";
      $JS = 'function pluginTagSubType(){';
      $JS .= Ajax::updateItemJsCode('plugin_tag_itemtype',
                                    $CFG_GLPI['root_doc']."/plugins/tag/ajax/tag.php",
                                    ['action'    => 'add_subtypes',
                                     'type_menu' => '__VALUE__',
                                     'rand'      => $rand],
                                    "dropdown_type_menu$rand", false);
      $JS .= '}';
      $JS .= 'pluginTagSubType();';
      echo Html::scriptBlock($JS);

      // Sub type choice
      $itemtypes = [];
      $selected  = [];
      if (!empty($this->fields['type_menu'])) {
         foreach (json_decode($this->fields['type_menu'], true) as $itemtype) {
            $item                 = getItemForItemtype($itemtype);
            $itemtypes[$itemtype] = $item->getTypeName();
            $selected[]           = $itemtype;
         }
      }
      Dropdown::showFromArray("subtypes", $itemtypes,
                              ['values'   => $selected,
                               'multiple' => true,
                               'rand'     => $rand,
                               'width'    => '100%']);
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
         $DB->query("CREATE TABLE IF NOT EXISTS `$table` (
            `id`           int(11) NOT NULL auto_increment,
            `entities_id`  int(11) NOT NULL DEFAULT '0',
            `is_recursive` tinyint(1) NOT NULL DEFAULT '1',
            `name`         varchar(255) NOT NULL DEFAULT '',
            `comment`      text collate utf8_unicode_ci,
            `color`        varchar(50) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
            `type_menu`    text collate utf8_unicode_ci,
            PRIMARY KEY (`id`),
            KEY `name` (`name`)
         ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci")
            or die($DB->error());
      }

      if (!FieldExists($table, 'type_menu')) {
         $migration->addField($table, 'type_menu', "text");
         $migration->migrationOneTable($table);
      }

      // Version 0.90-1.1
      $fields = $DB->list_fields($table);
      if (stristr($fields['type_menu']["Type"], 'varchar') !== false) {
         $migration->changeField($table, 'type_menu', 'type_menu', 'text');
         $migration->dropKey($table, 'type_menu');
         $migration->migrationOneTable($table);

         $datas = getAllDatasFromTable($table, "`type_menu` IS NOT NULL");
         if (!empty($datas)) {
            foreach ($datas as $data) {
               $itemtypes = PluginTagTagItem::getItemtypes($data['type_menu']);
               $DB->query("UPDATE `$table`
                           SET `type_menu` = '".json_encode($itemtypes)."'
                           WHERE `id` = '".$data['id']."'");
            }
         }
      }

      return true;
   }

   public static function uninstall() {
      global $DB;

      $DB->query("DELETE FROM glpi_logs
                  WHERE itemtype_link = '".__CLASS__."'
                     OR itemtype = '".__CLASS__."'")
         or die($DB->error());

      $DB->query("DELETE FROM glpi_bookmarks
                  WHERE itemtype = '".__CLASS__."'")
         or die($DB->error());

      $DB->query("DELETE FROM glpi_bookmarks_users
                  WHERE itemtype = '".__CLASS__."'")
         or die($DB->error());

      $DB->query("DELETE FROM glpi_displaypreferences
                  WHERE itemtype = '".__CLASS__."'
                     OR num = ".self::S_OPTION)
         or die($DB->error());

      $DB->query("DROP TABLE IF EXISTS `".getTableForItemType(__CLASS__)."`")
         or die($DB->error());

      return true;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      $tab    = [];
      $tab[2] = _n('Associated item', 'Associated items', 2); //Note : can add nb_element here
      return $tab;
   }

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
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('PluginTagTagItem', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      return $ong;
   }

   public function cleanDBonPurge() {
      $tagitem = new PluginTagTagItem();
      $tagitem->deleteByCriteria([
         'plugin_tag_tags_id' => $this->getID()
      ]);
   }

   function getLinkedItems() {
      global $DB;

      $query = "SELECT `itemtype`, `items_id`
                FROM `glpi_computers_items`
                WHERE `computers_id` = '".$this->getID()."'";
      $tab = [];
      foreach ($DB->request($query) as $data) {
         $tab[$data['itemtype']][$data['items_id']] = $data['items_id'];
      }
      return $tab;
   }

   // for massive actions
   function haveChildren() {
      $tagitems = new PluginTagTagItem();
      $data = $tagitems->find("`plugin_tag_tags_id` = ".$this->getID());
      if (count($data) == 0) {
         return false;
      }
      return true;
   }

   function getSearchOptions() {
      $tab                       = [];

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
      $tab[6]['searchtype']      = ['equals', 'notequals'];
      $tab[6]['name']            = _n('Associated item type', 'Associated item types', 2);
      $tab[6]['datatype']        = 'specific';

      $tab[7]['table']           = $this->getTable();
      $tab[7]['field']           = 'color';
      $tab[7]['name']            = __('HTML color', 'tag');
      $tab[7]['searchtype']      = 'contains';
      $tab[7]['datatype']        = 'specific';

      return $tab;
   }


   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {
      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'type_menu':
            $elements  = ['' => Dropdown::EMPTY_VALUE];
            foreach (PluginTagTagitem::getItemtypes('all') as $itemtype) {
               $item                = getItemForItemtype($itemtype);
               $elements[$itemtype] = $item->getTypeName();
            }

            return Dropdown::showFromArray($name, $elements,
                                           ['display' => false,
                                            'value'   => $values[$field]]);
            break;
      }

      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   static function getSpecificValueToDisplay($field, $values, array $options = array()) {
      switch ($field) {
         case 'type_menu':
            $itemtypes = json_decode($values[$field]);
            if (json_last_error() !== JSON_ERROR_NONE) {
               return __("None");
            }
            $itemtype_names = [];
            foreach ($itemtypes as $itemtype) {
               $item = getItemForItemtype($itemtype);
               $itemtype_names[] = $item->getTypeName();
            }
            $out = implode(", ", $itemtype_names);
            return $out;
         case 'color' :
            return "<div style='background-color: $values[$field];'>&nbsp;</div>";
      }

      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   /**
    * @see https://github.com/pluginsGLPI/tag/issues/1
    */
   static function parseItemtype($itemtype, $id = 0) {
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

   /**
    * Display the current tag dropdown in form header of items
    *
    * @param  array $params should contains theses keys:
    *                          - item the CommonDBTM object
    * @return nothing
    */
   static function preItemForm($params = array()) {
      if (isset($params['item'])
          && $params['item'] instanceof CommonDBTM) {
         $itemtype = get_class($params['item']);

         if (self::canItemtype($itemtype)) {
            $html_tag = ($itemtype == 'Ticket') ? "th" : 'td';

            echo "<tr class='tab_bg_1'>";
            echo "<$html_tag>"._n('Tag', 'Tags', 2, 'tag')."</$html_tag>";
            echo "<td colspan='3'>";
            self::showTagDropdown([
               'itemtype' => $itemtype,
               'id'       => $params['item']->getId(),
            ]);
            echo "</td>";
            echo "</tr>";
         }
      }
   }

   /**
    * Display the tag dropdowns
    * @param  array  $params could contains theses keys:
    *                           - itemtype (mandatory)
    *                           - id (optionnal)
    * @return nothing
    */
   static function showTagDropdown($params = []) {
      // compute default params
      $default_params = [
         'id'       => 0,
         'itemtype' => '',
      ];
      $params = array_merge($default_params, $params);

      // check itemtype
      $itemtype = self::parseItemtype($params['itemtype'], $params['id']);
      $obj = new $itemtype();

      // Object must be an instance of CommonDBTM (or inherint of this)
      if (!$obj instanceof CommonDBTM) {
         return;
      }

      // retrieve single object
      if (isset($params['id'])) {
         $obj->getFromDB($params['id']);
      }

      // create the select html tag
      $rand     = mt_rand();
      $sel_attr = $obj->canUpdateItem() ? '' : ' disabled ';
      echo "<select data-placeholder='".__('Choose tags...', 'tag')."'
                    name='_plugin_tag_tag_values[]'
                    id='tag_select_$rand' multiple
                    class='tag_select chosen-select-no-results'
                    $sel_attr
                    style='width:80%;' >";

      // found already used tag
      $selected_id = [];
      $tag_item = new PluginTagTagItem();
      foreach ($tag_item->find('items_id='.$params['id'].'
                                AND itemtype="'.$itemtype.'"') as $found_item) {
         $selected_id[] = $found_item['plugin_tag_tags_id'];
      }

      // Restrict tag finding by entity
      $where = "";
      if ($obj->isEntityAssign()) {
         $where = getEntitiesRestrictRequest("AND", '', '', '', true);
      }

      // create the select options
      $tag   = new self();
      $ftags = $tag->find($where, 'name');
      foreach ($ftags as $current_tag) {
         if (empty($current_tag['type_menu'])
             || in_array($itemtype, json_decode($current_tag['type_menu'], true))) {
            echo "<option value='{$current_tag['id']}'
                          ".(in_array($current_tag['id'], $selected_id) ? ' selected ' : '')."
                          data-color-option='{$current_tag['color']}'>".
            $current_tag['name'].'</option>';
         }
      }
      echo "</select>";

      // call select2 lib for this select html tag
      echo Html::scriptBlock("$(function() {
         $('#tag_select_$rand').select2({
             'formatResult': formatOption,
             'formatSelection': formatOption
         });
      });");

      // Show '+' button :
      if (self::canCreate()) {
         self::showMoreButton($rand);
      }
   }

   function prepareInputForAdd($input) {
      if (!$this->checkMandatoryFields($input)) {
         return false;
      }

      return $this->encodeSubtypes($input);
   }

   function prepareInputForUpdate($input) {
      if (!$this->checkMandatoryFields($input)) {
         return false;
      }

      return $this->encodeSubtypes($input);
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
   * Check all mandatory field are filled
   *
   * @param array $input
   * @return boolean
   */
   function checkMandatoryFields($input = []) {
      $msg              = [];
      $checkKo          = false;
      $mandatory_fields = ['name' => __('Name')];

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

   /**
    * Retrieve the current itemtype from the current page url
    *
    * @return mixed(string/boolean) false if not itemtype found, the string itemtype if found
    */
   public static function getCurrentItemtype() {
      $itemtype = '';
      if (preg_match('/\/plugins\/([a-zA-Z]+)\/front\/([a-zA-Z]+).form.php/',
                     $_SERVER['PHP_SELF'], $matches)) {
         $itemtype = 'plugin'.$matches[1].$matches[2];

      } else if (preg_match('/([a-zA-Z]+).form.php/', $_SERVER['PHP_SELF'], $matches)) {
         $itemtype = $matches[1];

      } else if (preg_match('/\/plugins\/([a-zA-Z]+)\/front\/([a-zA-Z]+).php/',
                            $_SERVER['PHP_SELF'], $matches)) {
         $itemtype = 'plugin'.$matches[1].$matches[2];

      } else if (preg_match('/([a-zA-Z]+).php/', $_SERVER['PHP_SELF'], $matches)) {
         $itemtype = $matches[1];
      }

      if (!empty($itemtype)
          && class_exists($itemtype)
          && is_subclass_of($itemtype, "CommonDBTM")) {
         // instanciate itemtype (to retrieve camelcase)
         $item = new $itemtype;
         return $item->getType();
      }

      return false;
   }
}
