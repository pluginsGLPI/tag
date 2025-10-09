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
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Destination\CommonITILField\Category;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Form;

class PluginTagDestinationField extends AbstractConfigField
{
    #[Override]
    public function getLabel(): string
    {
        return _sn('Tag', 'Tags', 1, 'tag');
    }

    #[Override]
    public function getConfigClass(): string
    {
        return PluginTagDestinationFieldConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof PluginTagDestinationFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        [$available_tags, $available_tags_color] = (new PluginTagQuestionType())->getAvailableTags();

        $twig = TemplateRenderer::getInstance();
        return $twig->render('@tag/destinationfield.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUES'  => PluginTagDestinationFieldStrategy::SPECIFIC_VALUES->value,
            'CONFIG_SPECIFIC_ANSWERS' => PluginTagDestinationFieldStrategy::SPECIFIC_ANSWERS->value,

            // General display options
            'options' => $display_options,

            // Specific additional config for SPECIFIC_VALUES strategy
            'specific_values_extra_field' => [
                'input_name'     => $input_name . "[" . PluginTagDestinationFieldConfig::SPECIFIC_TAG_IDS . "]",
                'selected_tags'  => $config->getSpecificTagIDs() ?? [],
                'available_tags' => $available_tags,
                'tags_color'     => $available_tags_color,
            ],

            // Specific additional config for SPECIFIC_ANSWERS strategy
            'specific_answers_extra_field' => [
                'empty_label'     => __("Select questions..."),
                'values'          => $config->getSpecificQuestionIds(),
                'input_name'      => $input_name . "[" . PluginTagDestinationFieldConfig::SPECIFIC_QUESTION_IDS . "]",
                'possible_values' => $this->getTagQuestionsValuesForDropdown($form),
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof PluginTagDestinationFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        foreach ($config->getStrategies() as $strategy) {
            $input['_plugin_tag_tag_values'] = array_unique(array_merge(
                $input['_plugin_tag_tag_values'] ?? [],
                $strategy->getTagIds($config, $answers_set),
            ));
        }

        return $input;
    }

    #[Override]
    public function prepareInput(array $input): array
    {
        $input = parent::prepareInput($input);

        if (!isset($input[$this->getKey()][PluginTagDestinationFieldConfig::STRATEGIES])) {
            return $input;
        }

        // Ensure that tag_ids is an array
        if (!is_array($input[$this->getKey()][PluginTagDestinationFieldConfig::SPECIFIC_TAG_IDS] ?? null)) {
            $input[$this->getKey()][PluginTagDestinationFieldConfig::SPECIFIC_TAG_IDS] = null;
        }

        // Ensure that question_ids is an array
        if (!is_array($input[$this->getKey()][PluginTagDestinationFieldConfig::SPECIFIC_QUESTION_IDS] ?? null)) {
            $input[$this->getKey()][PluginTagDestinationFieldConfig::SPECIFIC_QUESTION_IDS] = null;
        }

        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): PluginTagDestinationFieldConfig
    {
        return new PluginTagDestinationFieldConfig(
            [PluginTagDestinationFieldStrategy::LAST_VALID_ANSWER],
        );
    }

    #[Override]
    public function canHaveMultipleStrategies(): bool
    {
        return true;
    }

    public function getStrategiesForDropdown(): array
    {
        $values = [];
        foreach (PluginTagDestinationFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel();
        }
        return $values;
    }

    private function getTagQuestionsValuesForDropdown(Form $form): array
    {
        $values = [];
        $questions = $form->getQuestionsByType(PluginTagQuestionType::class);
        foreach ($questions as $question) {
            $values[$question->getId()] = $question->fields['name'];
        }

        return $values;
    }

    #[Override]
    public function getWeight(): int
    {
        return 0;
    }

    #[Override]
    public function getCategory(): Category
    {
        return Category::PROPERTIES;
    }
}
