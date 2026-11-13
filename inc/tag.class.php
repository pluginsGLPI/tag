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
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\DBAL\QueryExpression;
use Glpi\Form\Form;
use Glpi\Message\MessageType;

use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\preg_match;

class PluginTagTag extends CommonDropdown
{
    // From CommonDBTM
    public $dohistory = true;

    public const S_OPTION = 10500;

    public static $rightname = 'plugin_tag_tag';

    /**
     * Itemtypes of the active tags, memoized for the current request.
     *
     * @var array{use_global_tag: bool, itemtypes: array<string, true>}|null
     */
    private static ?array $active_tags_itemtypes = null;

    /**
     * Items whose form already displays the tags field in the current request.
     *
     * @var WeakMap<CommonDBTM, true>|null
     */
    private static ?WeakMap $displayed_items = null;

    /**
     * Colors of the tags available in the tags field, by search criteria (itemtype and entity restriction), memoized for the current request.
     *
     * @var array<string, array<int, string>>
     */
    private static array $available_tags_colors = [];

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
            'TicketSatisfaction',
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

        $active_itemtypes = self::getActiveTagsItemtypes();

        return $active_itemtypes['use_global_tag'] || isset($active_itemtypes['itemtypes'][$itemtype]);
    }

    /**
     * Get the itemtypes the active tags apply to, read once per request.
     *
     * @return array{use_global_tag: bool, itemtypes: array<string, true>}
     */
    private static function getActiveTagsItemtypes(): array
    {
        /** @var DBmysql $DB */
        global $DB;

        if (self::$active_tags_itemtypes !== null) {
            return self::$active_tags_itemtypes;
        }

        $itemtypes      = [];
        $use_global_tag = false;

        $iterator = $DB->request([
            'SELECT' => ['type_menu'],
            'FROM'   => self::getTable(),
            'WHERE'  => ['is_active' => 1],
        ]);
        foreach ($iterator as $tag) {
            if (empty($tag['type_menu'])) {
                $use_global_tag = true;
                continue;
            }

            foreach ((array) json_decode((string) $tag['type_menu'], true) as $tag_itemtype) {
                // Only itemtype names: nested or malformed values can never match an itemtype
                if (is_scalar($tag_itemtype)) {
                    $itemtypes[(string) $tag_itemtype] = true;
                }
            }
        }

        return self::$active_tags_itemtypes = [
            'use_global_tag' => $use_global_tag,
            'itemtypes'      => $itemtypes,
        ];
    }

    /**
     * Forget the itemtypes of the active tags and the tags available in the tags fields.
     *
     * Called after a tag is added, updated or purged through PluginTagTag; code writing
     * `glpi_plugin_tag_tags` directly (migration, tests rolling back a transaction) must call it.
     */
    public static function resetActiveTagsItemtypes(): void
    {
        self::$active_tags_itemtypes = null;
        self::$available_tags_colors = [];
    }

    public function post_addItem()
    {
        self::resetActiveTagsItemtypes();
        parent::post_addItem();
    }

    public function post_updateItem($history = true)
    {
        self::resetActiveTagsItemtypes();
        parent::post_updateItem($history);
    }

    public function post_purgeItem()
    {
        self::resetActiveTagsItemtypes();
        parent::post_purgeItem();
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

        $type_menu_values = empty($this->fields['type_menu']) ? [] : json_decode((string) $this->fields['type_menu']);

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
                CREATE TABLE IF NOT EXISTS `{$table}` (
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
        if (stristr((string) $fields['type_menu']["Type"], 'varchar') !== false) {
            $migration->changeField($table, 'type_menu', 'type_menu', 'text');
            $migration->dropKey($table, 'type_menu');
            $migration->migrationOneTable($table);

            $datas = getAllDataFromTable($table, ['NOT' => ['type_menu' => null]]);
            if (!empty($datas)) {
                foreach ($datas as $data) {
                    $itemtypes = $CFG_GLPI['plugin_tag_itemtypes'][$data['type_menu']] ?? [];
                    $DB->update($table, ['type_menu' => json_encode($itemtypes)], ['id' => $data['id']]);
                }
                self::resetActiveTagsItemtypes();
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

        $profileRight = new ProfileRight();
        $profileRight->deleteByCriteria(['name' => self::$rightname]);

        $migration = new Migration(PLUGIN_TAG_VERSION);
        $migration->dropTable(getTableForItemType(self::class));

        self::resetActiveTagsItemtypes();

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
        if ($item instanceof self && $tabnum === 2) {
            $tagitem = new PluginTagTagItem();
            $tagitem->showForTag($item);
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

        $tab = [];
        $iterator = $DB->request([
            'SELECT'    => [
                'itemtype_peripheral',
                'items_id_peripheral',
            ],
            'FROM'      => getTableForItemType(Asset_PeripheralAsset::class),
            'WHERE'     => [
                'itemtype_asset'                => Computer::class,
                'items_id_asset'                => $this->getID(),
            ],
        ]);

        foreach ($iterator as $data) {
            $tab[$data['itemtype_peripheral']][$data['items_id_peripheral']] = $data['items_id_peripheral'];
        }

        return $tab;
    }

    // for massive actions
    public function haveChildren()
    {
        $tagitems = new PluginTagTagItem();
        $data = $tagitems->find(['plugin_tag_tags_id' => $this->getID()]);
        return count($data) !== 0;
    }

    public function rawSearchOptions()
    {
        return [[
            'id'            => 'common',
            'name'          => __s('Characteristics'),
        ], [
            'id'            => 1,
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => __s('Name'),
            'datatype'      => 'itemlink',
            'massiveaction' => true,
        ], [
            'id'            => 2,
            'table'         => $this->getTable(),
            'field'         => 'comment',
            'name'          => __s('Description'),
            'datatype'      => 'string',
            'massiveaction' => true,
        ], [
            'id'            => 3,
            'table'         => $this->getTable(),
            'field'         => 'id',
            'name'          => __s('ID'),
            'datatype'      => 'number',
            'massiveaction' => false,
        ], [
            'id'            => 4,
            'table'         => 'glpi_entities',
            'field'         => 'completename',
            'linkfield'     => 'entities_id',
            'name'          => __s('Entity'),
            'datatype'      => 'dropdown',
        ], [
            'id'            => 5,
            'table'         => $this->getTable(),
            'field'         => 'is_recursive',
            'name'          => __s('Child entities'),
            'datatype'      => 'bool',
        ], [
            'id'            => 6,
            'table'         => $this->getTable(),
            'field'         => 'type_menu',
            'name'          => _sn('Associated item type', 'Associated item types', 2),
            'searchtype'    => ['equals', 'notequals'],
            'datatype'      => 'specific',
        ], [
            'id'            => 7,
            'table'         => $this->getTable(),
            'field'         => 'color',
            'name'          => __s('HTML color', 'tag'),
            'searchtype'    => 'contains',
            'datatype'      => 'specific',
        ], [
            'id'            => 8,
            'table'         => $this->getTable(),
            'field'         => 'is_active',
            'name'          => __s('Active'),
            'datatype'      => 'bool',
        ]];
    }

    public static function getSpecificValueToSelect(
        $field,
        $name = '',
        $values = '',
        array $options = [],
    ) {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        if ($field === 'type_menu') {
            $multiple = (bool) ($options['multiple'] ?? false);

            // Do not add an empty option to a multiple dropdown.
            $elements = $multiple
                ? []
                : ['' => Dropdown::EMPTY_VALUE];

            $supported_itemtypes = $CFG_GLPI['plugin_tag_itemtypes'] ?? [];

            foreach ($supported_itemtypes as $itemtypes) {
                foreach ($itemtypes as $itemtype) {
                    $item = getItemForItemtype($itemtype);
                    $elements[$itemtype] = $item::getTypeName();
                }
            }

            $selected = $values[$field] ?? ($multiple ? [] : '');

            if ($multiple) {
                if (!is_array($selected)) {
                    $selected = $selected === ''
                        ? []
                        : [$selected];
                }

                return Dropdown::showFromArray(
                    $name,
                    $elements,
                    [
                        'display'  => false,
                        'multiple' => true,
                        'values'   => array_values($selected),
                    ],
                );
            }

            // Preserve the original single-select behavior.
            if (is_array($selected)) {
                $selected = reset($selected) ?: '';
            }

            return Dropdown::showFromArray(
                $name,
                $elements,
                [
                    'display' => false,
                    'value'   => $selected,
                ],
            );
        }

        return parent::getSpecificValueToSelect(
            $field,
            $name,
            $values,
            $options,
        );
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        switch ($field) {
            case 'type_menu':
                $itemtypes = empty($values[$field]) ? [] : json_decode((string) $values[$field], true);
                if (!is_array($itemtypes)) {
                    return "&nbsp;";
                }

                $itemtype_names = [];
                foreach ($itemtypes as $itemtype) {
                    $item = getItemForItemtype($itemtype);
                    $itemtype_names[] = $item->getTypeName();
                }

                return implode(", ", $itemtype_names);
            case 'color':
                $color = $values[$field] ?: '#DDDDDD';
                return sprintf(
                    "<div style='background-color: %s;'>&nbsp;</div>",
                    htmlspecialchars((string) $color, ENT_QUOTES, 'UTF-8'),
                );
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
     * @param  array     $params should contains theses keys:
     *                             - item the CommonDBTM object
     * @param  bool|null $at_bottom true or false to display the field only when it is configured at the bottom
     *                             or at the top of the forms, null to display it whatever the configuration
     * @return bool|null False if the form was not shown. Otherwise nothing is returned and the form is displayed.
     */
    public static function showForItem($params = [], ?bool $at_bottom = null)
    {
        if (!self::canView()) {
            return false;
        }

        if (
            isset($params['item'])
            && $params['item'] instanceof CommonDBTM
        ) {
            $item     = $params['item'];
            $itemtype = $item::class;

            if (!self::canItemtype($itemtype)) {
                return null;
            }

            // Read the configured location only for forms that can show tags
            if ($at_bottom !== null && (PluginTagConfig::getTagsLocation() === '1') !== $at_bottom) {
                return null;
            }

            // A form calling the same hook twice (core template branches, other plugins)
            // must not get a second tags field with the same input name
            self::$displayed_items ??= new WeakMap();
            if (isset(self::$displayed_items[$item])) {
                return null;
            }

            self::$displayed_items[$item] = true;

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

        return null;
    }

    /**
     * `pre_item_form` hook: display the tags field when it is configured at the top of the forms.
     *
     * @param  array $params see showForItem()
     * @return bool|null
     */
    public static function showForItemAtTop($params = [])
    {
        return self::showForItem($params, false);
    }

    /**
     * `post_item_form` hook: display the tags field when it is configured at the bottom of the forms.
     *
     * @param  array $params see showForItem()
     * @return bool|null
     */
    public static function showForItemAtBottom($params = [])
    {
        return self::showForItem($params, true);
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
                $title = htmlentities((string) $data['comment'], ENT_QUOTES, "UTF-8");
                $name = $data['name'];
                $color = $data['color'] ?: '#DDDDDD';
                $textcolor = idealTextColor($color);
                $style = sprintf('background-color: %s; color: %s;', htmlentities((string) $color, ENT_QUOTES, "UTF-8"), $textcolor);
                $content .= sprintf("<span class='tag_choice' style='%s' title='%s'>%s</span>&nbsp;&nbsp;", $style, $title, htmlentities((string) $name, ENT_QUOTES, "UTF-8"));
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
        if (!is_a($itemtype, CommonDBTM::class, true)) {
            return;
        }

        $obj = new $itemtype();

        $tag = new self();

        if (isset($params['id'])) {
            $obj->getFromDB($params['id']);
        }

        if ($obj->isNewItem()) {
            $obj->getEmpty();
        }

        /** @var DBmysql $DB */
        global $DB;

        $where = [
            'is_active' => 1,
            'OR' => [
                ['type_menu' => null],
                ['type_menu' => ''],
                ['type_menu' => 0],
                new QueryExpression("JSON_CONTAINS(type_menu, " . $DB->quote('"' . addslashes((string) $itemtype) . '"') . ")"),
            ],
        ];
        if ($obj->isEntityAssign()) {
            $where += getEntitiesRestrictCriteria('', '', '', true);
        }

        // Several tags fields with the same criteria on one page (item and sub-forms) share the same list
        $available_tags_key = md5(serialize($where));
        if (!isset(self::$available_tags_colors[$available_tags_key])) {
            self::$available_tags_colors[$available_tags_key] = [];
            foreach ($tag->find($where, 'name') as $tag_data) {
                self::$available_tags_colors[$available_tags_key][$tag_data['id']] = $tag_data['color'] ?: '#DDDDDD';
            }
        }
        $available_tags_color = self::$available_tags_colors[$available_tags_key];

        // Tags linked to the item, ignoring links to tags that no longer exist, in one query (none for a new item)
        $selected_tags = [];
        if (!$obj->isNewItem()) {
            $selected_iterator = $DB->request([
                'SELECT'     => [PluginTagTagItem::getTable() . '.plugin_tag_tags_id'],
                'FROM'       => PluginTagTagItem::getTable(),
                'INNER JOIN' => [
                    self::getTable() => [
                        'FKEY' => [
                            self::getTable()             => 'id',
                            PluginTagTagItem::getTable() => 'plugin_tag_tags_id',
                        ],
                    ],
                ],
                'WHERE'      => [
                    PluginTagTagItem::getTable() . '.items_id' => $params['id'],
                    PluginTagTagItem::getTable() . '.itemtype' => $itemtype,
                ],
                'ORDERBY'    => PluginTagTagItem::getTable() . '.id',
            ]);
            foreach ($selected_iterator as $tag_item_data) {
                $selected_tags[] = $tag_item_data['plugin_tag_tags_id'];
            }
        }

        $rand = mt_rand();

        // Massive action: stop at the first selected item that cannot be updated
        $can_update_all = true;
        foreach ($params['items_ids'] as $selected_id) {
            $obj->getFromDB($selected_id);
            if (!$obj->canUpdateItem()) {
                $can_update_all = false;
                break;
            }
        }

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

        TemplateRenderer::getInstance()->display('@tag/tag_dropdown.html.twig', [
            'extra_class'       => $extra_class ?? '',
            'selected_tags'     => $selected_tags,
            'condition'         => $where,
            'tags_color'        => $available_tags_color,
            'rand'              => $rand,
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
        ]);
    }

    public static function getTagForEntityName($completename = "")
    {
        $plus_rootentity = sprintf(__s('%1$s + %2$s'), '', __s('Child entities'));
        $completename    = trim(str_replace($plus_rootentity, '', $completename));
        $entities_id     = Entity::getEntityIDByCompletename($completename);

        $out = "";
        if ($entities_id >= 0) {
            /** @var DBmysql $DB */
            global $DB;

            // Tags of the entity with their name and color, in one query
            $iterator = $DB->request([
                'SELECT'     => [self::getTable() . '.name', self::getTable() . '.color'],
                'FROM'       => PluginTagTagItem::getTable(),
                'INNER JOIN' => [
                    self::getTable() => [
                        'FKEY' => [
                            self::getTable()             => 'id',
                            PluginTagTagItem::getTable() => 'plugin_tag_tags_id',
                        ],
                    ],
                ],
                'WHERE'      => [
                    PluginTagTagItem::getTable() . '.items_id' => $entities_id,
                    PluginTagTagItem::getTable() . '.itemtype' => 'Entity',
                ],
                'ORDERBY'    => PluginTagTagItem::getTable() . '.id',
            ]);
            foreach ($iterator as $tag_data) {
                $out .= self::renderSingleTag($tag_data);
            }
        }

        return $out;
    }

    /**
     * Render a tag badge.
     *
     * @param array $tag_data tag fields, at least `name` and `color`
     *
     * @return string
     */
    private static function renderSingleTag(array $tag_data): string
    {
        $color = ($tag_data['color'] ?? '') ?: '#DDDDDD';
        $inv_color = idealTextColor($color);
        $style = sprintf('background-color: %s; color: %s', $color, $inv_color);

        return "<span class='select2-search-choice tag_choice'
                    style='padding-left:5px;{$style}'>"
              . htmlentities((string) ($tag_data['name'] ?? ''), ENT_QUOTES, "UTF-8") . '</span>';
    }

    public function prepareInputForAdd($input)
    {
        if (!$this->checkMandatoryFields($input) || !$this->checkColorField($input)) {
            return false;
        }

        return $this->encodeSubtypes($input);
    }

    public function prepareInputForUpdate($input)
    {
        if (!$this->checkMandatoryFields($input) || !$this->checkColorField($input)) {
            return false;
        }

        return $this->encodeSubtypes($input);
    }

    /**
     * Check that the color field, if provided, is a valid hexadecimal color
     *
     * @param array $input
     * @return boolean
     */
    public function checkColorField($input = [])
    {
        if (!empty($input['color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $input['color'])) {
            Session::addMessageAfterRedirect(__s('Invalid color format', 'tag'), true, ERROR);
            return false;
        }

        return true;
    }

    /**
     * Encode sub types
     *
     * @param array $input
     */
    public function encodeSubtypes($input)
    {
        if (!empty($input['type_menu'])) {
            $type_menu = $input['type_menu'];

            if (!is_array($type_menu)) {
                $type_menu = [$type_menu];
            }

            $input['type_menu'] = json_encode(array_values($type_menu));
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
            preg_match('/\/(?:marketplace|plugins)\/genericobject\/front\/object\.form.php/', (string) $request_uri)
            && array_key_exists('itemtype', $_GET)
        ) {
            $itemtype = $_GET['itemtype'];
        } elseif (
            preg_match(
                '/\/(?:marketplace|plugins)\/([a-zA-Z]+)\/front\/([a-zA-Z]+).form.php/',
                (string) $request_uri,
                $matches,
            ) !== 0
        ) {
            $itemtype = 'Plugin' . ucfirst($matches[1]) . ucfirst($matches[2]);
        } elseif (preg_match('/([a-zA-Z]+).form.php/', (string) $request_uri, $matches) !== 0) {
            $itemtype = $matches[1];
        } elseif (
            preg_match(
                '/\/(?:marketplace|plugins)\/([a-zA-Z]+)\/front\/([a-zA-Z]+).php/',
                (string) $request_uri,
                $matches,
            ) !== 0
        ) {
            $itemtype = 'Plugin' . ucfirst($matches[1]) . ucfirst($matches[2]);
        } elseif (preg_match('/([a-zA-Z]+).php/', (string) $request_uri, $matches) !== 0) {
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
