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
 * @copyright Copyright (C) 2014-2026 by Teclib'.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/tag
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Tag\Controller;

use CommonDBTM;
use Glpi\Controller\GenericFormController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Http\RedirectResponse;
use Html;
use PluginTagTag;
use PluginTagTagItem;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TagItemController extends GenericFormController
{
    #[Route('/associate', methods: ['POST'])]
public function associate(Request $request): Response
    {
        if ($request->query->getInt('associate') === 1) {
            Session::checkLoginUser();

            $tag_id = $request->request->getInt('plugin_tag_tags_id');
            $itemtype = $request->request->get('itemtype');
            $item_id = $request->request->getInt('items_id');

            if (!$tag_id || !$itemtype || !$item_id) {
                throw new BadRequestHttpException(__s('Missing parameters', 'tag'));
            }

            $tag = new PluginTagTag();
            if (!$tag->getFromDB($tag_id) || !$tag->can($tag_id, UPDATE)) {
                throw new AccessDeniedHttpException(__s('You do not have permission to update this tag', 'tag'));
            }

            if (!is_a($itemtype, CommonDBTM::class, true) || !PluginTagTag::canItemtype($itemtype)) {
                throw new BadRequestHttpException(__s('Invalid item type', 'tag'));
            }

            $item = new $itemtype();
            if (!$item->getFromDB($item_id) || !$item->canUpdateItem()) {
                throw new AccessDeniedHttpException(__s('You do not have permission to update this item', 'tag'));
            }

            $tag_item = new PluginTagTagItem();
            $found = $tag_item->find([
                'plugin_tag_tags_id' => $tag_id,
                'items_id'           => $item_id,
                'itemtype'           => $itemtype,
            ]);

            if (count($found) === 0) {
                $tag_item->add([
                    'plugin_tag_tags_id' => $tag_id,
                    'items_id'           => $item_id,
                    'itemtype'           => $itemtype,
                ]);
            }

            return new RedirectResponse(Html::getBackUrl());
        }
    }
}
