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

use Glpi\Exception\Http\BadRequestHttpException;

use function Safe\json_encode;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
Session::checkLoginUser();
header('Content-Type: application/json');

if (isset($_POST['itemtype'], $_POST['items_id'])) {
    $itemType = $_POST['itemtype'];
    $itemId = $_POST['items_id'];

    if (!is_a($itemType, CommonDBTM::class, true)) {
        throw new BadRequestHttpException(__s('Invalid item type', 'tag'));
    }

    $obj = new $itemType();
    $obj->getFromDB($itemId);
    $obj->input = $_POST;
    $success = PluginTagTagItem::updateItem($obj);

    if ($success) {
        Session::addMessageAfterRedirect(
            __s('Tags have been updated', 'tag'),
            false,
            INFO,
        );
        echo json_encode(['success' => true]);
    } else {
        throw new BadRequestHttpException(__s('Tags have not been updated', 'tag'));
    }
} else {
    throw new BadRequestHttpException(__s('Missing parameters', 'tag'));
}
