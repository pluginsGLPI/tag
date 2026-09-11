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

use Entity;
use GlpiPlugin\Tag\Tests\TagTestCase;
use PluginTagTag;
use Session;
use Ticket;

final class TagItemTest extends TagTestCase
{
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

    public function testTagOutOfEntityScopeIsNotLinked(): void
    {
        $this->login();

        $entity = new Entity();
        $visible_entity_id = $entity->add([
            'name' => 'TagVisibleEntity',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $visible_entity_id);
        $out_of_scope_entity_id = $entity->add([
            'name' => 'TagOutOfScopeEntity',
            'entities_id' => 0,
        ]);
        $this->assertGreaterThan(0, $out_of_scope_entity_id);

        $tag = new PluginTagTag();
        $tagID = $tag->add([
            'name' => 'OutOfScopeTag',
            'is_active' => 1,
            'type_menu' => ['Ticket'],
            'entities_id' => $out_of_scope_entity_id,
            'is_recursive' => 0,
        ]);
        $this->assertGreaterThan(0, $tagID);

        $this->assertTrue(Session::changeActiveEntities($visible_entity_id, false));

        $ticket = new Ticket();
        $ticket->add([
            'name' => 'Ticket out of scope tag',
            'content' => 'Ticket out of scope tag',
            'entities_id' => $visible_entity_id,
            '_plugin_tag_tag_process_form' => 1,
            '_plugin_tag_tag_values' => [
                $tagID,
            ],
        ]);
        $this->assertGreaterThan(0, $ticket->getID());

        $this->isItemNotTagged($ticket, $tagID);
    }
}
