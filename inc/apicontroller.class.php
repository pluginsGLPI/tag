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
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\Request;
use Glpi\Http\Response;

#[Route(path: '/Plugin/Tag', priority: 1, tags: ['PluginTag'])]
final class PluginTagApicontroller extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        return [
            'PluginTag' => [
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
    #[RouteVersion('2.3.0')]
    #[Doc\SearchRoute('PluginTag')]
    public function getTags(Request $request): Response
    {
        return ResourceAccessor::searchBySchema(
            $this->getKnownSchema('PluginTag', $this->getAPIVersion($request)),
            $request->getParameters()
        );
    }

    #[Route(path: '/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[RouteVersion('2.3.0')]
    #[Doc\GetRoute('PluginTag')]
    public function getTag(Request $request, int $id): Response
    {
        return ResourceAccessor::getOneBySchema(
            $this->getKnownSchema('PluginTag', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters(),
            $id
        );
    }

    #[Route(path: '/', methods: ['POST'])]
    #[RouteVersion('2.3.0')]
    #[Doc\CreateRoute('PluginTag')]
    public function createTag(Request $request): Response
    {
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema('PluginTag', $this->getAPIVersion($request)),
            $request->getParameters(),
            [self::class, 'getTag']
        );
    }

    #[Route(path: '/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[RouteVersion('2.3.0')]
    #[Doc\UpdateRoute('PluginTag')]
    public function updateTag(Request $request, int $id): Response
    {
        return ResourceAccessor::updateBySchema(
            $this->getKnownSchema('PluginTag', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters(),
        );
    }

    #[Route(path: '/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\DeleteRoute('PluginTag')]
    public function deleteTag(Request $request, int $id): Response
    {
        return ResourceAccessor::deleteBySchema(
            $this->getKnownSchema('PluginTag', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters(),
            $id
        );
    }
}
