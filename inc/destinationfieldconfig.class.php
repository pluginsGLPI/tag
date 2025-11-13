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

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Destination\ConfigFieldWithStrategiesInterface;

class PluginTagDestinationFieldConfig implements
    JsonFieldInterface,
    ConfigFieldWithStrategiesInterface
{
    // Unique reference to hardcoded names used for serialization and forms input names
    public const STRATEGIES            = 'strategies';

    public const SPECIFIC_QUESTION_IDS = 'specific_question_ids';

    public const SPECIFIC_TAG_IDS      = 'specific_tag_ids';

    /**
     * @param array<PluginTagDestinationFieldStrategy> $strategies
     * @param array<int>|null $specific_question_ids
     * @param array<int>|null $specific_tag_ids
     */
    public function __construct(
        private readonly array $strategies,
        private readonly ?array $specific_question_ids = null,
        private readonly ?array $specific_tag_ids      = null,
    ) {}

    #[Override]
    public static function jsonDeserialize(array $data): self
    {
        $strategies = array_map(
            fn($value) => PluginTagDestinationFieldStrategy::tryFrom($value),
            $data[self::STRATEGIES] ?? [],
        );
        if ($strategies === []) {
            $strategies = [PluginTagDestinationFieldStrategy::LAST_VALID_ANSWER];
        }

        return new self(
            strategies           : $strategies,
            specific_question_ids: $data[self::SPECIFIC_QUESTION_IDS] ?? null,
            specific_tag_ids     : $data[self::SPECIFIC_TAG_IDS] ?? null,
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            self::STRATEGIES            => array_map(
                fn(PluginTagDestinationFieldStrategy $strategy) => $strategy->value,
                $this->strategies,
            ),
            self::SPECIFIC_QUESTION_IDS => $this->specific_question_ids,
            self::SPECIFIC_TAG_IDS      => $this->specific_tag_ids,
        ];
    }

    #[Override]
    public static function getStrategiesInputName(): string
    {
        return self::STRATEGIES;
    }

    /**
     * @return array<PluginTagDestinationFieldStrategy>
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    public function getSpecificQuestionIds(): ?array
    {
        return $this->specific_question_ids;
    }

    public function getSpecificTagIDs(): ?array
    {
        return $this->specific_tag_ids;
    }
}
