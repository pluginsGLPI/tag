<?php

/**
 * -------------------------------------------------------------------------
 * Tag plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Tag.
 *
 * Tag is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tag is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tag. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2014-2022 by Teclib'.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/tag
 * -------------------------------------------------------------------------
 */

class PluginTagTag extends CommonDropdown {

   // From CommonDBTM
   public $dohistory = true;

   const S_OPTION = 10500;
   static $rightname = 'plugin_tag_tag';

   public static function getTypeName($nb = 1) {
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
         'PluginTagTagItem',
         'Itil_Project',
         'Item_Project',
         'Notification',
         'Crontask',
         'PluginFormcreatorFormanswer',
         'QueuedNotification',
         'PluginPrintercountersRecord',
         'ITILSolution',
         'ITILFollowup',
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

   public function showForm($ID, $options = []) {
      global $CFG_GLPI;

      if (!$this->canViewItem()) {
         return false;
      }

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
      Html::showColorField('color', ['value' => $this->fields['color'] ?: '#DDDDDD']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='line0 tab_bg_2'>";
      echo "<td><label>"
           ._n('Associated item type', 'Associated item types', 2)."</label></td>";
      echo "</td>";
      echo "<td>";
      // show an hidden input to permist deletion of all values
      echo Html::hidden("type_menu");

      // retrieve tags elements and existing values
      $type_menu_elements = [];
      foreach ($CFG_GLPI['plugin_tag_itemtypes'] as $group_label => $group_values) {
         foreach ($group_values as $itemtype) {
            $type_menu_elements[$group_label][$itemtype] = $itemtype::getTypeName();
         }
      }
      $type_menu_values = !empty($this->fields['type_menu']) ? json_decode($this->fields['type_menu']) : [];

      // show the multiple dropdown
      Dropdown::showFromArray("type_menu",
                              $type_menu_elements,
                              ['values'   => $type_menu_values,
                               'multiple' => 'multiples']);

      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);

      return true;
   }

   public static function install(Migration $migration) {
      global $DB;

      $default_charset = DBConnection::getDefaultCharset();
      $default_collation = DBConnection::getDefaultCollation();
      $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

      $table = getTableForItemType(__CLASS__);

      if (!$DB->tableExists($table)) {
         $DB->query("CREATE TABLE IF NOT EXISTS `$table` (
            `id`           int {$default_key_sign} NOT NULL auto_increment,
            `entities_id`  int {$default_key_sign} NOT NULL DEFAULT '0',
            `is_recursive` tinyint NOT NULL DEFAULT '1',
            `name`         varchar(255) NOT NULL DEFAULT '',
            `comment`      text,
            `color`        varchar(50) NOT NULL DEFAULT '',
            `type_menu`    text,
            PRIMARY KEY (`id`),
            KEY `name` (`name`)
         ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;")
            or die($DB->error());
      }

      if (!$DB->fieldExists($table, 'type_menu')) {
         $migration->addField($table, 'type_menu', "text");
         $migration->migrationOneTable($table);
      }

      // Version 0.90-1.1
      // Disable cache on field list as cache wes not pruned after adding field
      $fields = $DB->listFields($table, false);
      if (stristr($fields['type_menu']["Type"], 'varchar') !== false) {
         $migration->changeField($table, 'type_menu', 'type_menu', 'text');
         $migration->dropKey($table, 'type_menu');
         $migration->migrationOneTable($table);

         $datas = getAllDataFromTable($table, ['NOT' => ['type_menu' => null]]);
         if (!empty($datas)) {
            foreach ($datas as $data) {
               $itemtypes = PluginTagTagItem::getItemtypes($data['type_menu']);
               $DB->query("UPDATE `$table`
                           SET `type_menu` = '".json_encode($itemtypes)."'
                           WHERE `id` = '".$data['id']."'");
            }
         }
      }

      // Add full rights to profiles that have READ or UPDATE config right
      $migration->addRight(self::$rightname);
      $migration->displayWarning("Tags now have rights. Please review all profiles to set the required level of rights.");

      if (Session::haveRight(Config::$rightname, READ | UPDATE)) {
         // Update active profile to give access without having to logout/login
         $_SESSION['glpiactiveprofile'][self::$rightname] = ALLSTANDARDRIGHT;
      }

      return true;
   }

   public static function uninstall() {
      global $DB;

      $DB->query("DELETE FROM glpi_logs
                  WHERE itemtype_link = '".__CLASS__."'
                     OR itemtype = '".__CLASS__."'")
         or die($DB->error());

      $DB->query("DELETE FROM glpi_savedsearches
                  WHERE itemtype = '".__CLASS__."'")
         or die($DB->error());

      $DB->query("DELETE FROM glpi_savedsearches_users
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

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $tab    = [];
      $tab[2] = _n('Associated item', 'Associated items', 2); //Note : can add nb_element here
      return $tab;
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
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

   function defineTabs($options = []) {
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
      $data = $tagitems->find(['plugin_tag_tags_id' => $this->getID()]);
      if (count($data) == 0) {
         return false;
      }
      return true;
   }

   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'            => 'common',
         'name'          => __('Characteristics'),
      ];

      $tab[] = [
         'id'            => 1,
         'table'         => $this->getTable(),
         'field'         => 'name',
         'name'          => __('Name'),
         'datatype'      => 'itemlink',
         'massiveaction' => true,
      ];

      $tab[] = [
         'id'            => 2,
         'table'         => $this->getTable(),
         'field'         => 'comment',
         'name'          => __('Description'),
         'datatype'      => 'string',
         'massiveaction' => true,
      ];

      $tab[] = [
         'id'            => 3,
         'table'         => $this->getTable(),
         'field'         => 'id',
         'name'          => __('ID'),
         'datatype'      => 'number',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => 4,
         'table'         => 'glpi_entities',
         'field'         => 'completename',
         'linkfield'     => 'entities_id',
         'name'          => __('Entity'),
         'datatype'      => 'dropdown',
      ];

      $tab[] = [
         'id'            => 5,
         'table'         => $this->getTable(),
         'field'         => 'is_recursive',
         'name'          => __('Child entities'),
         'datatype'      => 'bool',
      ];

      $tab[] = [
         'id'            => 6,
         'table'         => $this->getTable(),
         'field'         => 'type_menu',
         'name'          => _n('Associated item type', 'Associated item types', 2),
         'searchtype'    => ['equals', 'notequals'],
         'datatype'      => 'specific',
      ];

      $tab[] = [
         'id'            => 7,
         'table'         => $this->getTable(),
         'field'         => 'color',
         'name'          => __('HTML color', 'tag'),
         'searchtype'    => 'contains',
         'datatype'      => 'specific',
      ];

      return $tab;
   }

   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {
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

   static function getSpecificValueToDisplay($field, $values, array $options = []) {
      switch ($field) {
         case 'type_menu':
            $itemtypes = json_decode($values[$field]);
            if (!is_array($itemtypes)) {
               return "&nbsp;";
            }
            $itemtype_names = [];
            foreach ($itemtypes as $itemtype) {
               $item = getItemForItemtype($itemtype);
               $itemtype_names[] = $item->getTypeName();
            }
            $out = implode(", ", $itemtype_names);
            return $out;
         case 'color' :
            $color = $values[$field] ?: '#DDDDDD';
            return "<div style='background-color: $color;'>&nbsp;</div>";
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

   /**
    * Display the current tag dropdown in form of items.
    *
    * Depending on the config settings, this will either show at the top or bottom of the forms.
    *
    * @param  array $params should contains theses keys:
    *                          - item the CommonDBTM object
    * @return bool|void False if the form was not shown. Otherwise nothing is returned and the form is displayed.
    */
   static function showForItem($params = []) {
      if (!self::canView()) {
         return false;
      }

      if (isset($params['item'])
         && $params['item'] instanceof CommonDBTM) {
         $item     = $params['item'];
         $itemtype = get_class($item);

         if (self::canItemtype($itemtype)) {
            // manage values after a redirect (ex ticket creation, after a cat change)
            $value = '';
            if (isset($item->input['_plugin_tag_tag_values'])) {
               $value = $item->input['_plugin_tag_tag_values'];
            }

            $field_class = "form-field row col-12 col-sm-6 px-3 mt-2 mb-n2";
            $label_class = "col-form-label col-xxl-5 text-xxl-end";
            $input_class = "col-xxl-7 field-container";

            if ($item instanceof CommonITILObject) {
               $field_class = "form-field row col-12 mb-2";
               $label_class = "col-form-label col-xxl-4 text-xxl-end";
               $input_class = "col-xxl-8 field-container";
            }

            echo "<div class='$field_class'>";
            echo "<div class='$label_class'>".
               _n('Tag', 'Tags', 2, 'tag').
            "</div>";
            echo "<div class='$input_class'>";
            self::showTagDropdown([
               'itemtype' => $itemtype,
               'id'       => $item->getId(),
               'value'    => $value,
            ]);
            echo "</div>";
            echo "</div>";
         }
      }
   }

   /**
    * Display the current tags before the Kanban item content.
    *
    * @param  array $params should contains theses keys:
    *                          - itemtype The item type
    *                          - items_id The item's id
    *                          - content postKanbanContent content
    * @return array Array of params passed in in addition to the new content.
    */
   static function preKanbanContent($params = []) {
      global $DB;

      if (!Session::haveRight(PluginTagTag::$rightname, READ)) {
         return null;
      }

      if (isset($params['itemtype']) && isset($params['items_id'])) {
         if (!isset($params['content'])) {
            $params['content'] = "";
         }
         $iterator = $DB->request([
            'SELECT'    => [
               'name',
               'comment',
               'color'
            ],
            'FROM'      => PluginTagTagItem::getTable(),
            'LEFT JOIN' => [
               PluginTagTag::getTable() => [
                  'FKEY'   => [
                     PluginTagTag::getTable()      => 'id',
                     PluginTagTagItem::getTable()  => 'plugin_tag_tags_id'
                  ]
               ]
            ],
            'WHERE'     => [
               'itemtype'  => $params['itemtype'],
               'items_id'  => $params['items_id']
            ]
         ]);

         $content = "<div style='display: flex; flex-wrap: wrap;'>";
         foreach ($iterator as $data) {
            $title = $data['comment'];
            $name = $data['name'];
            $color = $data['color'] ?: '#DDDDDD';
            $textcolor = idealTextColor($color);
            $style = "background-color: {$color}; color: {$textcolor};";
            $content .= "<span class='tag_choice' style='{$style}' title='{$title}'>{$name}</span>&nbsp;&nbsp;";
         }
         $content .= "</div>";
         $params['content'] .= $content;
         return $params;
      }
      return null;
   }

   public static function kanbanItemMetadata($params) {
      global $DB;

      if (!Session::haveRight(PluginTagTag::$rightname, READ)) {
         return $params;
      }

      if (isset($params['itemtype']) && isset($params['items_id'])) {
         $iterator = $DB->request([
            'SELECT'    => [
               'name',
            ],
            'FROM'      => PluginTagTagItem::getTable(),
            'LEFT JOIN' => [
               PluginTagTag::getTable() => [
                  'FKEY'   => [
                     PluginTagTag::getTable()      => 'id',
                     PluginTagTagItem::getTable()  => 'plugin_tag_tags_id'
                  ]
               ]
            ],
            'WHERE'     => [
               'itemtype'  => $params['itemtype'],
               'items_id'  => $params['items_id']
            ]
         ]);

         $params['metadata']['tags'] = $params['metadata']['tags'] ?? [];
         foreach ($iterator as $data) {
            $params['metadata']['tags'][] = $data['name'];
         }
      }
      return $params;
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
         'value'    => '',
      ];
      $params = array_merge($default_params, $params);

      // check itemtype
      $itemtype = self::parseItemtype($params['itemtype'], $params['id']);
      $obj = new $itemtype();

      // Object must be an instance of CommonDBTM (or inherint of this)
      if (!$obj instanceof CommonDBTM) {
         return;
      }

      // instanciate needed objects
      $tag      = new self();
      $tag_item = new PluginTagTagItem();

      // retrieve current item
      if (isset($params['id'])) {
         $obj->getFromDB($params['id']);
      }

      // find values for this items
      $values = [];
      if (!$obj->isNewItem()) {
         foreach ($tag_item->find(['items_id' => $params['id'],
                                   'itemtype' => $itemtype]) as $found_item) {
            $values[] = $found_item['plugin_tag_tags_id'];
         }
      } elseif (is_string($params['value'])) {
         $values = !empty($params['value']) ? explode(',', $params['value']) : [];
      } elseif (is_array($params['value'])) {
         $values = $params['value'];
      }

      // Restrict tags finding by itemtype and entity
      $where = [
         'OR' => [
            ['type_menu' => null],
            ['type_menu' => ''],
            ['type_menu' => 0],
            ['type_menu' => ['LIKE', '%"'.$itemtype.'"%']],
         ]
      ];
      if ($obj->isEntityAssign()) {
         $where += getEntitiesRestrictCriteria('', '', '', true);
      }

      // found existing tags
      $existing_tags = $tag->find($where, 'name');
      $select2_tags = [];
      foreach ($existing_tags as $existing_tag) {
         $select2_tags[] = [
            'id'       => $existing_tag['id'],
            'text'     => $existing_tag['name'],
            'color'    => $existing_tag['color'],
            'selected' => in_array($existing_tag['id'], $values),
         ];
      }
      // new tags restored from saved input
      $new_tags = array_diff($values, array_column($existing_tags, 'id'));
      foreach ($new_tags as $new_tag)  {
          $select2_tags[] = [
            'id'       => $new_tag,
            'text'     => preg_replace('/^newtag_(.+)/', '$1', $new_tag),
            'selected' => true,
          ];
      }

      // create an input receiving the tag tokens
      echo "<div class='btn-group btn-group-sm w-100'>";

      $rand = mt_rand();
      echo Html::hidden('_plugin_tag_tag_process_form', ['value' => '1',]);
      echo Html::select(
         '_plugin_tag_tag_values[]',
         [],
         [
            'id'       => "tag_select_$rand",
            'class'    => 'tag_select',
            'multiple' => 'multiple',
         ]
      );

      $token_creation = "
         // prefix value by 'newtag_' to differenciate created tag from existing ones
         return { id: 'newtag_'+ params.term, text: params.term };";
      if (!self::canCreate()) {
         $token_creation = "return null;";
      }

      $readOnly = (!$tag::canUpdate() ||
            ((($obj->isNewItem() && !$obj->canCreateItem())) ||
            (!$obj->isNewItem() && !$obj->canUpdateItem())));

      // call select2 lib for this input
      echo Html::scriptBlock("$(function() {
         $('#tag_select_$rand').select2({
            width: 'calc(100% - 20px)',
            templateResult: formatOptionResult,
            templateSelection: formatOptionSelection,
            formatSearching: '".__("Loading...")."',
            dropdownCssClass: 'tag_select_results',
            data: ".json_encode($select2_tags).",
            tags: true,
            tokenSeparators: [',', ';'],
            disabled: ".($readOnly ? 'true': 'false').",
            createTag: function (params) {
               var term = $.trim(params.term);
               if (term === '') {
                  return null;
               }
               $token_creation
            }
         });
      });");

      // Show tooltip
      if (self::canCreate()) {
         echo "<div class='btn btn-outline-secondary'>";
         echo Html::showToolTip(__("View all tags", 'tag'),
                                ['link' => self::getSearchURL()]);
         echo "</div>";
      }

      echo "</div>";
   }

   static function getTagForEntityName($completename = "") {
      $plus_rootentity = sprintf(__('%1$s + %2$s'), '', __('Child entities'));
      $completename    = Html::entity_decode_deep($completename);
      $completename    = trim(str_replace($plus_rootentity, '', $completename));
      $entities_id     = Entity::getEntityIDByCompletename($completename);

      $out = "";
      if ($entities_id >= 0) {
         $tag_item = new PluginTagTagItem();
         foreach ($tag_item->find(['items_id' => $entities_id,
                                   'itemtype' => 'Entity']) as $found_item) {
            $out .= PluginTagTag::getSingleTag($found_item['plugin_tag_tags_id']);
         }
      }

      return $out;
   }

   static function getSingleTag($tag_id, $separator = '') {
      $plugintagtag = new self();
      $plugintagtag->getFromDB($tag_id);
      $color = $plugintagtag->fields["color"] ?: '#DDDDDD';
      $inv_color = idealTextColor($color);
      $style = "background-color: $color; border: 1px solid $inv_color; color: $inv_color";

      return "<span class='select2-search-choice tag_choice'
                    style='padding-left:5px;$style'>".
              $separator.$plugintagtag->fields['name'].'</span>';
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
      if (!empty($input['type_menu'])) {
         $input['type_menu'] = json_encode(array_values($input['type_menu']));
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
      if (
          preg_match('/\/(?:marketplace|plugins)\/genericobject\/front\/object\.form.php/', $_SERVER['PHP_SELF'])
          && array_key_exists('itemtype', $_GET)
      ) {
         $itemtype = $_GET['itemtype'];
      } else if (preg_match('/\/(?:marketplace|plugins)\/([a-zA-Z]+)\/front\/([a-zA-Z]+).form.php/',
                     $_SERVER['PHP_SELF'], $matches)) {
         $itemtype = 'Plugin'.ucfirst($matches[1]).ucfirst($matches[2]);

      } else if (preg_match('/([a-zA-Z]+).form.php/', $_SERVER['PHP_SELF'], $matches)) {
         $itemtype = $matches[1];

      } else if (preg_match('/\/(?:marketplace|plugins)\/([a-zA-Z]+)\/front\/([a-zA-Z]+).php/',
                            $_SERVER['PHP_SELF'], $matches)) {
         $itemtype = 'Plugin'.ucfirst($matches[1]).ucfirst($matches[2]);

      } else if (preg_match('/([a-zA-Z]+).php/', $_SERVER['PHP_SELF'], $matches)) {
         $itemtype = $matches[1];
      }

      $item = getItemForItemtype($itemtype);
      if ($item instanceof CommonDBTM) {
         return $item->getType();
      }

      return false;
   }

   static function getIcon() {
      return "fas fa-tags";
   }
}
