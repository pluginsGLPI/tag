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
 * @copyright Copyright (C) 2014-2023 by Teclib'.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/tag
 * -------------------------------------------------------------------------
 */
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\Form\Form;
use Glpi\Message\MessageType;

class PluginTagTag extends CommonDropdown
{
    // From CommonDBTM
    public $dohistory = true;

    public const S_OPTION = 10500;
    public static $rightname = 'plugin_tag_tag';

    public static function getTypeName($nb = 1)
    {
        return _sn('Tag', 'Tags', $nb, 'tag');
    }

    /**
     * Return the list of blackisted itemtype
     * We don't want tag system on theses
     *
     * @return array of string itemtypes
     */
    public static function getBlacklistItemtype()
    {
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
    public static function canItemtype($itemtype = '')
    {
        if (empty($itemtype) || !class_exists($itemtype) || in_array($itemtype, self::getBlacklistItemtype())) {
            return false;
        }

        $tags = new self();
        $types_menu = [];
        $use_global_tag = false;

        foreach ($tags->find(['is_active' => 1]) as $tag) {
            if (!empty($tag['type_menu'])) {
                $types_menu = array_merge($types_menu, json_decode($tag['type_menu']));
            } else {
                $use_global_tag = true;
            }
        }

        return $use_global_tag || in_array($itemtype, $types_menu);
    }

    public function showForm($ID, $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!$this->canViewItem()) {
            return false;
        }

        $this->initForm($ID, $options);

        // retrieve tags elements and existing values
        $type_menu_elements = [];
        foreach ($CFG_GLPI['plugin_tag_itemtypes'] as $group_label => $group_values) {
            foreach ($group_values as $itemtype) {
                $type_menu_elements[$group_label][$itemtype] = $itemtype::getTypeName();
            }
        }
        $type_menu_values = !empty($this->fields['type_menu']) ? json_decode($this->fields['type_menu']) : [];

        TemplateRenderer::getInstance()->display('@tag/forms/tag.html.twig', [
            'item'               => $this,
            'params'             => $options,
            'type_menu_elements' => $type_menu_elements,
            'type_menu_values'   => $type_menu_values,
        ]);

        return true;
    }

    public static function install(Migration $migration)
    {
        /**
         * @var DBmysql $DB
         * @var array   $CFG_GLPI
         */
        global $DB, $CFG_GLPI;

        $default_charset = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

        $table = getTableForItemType(self::class);

        if (!$DB->tableExists($table)) {
            $query = <<<SQL
                CREATE TABLE IF NOT EXISTS `$table` (
                    `id`           int {$default_key_sign} NOT NULL auto_increment,
                    `entities_id`  int {$default_key_sign} NOT NULL DEFAULT '0',
                    `is_recursive` tinyint NOT NULL DEFAULT '1',
                    `is_active`    tinyint NOT NULL DEFAULT '1',
                    `name`         varchar(255) NOT NULL DEFAULT '',
                    `comment`      text,
                    `color`        varchar(50) NOT NULL DEFAULT '',
                    `type_menu`    text,
                    PRIMARY KEY (`id`),
                    KEY `name` (`name`),
                    KEY `entities_id` (`entities_id`),
                    KEY `is_recursive` (`is_recursive`),
                    KEY `is_active` (`is_active`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
            $DB->doQuery($query);
        } else {
            if (!$DB->fieldExists($table, 'type_menu')) {
                $migration->addField($table, 'type_menu', "text");
                $migration->migrationOneTable($table);
            }

            if (!$DB->fieldExists($table, 'is_active')) {
                $migration->addField($table, 'is_active', "tinyint NOT NULL DEFAULT '1'");
                $migration->migrationOneTable($table);
            }

            $migration->addKey($table, 'name');
            $migration->addKey($table, 'entities_id');
            $migration->addKey($table, 'is_recursive');
            $migration->addKey($table, 'is_active');
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
                    $itemtypes = $CFG_GLPI['plugin_tag_itemtypes'][$data['type_menu']] ?? [];
                    $DB->update($table, ['type_menu' => json_encode($itemtypes)], ['id' => $data['id']]);
                }
            }
        }

        // Add full rights to profiles that have READ or UPDATE config right
        $migration->addRight(self::$rightname);
        $migration->addMessage(MessageType::Warning, "Tags now have rights. Please review all profiles to set the required level of rights.");

        if (Session::haveRight(Config::$rightname, READ | UPDATE)) {
            // Update active profile to give access without having to logout/login
            $_SESSION['glpiactiveprofile'][self::$rightname] = ALLSTANDARDRIGHT;
        }

        return true;
    }

    public static function uninstall()
    {
        /** @var DBmysql $DB */
        global $DB;

        $DB->delete('glpi_logs', [
            'OR' => [
                'itemtype_link' => self::class,
                'itemtype'      => self::class,
            ],
        ]);
        $DB->delete('glpi_savedsearches', [
            'itemtype'      => self::class,
        ]);
        $DB->delete('glpi_savedsearches_users', [
            'itemtype'      => self::class,
        ]);
        $DB->delete('glpi_displaypreferences', [
            'OR' => [
                'itemtype'      => self::class,
                'num'           => self::S_OPTION,
            ],
        ]);

        $migration = new Migration(PLUGIN_TAG_VERSION);
        $migration->dropTable(getTableForItemType(self::class));

        return true;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $tab    = [];

        $nb = 0;
        if ($item instanceof CommonDBTM && $_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable(PluginTagTagItem::getTable(), [
                'plugin_tag_tags_id' => $item->getID(),
            ]);
        }

        $tab[2] = self::createTabEntry(_sn('Associated item', 'Associated items', 2), $nb, $item::getType(), 'ti ti-list');
        return $tab;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof self) {
            switch ($tabnum) {
                case 2:
                    $tagitem = new PluginTagTagItem();
                    $tagitem->showForTag($item);
                    break;
            }
        }
        return true;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(self::class, $ong, $options);
        $this->addStandardTab('PluginTagTagItem', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);
        return $ong;
    }

    public function cleanDBonPurge()
    {
        $tagitem = new PluginTagTagItem();
        $tagitem->deleteByCriteria([
            'plugin_tag_tags_id' => $this->getID(),
        ]);
    }

    public function getLinkedItems()
    {
        /** @var DBmysql $DB */
        global $DB;

        $query = "SELECT `itemtype`, `items_id`
                FROM `glpi_computers_items`
                WHERE `computers_id` = '" . $this->getID() . "'";
        $tab = [];
        foreach ($DB->doQuery($query) as $data) {
            $tab[$data['itemtype']][$data['items_id']] = $data['items_id'];
        }
        return $tab;
    }

    // for massive actions
    public function haveChildren()
    {
        $tagitems = new PluginTagTagItem();
        $data = $tagitems->find(['plugin_tag_tags_id' => $this->getID()]);
        return count($data) != 0;
    }

    public function rawSearchOptions()
    {

        $tab = [];

        $tab[] = [
            'id'            => 'common',
            'name'          => __s('Characteristics'),
        ];

        $tab[] = [
            'id'            => 1,
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => __s('Name'),
            'datatype'      => 'itemlink',
            'massiveaction' => true,
        ];

        $tab[] = [
            'id'            => 2,
            'table'         => $this->getTable(),
            'field'         => 'comment',
            'name'          => __s('Description'),
            'datatype'      => 'string',
            'massiveaction' => true,
        ];

        $tab[] = [
            'id'            => 3,
            'table'         => $this->getTable(),
            'field'         => 'id',
            'name'          => __s('ID'),
            'datatype'      => 'number',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => 4,
            'table'         => 'glpi_entities',
            'field'         => 'completename',
            'linkfield'     => 'entities_id',
            'name'          => __s('Entity'),
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => 5,
            'table'         => $this->getTable(),
            'field'         => 'is_recursive',
            'name'          => __s('Child entities'),
            'datatype'      => 'bool',
        ];

        $tab[] = [
            'id'            => 6,
            'table'         => $this->getTable(),
            'field'         => 'type_menu',
            'name'          => _sn('Associated item type', 'Associated item types', 2),
            'searchtype'    => ['equals', 'notequals'],
            'datatype'      => 'specific',
        ];

        $tab[] = [
            'id'            => 7,
            'table'         => $this->getTable(),
            'field'         => 'color',
            'name'          => __s('HTML color', 'tag'),
            'searchtype'    => 'contains',
            'datatype'      => 'specific',
        ];

        $tab[] = [
            'id'            => 8,
            'table'         => $this->getTable(),
            'field'         => 'is_active',
            'name'          => __s('Active'),
            'datatype'      => 'bool',
        ];

        return $tab;
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'type_menu':
                $elements  = ['' => Dropdown::EMPTY_VALUE];
                $supported_itemtypes = $CFG_GLPI['plugin_tag_itemtypes'] ?? [];
                foreach ($supported_itemtypes as $itemtypes) {
                    foreach ($itemtypes as $itemtype) {
                        $item = getItemForItemtype($itemtype);
                        $elements[$itemtype] = $item::getTypeName();
                    }
                }

                return Dropdown::showFromArray(
                    $name,
                    $elements,
                    ['display' => false,
                        'value'   => $values[$field],
                    ],
                );
        }

        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
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
            case 'color':
                $color = $values[$field] ?: '#DDDDDD';
                return "<div style='background-color: $color;'>&nbsp;</div>";
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * @see https://github.com/pluginsGLPI/tag/issues/1
     */
    public static function parseItemtype($itemtype, $id = 0)
    {
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
    public static function showForItem($params = [])
    {
        if (!self::canView()) {
            return false;
        }

        if (
            isset($params['item'])
            && $params['item'] instanceof CommonDBTM
        ) {
            $item     = $params['item'];
            $itemtype = get_class($item);

            if (self::canItemtype($itemtype)) {
                // manage values after a redirect (ex ticket creation, after a cat change)
                $value = '';
                if (isset($item->input['_plugin_tag_tag_values'])) {
                    $value = $item->input['_plugin_tag_tag_values'];
                }

                self::showTagDropdown([
                    'itemtype' => $itemtype,
                    'id'       => $item->getId(),
                    'value'    => $value,
                ]);
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
     * @return array|null Array of params passed in in addition to the new content.
     */
    public static function preKanbanContent($params = [])
    {
        /** @var DBmysql $DB */
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
                    'color',
                ],
                'FROM'      => PluginTagTagItem::getTable(),
                'LEFT JOIN' => [
                    PluginTagTag::getTable() => [
                        'FKEY'   => [
                            PluginTagTag::getTable()      => 'id',
                            PluginTagTagItem::getTable()  => 'plugin_tag_tags_id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    'itemtype'  => $params['itemtype'],
                    'items_id'  => $params['items_id'],
                ],
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

    public static function kanbanItemMetadata($params)
    {
        /** @var DBmysql $DB */
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
                            PluginTagTagItem::getTable()  => 'plugin_tag_tags_id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    'itemtype'  => $params['itemtype'],
                    'items_id'  => $params['items_id'],
                ],
            ]);

            $params['metadata']['tags'] ??= [];
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
     * @return void
     */
    public static function showTagDropdown($params = [])
    {
        // compute default params
        $default_params = [
            'id'       => 0,
            'itemtype' => '',
            'value'    => '',
            'items_ids' => [],
        ];
        $params = array_merge($default_params, $params);

        $itemtype = self::parseItemtype($params['itemtype'], $params['id']);
        $obj = new $itemtype();

        if (!$obj instanceof CommonDBTM) {
            return;
        }

        $tag      = new self();
        $tag_item = new PluginTagTagItem();

        if (isset($params['id'])) {
            $obj->getFromDB($params['id']);
        }
        if ($obj->isNewItem()) {
            $obj->getEmpty();
        }

        $values = [];
        if (!$obj->isNewItem()) {
            foreach (
                $tag_item->find(['items_id' => $params['id'], 'itemtype' => $itemtype]) as $found_item
            ) {
                $values[] = $found_item['plugin_tag_tags_id'];
            }
        } elseif (is_string($params['value'])) {
            $values = !empty($params['value']) ? explode(',', $params['value']) : [];
        } elseif (is_array($params['value'])) {
            $values = $params['value'];
        }

        /** @var DBmysql $DB */
        global $DB;

        $where = [
            'is_active' => 1,
            'OR' => [
                ['type_menu' => null],
                ['type_menu' => ''],
                ['type_menu' => 0],
                new QueryExpression("JSON_CONTAINS(type_menu, " . $DB->quote('"' . addslashes($itemtype) . '"') . ")"),
            ],
        ];
        if ($obj->isEntityAssign()) {
            $where += getEntitiesRestrictCriteria('', '', '', true);
        }

        $available_tags = $tag->find($where, 'name');
        foreach ($available_tags as $tag_data) {
            $available_tags[$tag_data['id']] = $tag_data['name'];
            $available_tags_color[$tag_data['id']] = $tag_data['color'] ?: '#DDDDDD';
        }

        $selected_tags = [];
        $select_tags = $tag_item->find(['items_id' => $params['id'], 'itemtype' => $itemtype]);
        foreach ($select_tags as $tag_item_data) {
            $tag_data = $tag->getFromDB($tag_item_data['plugin_tag_tags_id']);
            if ($tag_data) {
                $selected_tags[] = $tag_item_data['plugin_tag_tags_id'];
            }
        }

        $rand = mt_rand();

        $token_creation = self::canCreate()
            ? "return { id: 'newtag_'+ params.term, text: params.term };"
            : "return null;";

        $can_update_all = count(array_filter($params['items_ids'], function ($value) use ($obj) {
            $obj->getFromDB($value);
            return !$obj->canUpdateItem();
        })) === 0;

        $readOnly = !$tag::canUpdate()
            || ($obj->isNewItem() && !$obj->canCreateItem())
            || (!$obj->isNewItem() && !$obj->canUpdateItem())
            || (!empty($params['items_ids']) && !$can_update_all);

        $can_create = self::canCreate();

        if (!$readOnly && $obj instanceof CommonITILObject && $obj->isClosed()) {
            $show_save_button = true;
            $url = plugin_tag_geturl() . '/ajax/tag.php';
        }

        if ($obj instanceof CommonITILTask) {
            $extra_class = "mb-3";
        }

        $tag_location = Config::getConfigurationValues('plugin:Tag')['tags_location'] ?? 0;

        TemplateRenderer::getInstance()->display('@tag/tag_dropdown.html.twig', [
            'extra_class'       => $extra_class ?? '',
            'selected_tags'     => $selected_tags,
            'available_tags'    => $available_tags,
            'tags_color'        => $available_tags_color ?? [],
            'rand'              => $rand,
            'token_creation'    => $token_creation,
            'readOnly'          => $readOnly,
            'can_create'        => $can_create,
            'show_save_button'  => $show_save_button ?? false,
            'url'               => $url ?? '',
            'itemtype'          => $itemtype,
            'icon'              => Computer::getIcon(),
            'items_id'          => $params['id'],
            'in_itilobject'     => $obj instanceof CommonITILObject,
            'is_dropdown'       => $obj instanceof CommonDropdown,
            'is_form'           => $obj instanceof Form,
            'is_new_item'       => $obj->isNewItem(),
            'tag_search_url'    => self::getSearchURL(),
            'tag_location'      => $tag_location,
        ]);
    }

    public static function getTagForEntityName($completename = "")
    {
        $plus_rootentity = sprintf(__s('%1$s + %2$s'), '', __s('Child entities'));
        $completename    = trim(str_replace($plus_rootentity, '', $completename));
        $entities_id     = Entity::getEntityIDByCompletename($completename);

        $out = "";
        if ($entities_id >= 0) {
            $tag_item = new PluginTagTagItem();
            foreach ($tag_item->find(['items_id' => $entities_id, 'itemtype' => 'Entity']) as $found_item) {
                $out .= PluginTagTag::getSingleTag($found_item['plugin_tag_tags_id']);
            }
        }

        return $out;
    }

    public static function getSingleTag($tag_id, $separator = '')
    {
        $plugintagtag = new self();
        $plugintagtag->getFromDB($tag_id);
        $color = $plugintagtag->fields["color"] ?: '#DDDDDD';
        $inv_color = idealTextColor($color);
        $style = "background-color: $color; color: $inv_color";

        return "<span class='select2-search-choice tag_choice'
                    style='padding-left:5px;$style'>" .
              $separator . $plugintagtag->fields['name'] . '</span>';
    }

    public function prepareInputForAdd($input)
    {
        if (!$this->checkMandatoryFields($input)) {
            return false;
        }

        return $this->encodeSubtypes($input);
    }

    public function prepareInputForUpdate($input)
    {
        if (!$this->checkMandatoryFields($input)) {
            return false;
        }

        return $this->encodeSubtypes($input);
    }

    /**
     * Encode sub types
     *
     * @param array $input
     */
    public function encodeSubtypes($input)
    {
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
    public function checkMandatoryFields($input = [])
    {
        $msg              = [];
        $checkKo          = false;
        $mandatory_fields = ['name' => __s('Name')];

        foreach ($input as $key => $value) {
            if (isset($mandatory_fields[$key]) && empty($value)) {
                $msg[]   = $mandatory_fields[$key];
                $checkKo = true;
            }
        }

        if ($checkKo) {
            Session::addMessageAfterRedirect(sprintf(__s("Mandatory fields are not filled. Please correct: %s"), implode(', ', $msg)), true, ERROR);
            return false;
        }
        return true;
    }

    /**
     * Retrieve the current itemtype from the current page url
     *
     * @return string|boolean false if not itemtype found, the string itemtype if found
     */
    public static function getCurrentItemtype()
    {
        $request_uri = $_SERVER['REQUEST_URI'] ?? ''; // $_SERVER['REQUEST_URI'] from CLI context is empty
        $itemtype = '';
        if (
            preg_match('/\/(?:marketplace|plugins)\/genericobject\/front\/object\.form.php/', $request_uri)
            && array_key_exists('itemtype', $_GET)
        ) {
            $itemtype = $_GET['itemtype'];
        } elseif (
            preg_match(
                '/\/(?:marketplace|plugins)\/([a-zA-Z]+)\/front\/([a-zA-Z]+).form.php/',
                $request_uri,
                $matches,
            )
        ) {
            $itemtype = 'Plugin' . ucfirst($matches[1]) . ucfirst($matches[2]);
        } elseif (preg_match('/([a-zA-Z]+).form.php/', $request_uri, $matches)) {
            $itemtype = $matches[1];
        } elseif (
            preg_match(
                '/\/(?:marketplace|plugins)\/([a-zA-Z]+)\/front\/([a-zA-Z]+).php/',
                $request_uri,
                $matches,
            )
        ) {
            $itemtype = 'Plugin' . ucfirst($matches[1]) . ucfirst($matches[2]);
        } elseif (preg_match('/([a-zA-Z]+).php/', $request_uri, $matches)) {
            $itemtype = $matches[1];
        }

        $item = getItemForItemtype($itemtype);
        if ($item instanceof CommonDBTM) {
            return $item->getType();
        }

        return false;
    }


    public static function getIcon()
    {
        return "fas fa-tags";
    }
}
