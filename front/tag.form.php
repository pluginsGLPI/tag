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

include('../../../inc/includes.php');

Session::checkRight(PluginTagTag::$rightname, UPDATE);

if (!Plugin::isPluginActive("tag")) {
    Html::displayNotFoundError();
}

if (isset($_POST['add'])) {
    $item = new PluginTagTagItem();

    // Check unicity :
    if (isset($_REQUEST['plugin_tag_tags_id'])) {
        $found = $item->find([
            'plugin_tag_tags_id' => $_REQUEST['plugin_tag_tags_id'],
            'items_id' => $_REQUEST['items_id'],
            'itemtype' => $_REQUEST['itemtype'],
        ]);

        if (count($found) == 0) {
            $item->add($_REQUEST);
        }
    } else {
        $item->add($_REQUEST);
    }
}

$dropdown = new PluginTagTag();
include(GLPI_ROOT . "/front/dropdown.common.form.php");
