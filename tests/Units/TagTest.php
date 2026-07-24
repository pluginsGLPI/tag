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

namespace GlpiPlugin\Tag\Tests\Units;

use DBmysql;
use GlpiPlugin\Tag\Tests\TagTestCase;
use PluginTagTag;

final class TagTest extends TagTestCase
{
    public function testCheckColorFieldAcceptsValidOrEmptyColor(): void
    {
        $tag = new PluginTagTag();

        $this->assertTrue($tag->checkColorField(['color' => '#FFAA00']));
        $this->assertTrue($tag->checkColorField(['color' => '']));
        $this->assertTrue($tag->checkColorField([]));
    }

    public function testCheckColorFieldRejectsInvalidColor(): void
    {
        $tag = new PluginTagTag();

        $this->assertFalse($tag->checkColorField(['color' => 'red']));
        $this->assertFalse($tag->checkColorField(['color' => '#FFF']));
        $this->assertFalse($tag->checkColorField(['color' => '"><script>alert(1)</script>']));

        $this->hasSessionMessages(ERROR, ['Invalid color format']);
    }

    public function testGetSpecificValueToDisplayEscapesColor(): void
    {
        $malicious = '"><script>alert(1)</script>';

        $output = PluginTagTag::getSpecificValueToDisplay('color', ['color' => $malicious]);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString(htmlspecialchars($malicious, ENT_QUOTES, 'UTF-8'), $output);
    }

    public function testAddWhereEscapesValue(): void
    {
        /** @var DBmysql $DB */
        global $DB;

        $val = '" OR "1"="1';

        $where = plugin_tag_addWhere('AND', false, 'PluginTagTag', 6, $val, 'equals');

        $this->assertSame(
            sprintf("`glpi_plugin_tag_tags`.`type_menu` LIKE %s", $DB->quote('%"' . $val . '"%')),
            $where,
        );
    }

    public function testAddHavingEscapesValue(): void
    {
        /** @var DBmysql $DB */
        global $DB;

        $val = 'Ticket" OR "1"="1';

        $having = plugin_tag_addHaving('AND', false, 'PluginTagTag', 6, $val, 0);

        $this->assertSame(
            sprintf("AND `ITEM_0` LIKE %s", $DB->quote('%' . $val . '%')),
            $having,
        );
    }

    public function testUpdateAcceptsScalarTypeMenu(): void
    {
        $tag = $this->createItem(PluginTagTag::class, [
            'name' => 'Massive update test',
            'is_active' => 1,
            'type_menu' => ['Ticket', 'Problem'],
        ]);

        $tag = $this->updateItem(
            PluginTagTag::class,
            $tag->getID(),
            ['type_menu' => 'Ticket'],
            ['type_menu'],
        );

        $this->assertSame('["Ticket"]', $tag->fields['type_menu']);
    }
}
