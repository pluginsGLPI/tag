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

use Glpi\Form\AnswersSet;

enum PluginTagDestinationFieldStrategy: string
{
    case NO_TAGS           = 'no_tags';
    case SPECIFIC_VALUES   = 'specific_values';
    case SPECIFIC_ANSWERS  = 'specific_answers';
    case LAST_VALID_ANSWER = 'last_valid_answer';

    public function getLabel(): string
    {
        return match ($this) {
            self::NO_TAGS           => __("No tags"),
            self::SPECIFIC_VALUES   => __("Specific tags"),
            self::SPECIFIC_ANSWERS  => __("Answers from specific questions"),
            self::LAST_VALID_ANSWER => __('Answer to last "Tag" question'),
        };
    }

    public function getTagIds(
        PluginTagDestinationFieldConfig $config,
        AnswersSet $answers_set,
    ): array {
        return match ($this) {
            self::NO_TAGS         => [],
            self::SPECIFIC_VALUES => $config->getSpecificTagIDs() ?? [],
            self::SPECIFIC_ANSWERS => $this->getTagsIDsForSpecificAnswers(
                $config->getSpecificQuestionIds(),
                $answers_set,
            ),
            self::LAST_VALID_ANSWER => $this->getTagIDsForLastValidAnswer($answers_set),
        };
    }

    private function getTagsIDsForSpecificAnswers(
        ?array $question_ids,
        AnswersSet $answers_set,
    ): array {
        if (empty($question_ids)) {
            return [];
        }

        $tag_ids = [];
        foreach ($question_ids as $question_id) {
            $tag_ids = array_merge(
                $tag_ids,
                $this->getTagsIDsForSpecificAnswer($question_id, $answers_set),
            );
        }

        // Ensure unique IDs
        return array_values(array_unique($tag_ids));
    }

    private function getTagsIDsForSpecificAnswer(
        ?int $question_id,
        AnswersSet $answers_set,
    ): array {
        if ($question_id === null) {
            return [];
        }

        $answer = $answers_set->getAnswerByQuestionId($question_id);
        if (!is_array($answer->getRawAnswer())) {
            return [];
        }

        return $answer->getRawAnswer();
    }

    private function getTagIDsForLastValidAnswer(
        AnswersSet $answers_set,
    ): array {
        $valid_answers = $answers_set->getAnswersByType(PluginTagQuestionType::class);
        if (count($valid_answers) == 0) {
            return [];
        }

        $answer = end($valid_answers);
        return $answer->getRawAnswer() ?? [];
    }
}
