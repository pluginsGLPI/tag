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
use GlpiPlugin\Tag\Controller\TagItemController;
use GlpiPlugin\Tag\Tests\TagTestCase;
use Symfony\Component\HttpFoundation\Request;
use Ticket;

final class TagItemTest extends TagTestCase
{
    private const TECH_USER = ['login' => 'tech', 'pass' => 'tech'];

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

    public function testTagAssociationCreatesLink(): void
    {
        $this->loginAs(self::TECH_USER);

        $tag = $this->createTag('MyTag', ['Computer']);
        $computer = $this->createItem(Computer::class, [
            'name' => 'Computer to tag',
            'entities_id' => 0,
        ]);

        $controller = new TagItemController();
        $request = Request::create('/plugins/tag/associate', 'POST', [
            'plugin_tag_tags_id' => $tag,
            'itemtype'           => Computer::class,
            'items_id'           => $computer->getID(),
        ]);

        $controller->associate($request);

        $this->isItemTagged($computer, $tag);
    }
}
