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

use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Form;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Override;
use PluginTagDestinationField;
use PluginTagDestinationFieldConfig;
use PluginTagDestinationFieldStrategy;
use PluginTagQuestionType;
use PluginTagTag;
use PluginTagTagItem;
use tests\units\Glpi\Form\Destination\CommonITILField\AbstractDestinationFieldTest;
use User;

include_once __DIR__ . '/../../../../tests/abstracts/AbstractDestinationFieldTest.php';

final class TagDestinationFieldTest extends AbstractDestinationFieldTest
{
    use FormTesterTrait;

    public function testNoTags(): void
    {
        $form = $this->createAndGetFormWithMultipleTagQuestions();
        $answers = $this->getAnswers();

        $this->sendFormAndAssertTicketTags(
            form: $form,
            config: new PluginTagDestinationFieldConfig(
                [PluginTagDestinationFieldStrategy::NO_TAGS],
            ),
            answers: $answers['answers'],
            expected_tag_ids: [],
        );
    }

    public function testTagsFromSpecificValues(): void
    {
        $form = $this->createAndGetFormWithMultipleTagQuestions();
        $answers = $this->getAnswers();

        $this->sendFormAndAssertTicketTags(
            form: $form,
            config: new PluginTagDestinationFieldConfig(
                [PluginTagDestinationFieldStrategy::SPECIFIC_VALUES],
                specific_tag_ids: [$answers['tags'][0]->getID(), $answers['tags'][1]->getID()],
            ),
            answers: $answers['answers'],
            expected_tag_ids: [$answers['tags'][0]->getID(), $answers['tags'][1]->getID()],
        );
    }

    public function testTagsFromSpecificAnswers(): void
    {
        $form = $this->createAndGetFormWithMultipleTagQuestions();
        $answers = $this->getAnswers();

        $this->sendFormAndAssertTicketTags(
            form: $form,
            config: new PluginTagDestinationFieldConfig(
                [PluginTagDestinationFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [$this->getQuestionId($form, "Tag 1")],
            ),
            answers: $answers['answers'],
            expected_tag_ids: [$answers['tags'][0]->getID()],
        );

        $this->sendFormAndAssertTicketTags(
            form: $form,
            config: new PluginTagDestinationFieldConfig(
                [PluginTagDestinationFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [$this->getQuestionId($form, "Tag 2")],
            ),
            answers: $answers['answers'],
            expected_tag_ids: [$answers['tags'][1]->getID(), $answers['tags'][2]->getID()],
        );

        $this->sendFormAndAssertTicketTags(
            form: $form,
            config: new PluginTagDestinationFieldConfig(
                [PluginTagDestinationFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    $this->getQuestionId($form, "Tag 1"),
                    $this->getQuestionId($form, "Tag 2"),
                ],
            ),
            answers: $answers['answers'],
            expected_tag_ids: [
                $answers['tags'][0]->getID(),
                $answers['tags'][1]->getID(),
                $answers['tags'][2]->getID(),
            ],
        );
    }

    public function testTagsFromLastValidAnswer(): void
    {
        $form = $this->createAndGetFormWithMultipleTagQuestions();
        $answers = $this->getAnswers();

        $this->sendFormAndAssertTicketTags(
            form: $form,
            config: new PluginTagDestinationFieldConfig(
                [PluginTagDestinationFieldStrategy::LAST_VALID_ANSWER],
            ),
            answers: $answers['answers'],
            expected_tag_ids: [$answers['tags'][1]->getID(), $answers['tags'][2]->getID()],
        );
    }

    public function testMultipleStrategies(): void
    {
        $form = $this->createAndGetFormWithMultipleTagQuestions();
        $answers = $this->getAnswers();

        $this->sendFormAndAssertTicketTags(
            form: $form,
            config: new PluginTagDestinationFieldConfig(
                [
                    PluginTagDestinationFieldStrategy::SPECIFIC_VALUES,
                    PluginTagDestinationFieldStrategy::SPECIFIC_ANSWERS,
                    PluginTagDestinationFieldStrategy::LAST_VALID_ANSWER,
                ],
                specific_question_ids: [$this->getQuestionId($form, "Tag 1")],
                specific_tag_ids: [$answers['tags'][0]->getID()],
            ),
            answers: $answers['answers'],
            expected_tag_ids: [
                $answers['tags'][0]->getID(),
                $answers['tags'][1]->getID(),
                $answers['tags'][2]->getID(),
            ],
        );
    }

    #[Override]
    public static function provideConvertFieldConfigFromFormCreator(): iterable
    {
        yield 'No destination field config related to Tag question in FormCreator - Default configuration must be applied' => [
            'field_key'     => PluginTagDestinationField::getKey(),
            'fields_to_set' => [],
            'field_config' => new PluginTagDestinationFieldConfig(
                [PluginTagDestinationFieldStrategy::LAST_VALID_ANSWER],
            ),
        ];
    }

    private function getAnswers()
    {
        $tags = $this->createItems(PluginTagTag::class, [
            ['name' => 'Tag 1' ],
            ['name' => 'Tag 2' ],
            ['name' => 'Tag 3' ],
        ]);

        return [
            'answers' => [
                "Tag 1"    => [$tags[0]->getId()],
                "Tag 2"    => [$tags[1]->getId(), $tags[2]->getId()],
            ],
            'tags' => $tags,
        ];
    }

    private function sendFormAndAssertTicketTags(
        Form $form,
        PluginTagDestinationFieldConfig $config,
        array $answers,
        array $expected_tag_ids,
    ): void {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [PluginTagDestinationField::getKey() => $config->jsonSerialize()]],
            ["config"],
        );

        // The provider use a simplified answer format to be more readable.
        // Rewrite answers into expected format.
        $formatted_answers = [];
        foreach ($answers as $question => $answer) {
            $key = $this->getQuestionId($form, $question);
            $formatted_answers[$key] = $answer;
        }

        // Submit form
        $answers_handler = AnswersHandler::getInstance();
        $answers = $answers_handler->saveAnswers(
            $form,
            $formatted_answers,
            getItemByTypeName(User::class, TU_USER, true),
        );

        // Get created ticket
        $created_items = $answers->getCreatedItems();
        $this->assertCount(1, $created_items);
        $ticket = current($created_items);

        // Check ticket tags
        $tag_item = new PluginTagTagItem();
        $tags = $tag_item->find(['items_id' => $ticket->getId(), 'itemtype' => $ticket::getType()]);
        $this->assertCount(count($expected_tag_ids), $tags);
        $tag_ids = array_map(fn($tag) => $tag['plugin_tag_tags_id'], $tags);
        $this->assertEqualsCanonicalizing($expected_tag_ids, $tag_ids);
    }

    private function createAndGetFormWithMultipleTagQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Tag 1", PluginTagQuestionType::class);
        $builder->addQuestion("Tag 2", PluginTagQuestionType::class);
        return $this->createForm($builder);
    }
}
