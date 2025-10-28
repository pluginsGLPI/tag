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

use Glpi\Form\QuestionType\QuestionTypesManager;
use Glpi\Tests\FormBuilder;
use GlpiPlugin\Tag\Tests\QuestionTypeTestCase;
use PluginTagQuestionType;
use PluginTagQuestionTypeCategory;

final class TagQuestionTypeTest extends QuestionTypeTestCase
{
    public function testTagsQuestionCategoryIsAvailable(): void
    {
        // Act: get enabled question type categories
        $manager = QuestionTypesManager::getInstance();
        $categories = $manager->getCategories();

        // Assert: check that Tag question type category is registered
        $this->assertContains(
            PluginTagQuestionTypeCategory::class,
            array_map(fn($category) => get_class($category), $categories),
        );
    }

    public function testTagsQuestionIsAvailable(): void
    {
        // Act: get enabled question types
        $manager = QuestionTypesManager::getInstance();
        $types = $manager->getQuestionTypes();

        // Assert: check that Tag question type is registered
        $this->assertContains(
            PluginTagQuestionType::class,
            array_map(fn($type) => get_class($type), $types),
        );
    }

    public function testTagsQuestionEditorRendering(): void
    {
        // Arrange: create form with Tag question
        $builder = new FormBuilder("My form");
        $builder->addQuestion("My question", PluginTagQuestionType::class);
        $form = $this->createForm($builder);

        // Act: render form editor
        $crawler = $this->renderFormEditor($form);

        // Assert: item was rendered
        $this->assertNotEmpty($crawler->filter('select[data-glpi-plugin-tag-dropdown-uuid]'));
    }

    public function testTagsQuestionHelpdeskRendering(): void
    {
        // Arrange: create some tags
        $this->createTag('Tag 1');
        $this->createTag('Tag 2');

        // Arrange: create form with Tag question
        $builder = new FormBuilder("My form");
        $builder->addQuestion("My question", PluginTagQuestionType::class);
        $form = $this->createForm($builder);

        // Act: render helpdesk form
        $crawler = $this->renderHelpdeskForm($form);

        // Assert: item was rendered
        $this->assertNotEmpty($crawler->filter('select[data-glpi-plugin-tag-dropdown-uuid]'));
    }
}
