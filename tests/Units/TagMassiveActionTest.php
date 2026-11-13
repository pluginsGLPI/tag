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

use Computer;
use GlpiPlugin\Tag\Tests\TagTestCase;
use PluginTagTag;
use Symfony\Component\DomCrawler\Crawler;

use function Safe\ob_get_clean;
use function Safe\ob_start;

final class TagMassiveActionTest extends TagTestCase
{
    public function testAssociatedItemTypesCanUseMultipleDropdown(): void
    {
        $html = PluginTagTag::getSpecificValueToSelect(
            'type_menu',
            'type_menu',
            ['type_menu' => []],
            ['multiple' => true],
        );

        $this->assertIsString($html);
        $this->assertStringContainsString('type_menu', $html);
        $this->assertStringContainsString('multiple', $html);
    }

    public function testAssociatedItemTypesRemainSingleByDefault(): void
    {
        $html = PluginTagTag::getSpecificValueToSelect(
            'type_menu',
            'type_menu',
            ['type_menu' => 'Ticket'],
        );

        $this->assertIsString($html);
        $this->assertStringContainsString('type_menu', $html);
        $this->assertStringNotContainsString('multiple="multiple"', $html);
    }

    public function testMassiveActionHookHandlesTypeMenu(): void
    {
        ob_start();

        $handled = plugin_tag_MassiveActionsFieldsDisplay([
            'itemtype' => PluginTagTag::class,
            'options' => [
                'field' => 'type_menu',
            ],
        ]);

        $html = ob_get_clean();

        $this->assertTrue($handled);
        $this->assertStringContainsString('type_menu', $html);
        $this->assertStringContainsString('multiple', $html);
    }

    public function testMassiveActionHookIgnoresOtherFields(): void
    {
        ob_start();

        $handled = plugin_tag_MassiveActionsFieldsDisplay([
            'itemtype' => PluginTagTag::class,
            'options' => [
                'field' => 'name',
            ],
        ]);

        $html = ob_get_clean();

        $this->assertFalse($handled);
        $this->assertSame('', $html);
    }

    public function testMassiveActionTagDropdownIsBuiltForTheSelectedItems(): void
    {
        $this->login();
        $this->createTag('MassiveActionTag', ['Computer']);
        $computer = $this->createItem(Computer::class, [
            'name'        => 'Computer updated through a massive action',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        ob_start();
        PluginTagTag::showTagDropdown([
            'itemtype'  => Computer::class,
            'items_ids' => [$computer->getID()],
        ]);
        $html = ob_get_clean();

        // The rights and the layout come from the selected items (update), not from an empty new item (create)
        $crawler = new Crawler($html);
        $select = $crawler->filter('select[name="_plugin_tag_tag_values[]"]');
        $this->assertCount(1, $select);
        $this->assertNull($select->attr('disabled'));
        $this->assertCount(1, $crawler->filter('div.col-xxl-9'));
        $this->assertCount(0, $crawler->filter('div.col-xxl-12'));
    }
}
