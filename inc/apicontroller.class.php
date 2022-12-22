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
 * @copyright Copyright (C) 2014-2022 by Teclib'.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/tag
 * -------------------------------------------------------------------------
 */

use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Route;
use Glpi\Http\Request;
use Glpi\Http\Response;

#[Route(path: '/Tag', priority: 1, tags: ['Tag'])]
final class PluginTagApicontroller extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        return [
            'Tag' => [
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => PluginTagTag::class,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'x-readonly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                ]
            ]
        ];
    }

    #[Route(path: '/', methods: ['GET'])]
    #[Doc\Route(
        description: 'List or search tags'
    )]
    public function getTags(Request $request): Response
    {
        return \Glpi\Api\HL\Search::searchBySchema($this->getKnownSchema('Tag'), $request->getParameters());
    }

    #[Route(path: '/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Get a tag by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the tag',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ]
    )]
    public function getTag(Request $request, int $id): Response
    {
        return \Glpi\Api\HL\Search::getOneBySchema($this->getKnownSchema('Tag'), $request->getAttributes(), $request->getParameters(), $id);
    }

    #[Route(path: '/', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new tag', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'Tag',
        ]
    ])]
    public function createTag(Request $request): Response
    {
        return \Glpi\Api\HL\Search::createBySchema($this->getKnownSchema('Tag'), $request->getParameters(), 'getTag');
    }

    #[Route(path: '/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a tag by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the tag',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ],
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'type' => Doc\Schema::TYPE_OBJECT,
                'schema' => 'Tag',
            ]
        ],
        responses: [
            ['schema' => 'Tag']
        ]
    )]
    public function updateTag(Request $request, int $id): Response
    {
        return \Glpi\Api\HL\Search::updateBySchema($this->getKnownSchema('Tag'), $request->getAttributes(), $request->getParameters(), $id);
    }

    #[Route(path: '/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Delete a tag by ID',
        parameters: [
            [
                'name' => 'id',
                'description' => 'The ID of the tag',
                'location' => Doc\Parameter::LOCATION_PATH,
                'schema' => ['type' => Doc\Schema::TYPE_INTEGER]
            ]
        ]
    )]
    public function deleteTag(Request $request, int $id): Response
    {
        return \Glpi\Api\HL\Search::deleteBySchema($this->getKnownSchema('Tag'), $request->getAttributes(), $request->getParameters(), $id);
    }
}
