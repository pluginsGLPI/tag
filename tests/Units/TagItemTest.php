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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Exception;
use Computer;
use GlpiPlugin\Tag\Tests\TagTestCase;
use PluginTagTagItem;
use Ticket;

use function Safe\ob_end_clean;
use function Safe\ob_start;

final class TagItemTest extends TagTestCase
{
    private const TECH_USER = ['login' => 'tech', 'pass' => 'tech'];

    private const SELF_SERVICE_USER = ['login' => 'post-only', 'pass' => 'postonly'];

    public function testTagsFromTicket(): void
    {
        $tagID1 = $this->createTag('TicketTag1');
        $tagID2 = $this->createTag('TicketTag2');


        $ticket = new Ticket();
        $ticket->add([
            'name' => 'Ticket add Tag',
            'content' => 'Ticket Add Tag',
            '_plugin_tag_tag_process_form' => 1,
            '_plugin_tag_tag_values'   => [
                $tagID1,
                $tagID2,
            ],
        ]);

        $this->isItemTagged($ticket, $tagID1);
        $this->isItemTagged($ticket, $tagID2);
    }

    public function testAddItemToTagViaAjaxSucceeds(): void
    {
        $this->loginAs(self::TECH_USER);

        $tagID = $this->createTag('AddItemTag');
        $ticket = $this->createItem(Ticket::class, [
            'name' => 'Ticket to tag',
            'content' => 'Ticket to tag',
        ]);

        $_POST['plugin_tag_tags_id'] = $tagID;
        $_POST['itemtype'] = Ticket::class;
        $_POST['items_id'] = $ticket->getID();

        $this->callAjax('plugins/tag/ajax/add_item_to_tag.php');

        $this->isItemTagged($ticket, $tagID);
    }

    public function testAddItemToTagViaAjaxIsIdempotent(): void
    {
        $this->loginAs(self::TECH_USER);

        $tagID = $this->createTag('AddItemTagTwice');
        $ticket = $this->createItem(Ticket::class, [
            'name' => 'Ticket to tag twice',
            'content' => 'Ticket to tag twice',
        ]);

        $_POST['plugin_tag_tags_id'] = $tagID;
        $_POST['itemtype'] = Ticket::class;
        $_POST['items_id'] = $ticket->getID();

        $this->callAjax('plugins/tag/ajax/add_item_to_tag.php');
        $this->callAjax('plugins/tag/ajax/add_item_to_tag.php');

        $tagItem = new PluginTagTagItem();
        $links = $tagItem->find([
            'plugin_tag_tags_id' => $tagID,
            'itemtype'           => Ticket::class,
            'items_id'           => $ticket->getID(),
        ]);
        $this->assertCount(1, $links);
    }

    public function testAddItemToTagViaAjaxFailsForUnknownTag(): void
    {
        $this->loginAs(self::TECH_USER);

        $ticket = $this->createItem(Ticket::class, [
            'name' => 'Ticket unknown tag',
            'content' => 'Ticket unknown tag',
        ]);

        $_POST['plugin_tag_tags_id'] = 999999;
        $_POST['itemtype'] = Ticket::class;
        $_POST['items_id'] = $ticket->getID();

        $this->expectException(AccessDeniedHttpException::class);
        $this->callAjax('plugins/tag/ajax/add_item_to_tag.php');
    }

    public function testAddItemToTagViaAjaxFailsWhenUserLacksTagUpdateRight(): void
    {
        $this->loginAs(self::TECH_USER);

        $tagID = $this->createTag('ReadOnlyTag');
        $ticket = $this->createItem(Ticket::class, [
            'name' => 'Ticket read only tag',
            'content' => 'Ticket read only tag',
        ]);

        $this->loginAs(self::TECH_USER, READ);

        $_POST['plugin_tag_tags_id'] = $tagID;
        $_POST['itemtype'] = Ticket::class;
        $_POST['items_id'] = $ticket->getID();

        $this->expectException(AccessDeniedHttpException::class);
        $this->callAjax('plugins/tag/ajax/add_item_to_tag.php');
    }

    public function testAddItemToTagViaAjaxFailsWhenUserLacksItemUpdateRight(): void
    {
        $this->loginAs(self::TECH_USER);
        $tagID = $this->createTag('ComputerTag', ['Computer']);
        $computer = $this->createItem(Computer::class, [
            'name' => 'Computer to tag',
            'entities_id' => 0,
        ]);

        $this->loginAs(self::SELF_SERVICE_USER);

        $_POST['plugin_tag_tags_id'] = $tagID;
        $_POST['itemtype'] = Computer::class;
        $_POST['items_id'] = $computer->getID();

        $this->expectException(AccessDeniedHttpException::class);
        $this->callAjax('plugins/tag/ajax/add_item_to_tag.php');
    }

    private function callAjax(string $path): void
    {
        ob_start();
        try {
            include GLPI_ROOT . '/' . $path;
        } catch (Exception $exception) {
            ob_end_clean();
            throw $exception;
        }

        ob_end_clean();
    }

}
