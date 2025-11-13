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
use Group;
use Group_User;
use Rule;
use RuleAction;
use RuleCriteria;
use RuleTicket;
use Ticket;

final class TagRuleTest extends TagTestCase
{
    private const SELF_SERVICE_USER = ['login' => 'post-only', 'pass' => 'postonly'];

    private const TECH_USER = ['login' => 'tech', 'pass' => 'tech'];

    public function testRuleApplyingTagSelfServiceUser(): void
    {
        $user_id = $this->loginAs(self::SELF_SERVICE_USER);
        $tagID = $this->createTag('TicketTag');
        $this->createTagRule($tagID);
        $ticket = $this->createTicket(
            [
                'name' => 'Ticket add Tag',
                'content' => 'Ticket Add Tag',
                '_users_id_assign'   => $user_id,
            ],
        );
        $this->isItemTagged($ticket, $tagID);
    }

    public function testRuleApplyingTagTechnicianUser(): void
    {
        $user_id = $this->loginAs(self::TECH_USER);
        $tagID = $this->createTag('TicketTag');
        $this->createTagRule($tagID);
        $ticket = $this->createTicket(
            [
                'name' => 'Ticket add Tag',
                'content' => 'Ticket Add Tag',
                '_users_id_assign'   => $user_id,
            ],
        );
        $this->isItemTagged($ticket, $tagID);
    }

    public function testManualTagAndRuleBasedTag(): void
    {
        $user_id = $this->loginAs(self::TECH_USER);
        $tagID1 = $this->createTag('TicketTag1');
        $tagID2 = $this->createTag('TicketTag2');
        $this->createTagRule($tagID1);
        $ticket = $this->createTicket(
            [
                'name' => 'Ticket add Tag',
                'content' => 'Ticket Add Tag',
                '_users_id_assign'   => $user_id,
                '_plugin_tag_tag_values' => [$tagID2],
            ],
        );
        $this->isItemTagged($ticket, $tagID1);
        $this->isItemTagged($ticket, $tagID2);
    }

    public function testMultipleRulesAppendingTags(): void
    {
        $user_id = $this->loginAs(self::TECH_USER);

        $tagID1 = $this->createTag('TicketTag1');
        $tagID2 = $this->createTag('TicketTag2');

        $this->createTagRule($tagID1, 'content', 'Tag1', 'assign');

        $this->createTagRule($tagID2, 'content', 'Tag2', 'append');

        $ticket = $this->createTicket(
            [
                'name' => 'Ticket add Tag',
                'content' => 'Add Tag1 & Tag2',
                '_users_id_assign' => $user_id,
            ],
        );

        $this->isItemTagged($ticket, $tagID1);
        $this->isItemTagged($ticket, $tagID2);
    }

    public function testAssignTagOnTicketCreation(): void
    {
        $user_id = $this->loginAs(self::TECH_USER);

        $tagID1 = $this->createTag('TicketTag1');
        $tagID2 = $this->createTag('TicketTag2');

        $this->createTagRule($tagID1, 'name', 'Add Tag', 'assign', RuleTicket::ONADD);

        $ticket = $this->createTicket([
            'name' => 'Ticket add Tag',
            'content' => 'Test Content',
            '_users_id_assign' => $user_id,
            '_plugin_tag_tag_values' => [$tagID2],
        ]);

        $this->isItemTagged($ticket, $tagID1);

        $this->isItemTagged($ticket, $tagID2);
    }

    public function testAppendTagOnTicketCreation(): void
    {
        $user_id = $this->loginAs(self::TECH_USER);

        $tagID1 = $this->createTag('TicketTag1');
        $tagID2 = $this->createTag('TicketTag2');

        $this->createTagRule($tagID1, 'name', 'Add Tag', 'append', RuleTicket::ONADD);

        $ticket = $this->createTicket([
            'name' => 'Ticket add Tag',
            'content' => 'Test Content',
            '_users_id_assign' => $user_id,
            '_plugin_tag_tag_values' => [$tagID2],
        ]);

        $this->isItemTagged($ticket, $tagID1);
        $this->isItemTagged($ticket, $tagID2);
    }

    public function testAssignOverridesPreviousTags(): void
    {
        $user_id = $this->loginAs(self::TECH_USER);

        $tagID1 = $this->createTag('TicketTag1');
        $tagID2 = $this->createTag('TicketTag2');
        $tagID3 = $this->createTag('TicketTag3');

        $ticket = $this->createTicket([
            'name' => 'Ticket Test',
            'content' => 'Initial content',
            '_users_id_assign' => $user_id,
            '_plugin_tag_tag_values' => [$tagID2],
        ]);

        $this->isItemTagged($ticket, $tagID2);

        $this->createTagRule($tagID1, 'content', 'Updated content', 'assign', RuleTicket::ONUPDATE);

        $this->updateTicket($ticket->getID(), [
            'content' => 'Updated content',
            '_plugin_tag_tag_values' => [$tagID3],
        ]);

        $this->isItemTagged($ticket, $tagID1);
        $this->isItemNotTagged($ticket, $tagID2);
        $this->isItemTagged($ticket, $tagID3);
    }

    public function testAppendPreservesPreviousTags(): void
    {
        $user_id = $this->loginAs(self::TECH_USER);

        $tagID1 = $this->createTag('TicketTag1');
        $tagID2 = $this->createTag('TicketTag2');
        $tagID3 = $this->createTag('TicketTag3');

        $ticket = $this->createTicket([
            'name' => 'Ticket Test',
            'content' => 'Initial content',
            '_users_id_assign' => $user_id,
            '_plugin_tag_tag_values' => [$tagID2],
        ]);

        $this->isItemTagged($ticket, $tagID2);

        $this->createTagRule($tagID1, 'content', 'Updated content', 'append', RuleTicket::ONUPDATE);

        $this->updateTicket($ticket->getID(), [
            'content' => 'Updated content',
            '_plugin_tag_tag_values' => [$tagID2, $tagID3],
        ]);

        $this->isItemTagged($ticket, $tagID1);
        $this->isItemTagged($ticket, $tagID2);
        $this->isItemTagged($ticket, $tagID3);
    }

    public function testMultipleRulesWithDifferentActions(): void
    {
        $user_id = $this->loginAs(self::TECH_USER);

        $tagID1 = $this->createTag('RuleTag1');
        $tagID2 = $this->createTag('RuleTag2');
        $tagID3 = $this->createTag('ManualTag');

        $this->createTagRule($tagID1, 'name', 'Multiple Rules', 'assign', RuleTicket::ONADD);

        $this->createTagRule($tagID2, 'content', 'Updated for multiple rules', 'append', RuleTicket::ONUPDATE);

        $ticket = $this->createTicket([
            'name' => 'Multiple Rules Test',
            'content' => 'Initial content',
            '_users_id_assign' => $user_id,
            '_plugin_tag_tag_values' => [$tagID3],
        ]);

        $this->isItemTagged($ticket, $tagID1);
        $this->isItemTagged($ticket, $tagID3);

        $this->updateTicket($ticket->getID(), [
            'content' => 'Updated for multiple rules',
            '_plugin_tag_tag_values' => [$tagID1, $tagID3],
        ]);

        $this->isItemTagged($ticket, $tagID1);
        $this->isItemTagged($ticket, $tagID2);
        $this->isItemTagged($ticket, $tagID3);
    }

    public function testUpdateTicketWithOnlyActor(): void
    {
        $user_id = $this->loginAs(self::TECH_USER);

        $tagID1 = $this->createTag('RuleTag1');

        $user_id_2 = getItemByTypeName('User', 'post-only', true);

        $group = new Group();
        $group->add([
            'name' => 'Group for post-only',
        ]);

        $group_user = new Group_User();
        $group_user->add([
            'groups_id' => $group->getID(),
            'users_id' => $user_id_2,
        ]);

        $this->createTagRule(
            $tagID1,
            '_groups_id_of_requester',
            $group->getID(),
            'assign',
            RuleTicket::ONUPDATE,
            Rule::PATTERN_IS,
        );

        $ticket = $this->createTicket([
            'name' => 'Update Ticket with Actor',
            'content' => 'Initial content',
            '_users_id_requester' => $user_id,
        ]);

        $this->isItemNotTagged($ticket, $tagID1);

        $this->updateTicket($ticket->getID(), [
            '_users_id_requester' => $user_id_2,
        ]);

        $this->isItemTagged($ticket, $tagID1);
    }

    private function createTagRule(
        int $tagID,
        string $criteria_field = 'name',
        $criteria_pattern = 'Add Tag',
        string $action_type = 'assign',
        int $condition = RuleTicket::ONADD,
        int $criteria_condition = Rule::PATTERN_CONTAIN,
    ): void {
        $rule       = new Rule();
        $criteria   = new RuleCriteria();
        $action     = new RuleAction();

        $rules_id = $rule->add([
            'name'        => 'Rule for tag ' . $tagID,
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleTicket',
            'match'       => Rule::AND_MATCHING,
            'condition'   => $condition,
            'description' => '',
        ]);
        $this->assertGreaterThan(0, (int) $rules_id);

        $this->assertGreaterThan(
            0,
            (int) $criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => $criteria_field,
                'condition' => $criteria_condition,
                'pattern'   => $criteria_pattern,
            ]),
        );

        $this->assertGreaterThan(
            0,
            (int) $action->add([
                'rules_id'    => $rules_id,
                'action_type' => $action_type,
                'field'       => '_plugin_tag_tag_from_rules',
                'value'       => $tagID,
            ]),
        );

        $this->assertTrue($rule->getRuleWithCriteriasAndActions($rules_id, true, true));
        $this->assertCount(1, $rule->criterias);
        $this->assertCount(1, $rule->actions);
    }

    private function createTicket(array $data): Ticket
    {
        $ticket = new Ticket();
        $ticket->add($data);
        $this->assertGreaterThan(0, $ticket->getID());

        return $ticket;
    }

    private function updateTicket(int $ticket_id, array $data): void
    {
        $ticket = new Ticket();
        $ticket->getFromDB($ticket_id);
        $data['id'] = $ticket_id;
        $this->assertTrue($ticket->update($data));

        $ticket->getFromDB($ticket_id);
    }
}
