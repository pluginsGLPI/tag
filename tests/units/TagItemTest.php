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

use GlpiPlugin\Tag\Tests\TagTestCase;
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

}
