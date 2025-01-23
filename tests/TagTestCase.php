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

use Auth;
use PHPUnit\Framework\TestCase;
use Session;

abstract class TagTestCase extends TestCase
{
    protected function setUp(): void
    {
        global $DB;
        $DB->beginTransaction();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        global $DB;
        $DB->rollback();
        parent::tearDown();
    }

    protected function login(
        string $user_name = TU_USER,
        string $user_pass = TU_PASS,
        bool $noauto = true,
        bool $expected = true
    ): Auth {
        Session::destroy();
        Session::start();

        $auth = new Auth();
        $this->assertEquals($expected, $auth->login($user_name, $user_pass, $noauto));

        return $auth;
    }

    protected function logOut()
    {
        $ctime = $_SESSION['glpi_currenttime'];
        Session::destroy();
        $_SESSION['glpi_currenttime'] = $ctime;
    }
}
