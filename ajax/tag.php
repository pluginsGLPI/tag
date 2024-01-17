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

include("../../../inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkLoginUser();
header('Content-Type: application/json');

if (isset($_POST['plugin_tag_tags_id'], $_POST['itemtype'], $_POST['items_id'], $_POST['action'])) {
    $tagId = $_POST['plugin_tag_tags_id'];
    $itemType = $_POST['itemtype'];
    $itemId = $_POST['items_id'];
    $action = $_POST['action'];

    if ($action === 'add') {
        $tagItem = new PluginTagTagItem();
        $tagItem->add([
            'plugin_tag_tags_id' => $tagId,
            'itemtype' => $itemType,
            'items_id' => $itemId
        ]);
        Session::addMessageAfterRedirect(
            __('Tag has been added'),
            false,
            INFO
        );
        echo json_encode(['success' => true]);
    } elseif ($action === 'delete') {
        $tagItem = new PluginTagTagItem();
        $tagItem->deleteByCriteria([
            'plugin_tag_tags_id' => $tagId,
            'itemtype' => $itemType,
            'items_id' => $itemId
        ]);
        Session::addMessageAfterRedirect(
            __('Tag has been removed'),
            false,
            INFO
        );
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        Session::addMessageAfterRedirect(
            __('Invalid action'),
            false,
            INFO
        );
        echo json_encode(['success' => false]);
    }
} else {
    http_response_code(400);
    Session::addMessageAfterRedirect(
        __('Missing parameters'),
        false,
        ERROR
    );
    echo json_encode(['success' => false]);
}
