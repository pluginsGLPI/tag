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

namespace GlpiPlugin\Tag\Tests;

use CommonDBTM;
use DbTestCase;
use PluginTagTag;
use PluginTagTagItem;
use Profile_User;
use Session;

abstract class TagTestCase extends DbTestCase
{
    protected function logOut()
    {
        $ctime = $_SESSION['glpi_currenttime'];
        Session::destroy();
        $_SESSION['glpi_currenttime'] = $ctime;
    }

    public function loginAs(array $credentials): int
    {
        global $DB;

        $login = $credentials['login'];
        $pass  = $credentials['pass'];
        $user  = getItemByTypeName('User', $login);
        $user_profile = Profile_User::getUserProfiles($user->getID());
        $user_profile = array_keys($user_profile)[0];

        $DB->update(
            'glpi_profilerights',
            [
                'rights' => CREATE | UPDATE | PURGE,
            ],
            [
                'profiles_id' => $user_profile,
                'name'        => PluginTagTag::$rightname,
            ],
        );

        $this->login($login, $pass);

        return $user->getID();
    }

    public function createTag(string $tagName): int
    {
        $tag = new PluginTagTag();
        $tag->add(
            [
                'name' => $tagName,
                'is_active' => 1,
                'type_menu' => ['Ticket'],
            ],
        );
        $this->assertGreaterThan(0, $tag->getID());

        return $tag->getID();
    }

    public function isItemTagged(CommonDBTM $item, int $tagID)
    {
        $tagItem = new PluginTagTagItem();
        $ticketTag = $tagItem->getFromDBForItems(PluginTagTag::getById($tagID), $item);

        $this->assertTrue($ticketTag);
    }

    public function isItemNotTagged(CommonDBTM $item, int $tagID): void
    {
        $tagItem = new PluginTagTagItem();
        $ticketTag = $tagItem->getFromDBForItems(PluginTagTag::getById($tagID), $item);

        $this->assertFalse($ticketTag);
    }
}
