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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Form;
use Glpi\Form\Migration\FormQuestionDataConverterInterface;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\AbstractQuestionType;
use Glpi\Form\QuestionType\QuestionTypeCategoryInterface;

final class PluginTagQuestionType extends AbstractQuestionType implements FormQuestionDataConverterInterface
{
    #[Override]
    public function getCategory(): QuestionTypeCategoryInterface
    {
        return new PluginTagQuestionTypeCategory();
    }

    #[Override]
    public function isAllowedForUnauthenticatedAccess(): bool
    {
        return true;
    }

    #[Override]
    public function formatDefaultValueForDB(mixed $value): ?string
    {
        if (!is_array($value)) {
            return null;
        }

        return implode(',', $value);
    }

    #[Override]
    public function formatRawAnswer(mixed $answer, Question $question): string
    {
        if (!is_array($answer)) {
            return '';
        }

        if (count(array_filter($answer, 'is_numeric')) !== count($answer)) {
            throw new LogicException('Answer must be an array of numeric IDs');
        }

        $tags = PluginTagTag::getByIds($answer);
        $tag_names = array_map(fn($tag) => $tag->fields['name'], $tags);

        return implode(',', $tag_names);
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question): string
    {
        [$available_tags, $available_tags_color] = $this->getAvailableTags();

        $twig = TemplateRenderer::getInstance();
        return $twig->render('@tag/question_dropdown.html.twig', [
            'input_name'      => 'default_value',
            'selected_tags'   => empty($question?->fields['default_value']) ? [] : explode(',', (string) $question->fields['default_value']),
            'available_tags'  => $available_tags,
            'tags_color'      => $available_tags_color,
            'dropdown_params' => [
                'no_label' => true,
                'init'     => $question instanceof Question,
            ],
        ]);
    }

    #[Override]
    public function renderEndUserTemplate(Question $question): string
    {
        [$available_tags, $available_tags_color] = $this->getAvailableTags($question->getForm());

        $twig = TemplateRenderer::getInstance();
        return $twig->render('@tag/question_dropdown.html.twig', [
            'input_name'          => $question->getEndUserInputName(),
            'selected_tags'       => empty($question?->fields['default_value']) ? [] : explode(',', (string) $question->fields['default_value']),
            'available_tags'      => $available_tags,
            'tags_color'          => $available_tags_color,
            'show_search_tooltip' => false,
            'dropdown_params'     => [
                'no_label' => true,
                'init'     => true,
            ],
        ]);
    }

    #[Override]
    public function beforeConversion(array $rawData): void {}

    #[Override]
    public function convertDefaultValue(array $rawData): null
    {
        return null;
    }

    #[Override]
    public function convertExtraData(array $rawData): null
    {
        return null;
    }

    #[Override]
    public function getTargetQuestionType(array $rawData): string
    {
        return self::class;
    }

    public function getAvailableTags(?Form $form = null): array
    {
        $active_entities_ids = Session::getActiveEntities();
        if ($active_entities_ids === [] && $form) {
            $active_entities_ids = [$form->getEntityID()];
        }

        $tag                  = new PluginTagTag();
        $available_tags       = [];
        $available_tags_color = [];
        $result               = $tag->find([
            'is_active' => 1,
            'OR' => [
                ['type_menu' => ['LIKE', '%\"Ticket\"%']],
                ['type_menu' => ['LIKE', '%\"Change\"%']],
                ['type_menu' => ['LIKE', '%\"Problem\"%']],
                ['type_menu' => '0'],
                ['type_menu' => ''],
                ['type_menu' => 'NULL'],
            ],
        ] + getEntitiesRestrictCriteria('', '', $active_entities_ids, true), 'name');
        foreach ($result as $id => $data) {
            $available_tags[$id] = $data['name'];
            $available_tags_color[$id] = $data['color'] ?: '#DDDDDD';
        }

        return [$available_tags, $available_tags_color];
    }
}
