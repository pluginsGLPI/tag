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
use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Tag\Controller\TagItemController;
use GlpiPlugin\Tag\Tests\TagTestCase;
use PluginTagTag;
use Symfony\Component\HttpFoundation\Request;
use Ticket;

final class TagItemTest extends TagTestCase
{
    private const TECH_USER = ['login' => 'tech', 'pass' => 'tech'];

    public function testTagsFromTicket(): void
    {
        $this->loginAs(self::TECH_USER);

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

        $out_of_scope_entity = getItemByTypeName('Entity', '_test_child_2', true);
        $tag = new PluginTagTag();
        $tag->add([
            'name' => 'OutOfScopeTag',
            'is_active' => 1,
            'type_menu' => ['Ticket'],
            'entities_id' => $out_of_scope_entity,
            'is_recursive' => 0,
        ]);
        $tagID = $tag->getID();
        $this->assertGreaterThan(0, $tagID);

        $this->setEntity('_test_child_1', false);

        $ticket = new Ticket();
        $ticket->add([
            'name' => 'Ticket out of scope tag',
            'content' => 'Ticket out of scope tag',
            'entities_id' => getItemByTypeName('Entity', '_test_child_1', true),
            '_plugin_tag_tag_process_form' => 1,
            '_plugin_tag_tag_values'   => [
                $tagID,
            ],
        ]);
        $this->assertGreaterThan(0, $ticket->getID());

        $this->isItemNotTagged($ticket, $tagID);
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

    //test takes a non-recursive tag associated with _test_child_2 and a computer in _test_child_1. It calls
    //associate() with these two elements and waits for an exception. It logs in as a superadmin with the root entity
    //set to recursive, to ensure that it is indeed the entity validation that is causing the block, and not a lack of
    // permissions.
    public function testTagAssociationOutOfEntityScopeIsDenied(): void
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $tag = $this->createItem(PluginTagTag::class, [
            'name' => 'OutOfScopeTag',
            'is_active' => 1,
            'type_menu' => ['Computer'],
            'entities_id' => getItemByTypeName('Entity', '_test_child_2', true),
            'is_recursive' => 0,
        ], ['type_menu']);
        $computer = $this->createItem(Computer::class, [
            'name' => 'Computer out of tag scope',
            'entities_id' => getItemByTypeName('Entity', '_test_child_1', true),
        ]);

        $controller = new TagItemController();
        $request = Request::create('/plugins/tag/associate', 'POST', [
            'plugin_tag_tags_id' => $tag->getID(),
            'itemtype'           => Computer::class,
            'items_id'           => $computer->getID(),
        ]);

        $this->expectException(AccessDeniedHttpException::class);
        $controller->associate($request);
    }
}
