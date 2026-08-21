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
 * @copyright Copyright (C) 2014-2026 by Teclib'.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/tag
 * -------------------------------------------------------------------------
 */


Session::checkLoginUser();

if (!isset($_POST['plugin_tag_tags_id'], $_POST['itemtype'], $_POST['items_id'])) {
    http_response_code(400);
    exit;
}

$tag = new PluginTagTag();
if (!$tag->getFromDB($_POST['plugin_tag_tags_id']) || !$tag->can($tag->getID(), UPDATE)) {
    http_response_code(403);
    exit;
}

$itemtype = $_POST['itemtype'];
if (!is_a($itemtype, CommonDBTM::class, true) || !PluginTagTag::canItemtype($itemtype)) {
    http_response_code(400);
    exit;
}

$item = new $itemtype();
if (!$item->getFromDB($_POST['items_id']) || !$item->canUpdateItem()) {
    http_response_code(403);
    exit;
}

$tag_item = new PluginTagTagItem();
$found = $tag_item->find([
    'plugin_tag_tags_id' => $tag->getID(),
    'items_id'           => $item->getID(),
    'itemtype'           => $itemtype,
]);

if (count($found) === 0) {
    $tag_item->add([
        'plugin_tag_tags_id' => $tag->getID(),
        'items_id'           => $item->getID(),
        'itemtype'           => $itemtype,
    ]);
}

Html::back();
