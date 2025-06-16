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

// Plugin hook after *Uninstall*
function plugin_uninstall_after_tag($item)
{
    $tagitem = new PluginTagTagItem();
    $tagitem->deleteByCriteria([
        'itemtype' => $item->getType(),
        'items_id' => $item->getID(),
    ]);
}

function plugin_datainjection_populate_tag()
{
    /** @var array $INJECTABLE_TYPES */
    global $INJECTABLE_TYPES;

    $INJECTABLE_TYPES['PluginTagTagInjection'] = 'tag';
}

function plugin_tag_getAddSearchOptionsNew($itemtype)
{
    if (!PluginTagTag::canView() || !PluginTagTag::canItemtype($itemtype)) {
        return [];
    }

    $glpiVersion = new Plugin();
    $glpiVersion = $glpiVersion->getGlpiVersion();

    $so_param = [
        'id'            => PluginTagTag::S_OPTION,
        'table'         => PluginTagTag::getTable(),
        'field'         => 'name',
        'name'          => PluginTagTag::getTypeName(2),
        'datatype'      => 'dropdown',
        'searchtype'    => ['equals','notequals','contains'],
        'massiveaction' => false,
        'forcegroupby'  => true,
        'joinparams'    =>  [
            'beforejoin' => [
                'table'      => 'glpi_plugin_tag_tagitems',
                'joinparams' => [
                    'jointype' => 'itemtype_item',
                ],
            ],
        ],
    ];

    if (version_compare($glpiVersion, "10.0.19", '>=')) {
        $so_param['use_subquery'] = true;
        $so_param['joinparams']['beforejoin']['joinparams']['field'] = 'items_id';
    }

    $options[] = $so_param;

    if ($itemtype != 'AllAssets') {
        $item = new $itemtype();
        if ($item->isEntityAssign()) {
            $options [] = [
                'id'            => (PluginTagTag::S_OPTION + 1),
                'table'         => PluginTagTag::getTable(),
                'field'         => 'name',
                'name'          => PluginTagTag::getTypeName(2) . " - " . __("Entity"),
                'datatype'      => 'string',
                'searchtype'    => 'contains',
                'massiveaction' => false,
                'forcegroupby'  => true,
                'usehaving'     => true,
                'joinparams'    =>  [
                    'condition'  => "AND 1=1", // to force distinct complex id than the previous option
                    'beforejoin' => [
                        'table'      => 'glpi_plugin_tag_tagitems',
                        'joinparams' => [
                            'jointype'          => 'itemtype_item',
                            'specific_itemtype' => 'Entity',
                            'beforejoin' => [
                                'table' => 'glpi_entities',
                            ],
                        ],
                    ],
                ],
            ];
        }
    }

    return $options;
}

function plugin_tag_giveItem($type, $field, $data, $num, $linkfield = "")
{
    switch ($field) {
        case PluginTagTag::S_OPTION:
        case PluginTagTag::S_OPTION + 1:
            $out = '<div class="tag_select select2-container" style="width: 100%;">
                 <div class="select2-choices no-negative-margin">';
            $separator = '';
            foreach ($data[$num] as $tag) {
                if (isset($tag['id']) && isset($tag['name'])) {
                    $out .= PluginTagTag::getSingleTag($tag['id'], $separator);
                    //For export (CSV, PDF) of GLPI core
                    $separator = '<span style="display:none">, </span>';
                }
            }
            $out .= '</div></div>';
            return $out;
    }

    return "";
}


function plugin_tag_addHaving($link, $nott, $itemtype, $id, $val, $num)
{
    $searchopt = &Search::getOptions($itemtype);
    $table     = $searchopt[$id]["table"];
    $field     = $searchopt[$id]["field"];

    if ($table . "." . $field == "glpi_plugin_tag_tags.type_menu") {
        $values = explode(",", $val);
        $where  = "$link `ITEM_$num` LIKE '%" . $values[0] . "%'";
        array_shift($values);
        foreach ($values as $value) {
            $value = trim($value);
            $where .= " OR `ITEM_$num` LIKE '%$value%'";
        }
        return $where;
    }
}

function plugin_tag_addWhere($link, $nott, $itemtype, $id, $val, $searchtype)
{
    $searchopt = &Search::getOptions($itemtype);
    $table     = $searchopt[$id]["table"];
    $field     = $searchopt[$id]["field"];

    if ($table . "." . $field == "glpi_plugin_tag_tags.type_menu") {
        switch ($searchtype) {
            case 'equals':
                return "`glpi_plugin_tag_tags`.`type_menu` LIKE '%\"$val\"%'";

            case 'notequals':
                return "`glpi_plugin_tag_tags`.`type_menu` NOT LIKE '%\"$val\"%'";
        }
    }

    return "";
}


/**
 * Define Dropdown managed in GLPI
 *
 * @return  array the list of dropdowns (label => class)
 */
function plugin_tag_getDropdown()
{
    return ['PluginTagTag' => PluginTagTag::getTypeName(2)];
}

/**
 * Define massive actions for other itemtype
 *
 * @param  string $itemtype
 * @return array the massive action list
 */
function plugin_tag_MassiveActions($itemtype = '')
{
    if (PluginTagTag::canItemtype($itemtype) && is_a($itemtype, CommonDBTM::class, true) && $itemtype::canUpdate()) {
        return [
            'PluginTagTagItem' . MassiveAction::CLASS_ACTION_SEPARATOR . 'addTag'
               => __("Add tags", 'tag'),
            'PluginTagTagItem' . MassiveAction::CLASS_ACTION_SEPARATOR . 'removeTag'
               => __("Remove tags", 'tag'),
        ];
    }

    return [];
}

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_tag_install()
{
    $version   = plugin_version_tag();
    $migration = new Migration($version['version']);

    // Parse inc directory
    foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
        // Load *.class.php files and get the class name
        if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
            $classname = 'PluginTag' . ucfirst($matches[1]);

            // Don't load Datainjection mapping lass (no install + bug if datainjection is not installed and activated)
            if ($classname == 'PluginTagTaginjection') {
                continue;
            }

            include_once($filepath);
            // If the install method exists, load it
            if (method_exists($classname, 'install')) {
                $classname::install($migration);
            }
        }
    }
    return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_tag_uninstall()
{
    // Parse inc directory
    foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
        // Load *.class.php files and get the class name
        if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
            $classname = 'PluginTag' . ucfirst($matches[1]);

            // Don't load Datainjection mapping lass (no uninstall + bug if datainjection is not installed and activated)
            if ($classname == 'PluginTagTaginjection') {
                continue;
            }

            include_once($filepath);
            // If the uninstall method exists, load it
            if (method_exists($classname, 'uninstall')) {
                $classname::uninstall();
            }
        }
    }
    return true;
}

function plugin_tag_post_init()
{
    /** @var array $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS;

    // hook on object changes
    if ($itemtype = PluginTagTag::getCurrentItemtype()) {
        if (PluginTagTag::canItemtype($itemtype)) {
            $PLUGIN_HOOKS['item_add']['tag'][$itemtype]        = ['PluginTagTagItem', 'updateItem'];
            $PLUGIN_HOOKS['item_update']['tag'][$itemtype]     = ['PluginTagTagItem', 'updateItem'];
            $PLUGIN_HOOKS['pre_item_update']['tag'][$itemtype]     = ['PluginTagTagItem', 'updateItem'];
            $PLUGIN_HOOKS['pre_item_purge']['tag'][$itemtype]  = ['PluginTagTagItem', 'purgeItem'];
        }
    }

    // Always define hook for tickets
    // Needed for rules to function properly when a ticket is created from a mail
    // collector
    $PLUGIN_HOOKS['item_add']['tag'][Ticket::getType()]        = ['PluginTagTagItem', 'updateItem'];
    $PLUGIN_HOOKS['item_update']['tag'][Ticket::getType()]     = ['PluginTagTagItem', 'updateItem'];
    $PLUGIN_HOOKS['pre_item_update']['tag'][Ticket::getType()]     = ['PluginTagTagItem', 'updateItem'];
    $PLUGIN_HOOKS['pre_item_purge']['tag'][Ticket::getType()]  = ['PluginTagTagItem', 'purgeItem'];

    $PLUGIN_HOOKS['rule_matched']['tag'] = 'plugin_tag_rule_matched';
}

function plugin_tag_rule_matched($params = [])
{
    // Ensure tags are added when only actors are updated, as actor updates are processed in post_add
    if (
        $params['sub_type'] == \RuleTicket::class
        && !empty($params['output'])
        && isset($params['output']['id'])
        && (
            isset($params['output']["_plugin_tag_tag_from_rules"])
            || isset($params['output']["_additional_tags_from_rules"])
        )
    ) {
        $ticket = new Ticket();
        if ($ticket->getFromDB($params['output']['id'])) {
            $ticket->input = array_merge($ticket->input, $params['output']);
            PluginTagTagItem::updateItem($ticket);
        }
    }
}

function plugin_tag_getRuleActions($params = [])
{
    $actions = [];

    switch ($params['rule_itemtype']) {
        case "RuleTicket":
            $actions['_plugin_tag_tag_from_rules'] = [
                'name'  => __("Add tags", 'tag'),
                'type'  => 'dropdown',
                'table' => PluginTagTag::getTable(),
                'force_actions' => ['assign', 'append'],
                'appendto' => '_additional_tags_from_rules',
                'condition' => ['type_menu' => ['LIKE', '%\"Ticket\"%']],
            ];

            break;
    }

    return $actions;
}
