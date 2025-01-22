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
use PluginTagTag;
use PluginTagTagItem;
use Profile;
use Ticket;
use User;

final class TagRuleTest extends TagTestCase
{
    private const SELF_SERVICE_USER = ['login' => 'self-service', 'pass' => 'self-service'];
    private const TECH_USER = ['login' => 'tech', 'pass' => 'tech'];

    public function testRuleApplyingTagSelfServiceUser(): void
    {
        $this->createSelfServiceUser();
        $user_id = $this->loginAs(self::SELF_SERVICE_USER);
        $tagID = $this->createTag('TicketTag');
        $this->createRule($tagID);
        $ticket = $this->createTicket(
            [
                'name' => 'Ticket add Tag',
                'content' => 'Ticket Add Tag',
                '_users_id_assign'   => $user_id,
            ]
        );
        $this->isTicketTagged($ticket, $tagID);
    }

    public function testRuleApplyingTagTechnicianUser(): void
    {
        $user_id = $this->loginAs(self::TECH_USER);
        $tagID = $this->createTag('TicketTag');
        $this->createRule($tagID);
        $ticket = $this->createTicket(
            [
                'name' => 'Ticket add Tag',
                'content' => 'Ticket Add Tag',
                '_users_id_assign'   => $user_id,
            ]
        );
        $this->isTicketTagged($ticket, $tagID);
    }

    public function testManualTagAndRuleBasedTag(): void
    {
        $user_id = $this->loginAs(self::TECH_USER);
        $tagID1 = $this->createTag('TicketTag1');
        $tagID2 = $this->createTag('TicketTag2');
        $this->createRule($tagID1);
        $ticket = $this->createTicket(
            [
                'name' => 'Ticket add Tag',
                'content' => 'Ticket Add Tag',
                '_users_id_assign'   => $user_id,
                '_plugin_tag_tag_values' => [$tagID2]
            ]
        );
        $this->isTicketTagged($ticket, $tagID1);
        $this->isTicketTagged($ticket, $tagID2);
    }

    private function createSelfServiceUser()
    {
        $item = new User();
        $id = $item->add(
            [
                'name'          => self::SELF_SERVICE_USER['login'],
                'password'      => self::SELF_SERVICE_USER['pass'],
                'password2'     => self::SELF_SERVICE_USER['pass'],
                '_profiles_id'  => getItemByTypeName(Profile::class, 'Self-Service', true),
                '_is_recursive' => true,
            ]
        );
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    private function loginAs(array $credentials): int
    {
        $login = $credentials['login'];
        $pass  = $credentials['pass'];
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        $this->assertNotNull($user);

        return $user->getID();
    }

    private function createTag(string $tagName): int
    {
        $tag = new PluginTagTag();
        $tag->add(
            [
                'name' => $tagName,
                'is_active' => 1,
                'type_menu' => ['Ticket']
            ]
        );
        $this->assertGreaterThan(0, $tag->getID());

        return $tag->getID();
    }

    private function createRule(int $tagID): void
    {
        $rule       = new \Rule();
        $criteria   = new \RuleCriteria();
        $action     = new \RuleAction();

        $rules_id = $rule->add(['name'        => 'Assign Tag',
            'is_active'   => 1,
            'entities_id' => 0,
            'sub_type'    => 'RuleTicket',
            'match'       => \Rule::AND_MATCHING,
            'condition'   => 1,
            'description' => ''
        ]);
        $this->assertGreaterThan(0, (int)$rules_id);

        $this->assertGreaterThan(
            0,
            (int)$criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'name',
                'condition' => \Rule::PATTERN_CONTAIN,
                'pattern'   => 'Add Tag'
            ])
        );

        $this->assertGreaterThan(
            0,
            (int)$action->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => '_plugin_tag_tag_from_rules',
                'value'       => $tagID
            ])
        );

        $this->assertTrue($rule->getRuleWithCriteriasAndActions($rules_id, 1, 1));
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

    private function isTicketTagged(Ticket $ticket, int $tagID)
    {
        $tagItem = new PluginTagTagItem();
        $ticketTag = $tagItem->getFromDBForItems(PluginTagTag::getById($tagID), $ticket);

        $this->assertTrue($ticketTag);
    }
}
