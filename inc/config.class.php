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

class PluginTagConfig extends CommonDBTM
{
    protected static $notable = true;

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate && $item->getType() === 'Config') {
            return self::createTabEntry(__('Tag Management', 'tag'), 0, $item::getType(), PluginTagTag::getIcon());
        }

        return '';
    }

    public function showConfigForm()
    {
        if (!Session::haveRight('config', UPDATE)) {
            return false;
        }

        $config = Config::getConfigurationValues('plugin:Tag');

        TemplateRenderer::getInstance()->display('@tag/forms/config.html.twig', [
            'form_action'    => Toolbox::getItemTypeFormURL('Config'),
            'config_class'   => self::class,
            'config_context' => 'plugin:Tag',
            'tags_location'  => (int) ($config['tags_location'] ?? 0),
        ]);

        return true;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == 'Config') {
            $config = new self();
            $config->showConfigForm();
        }

        return true;
    }

    public static function uninstall()
    {
        $config = Config::getConfigurationValues('plugin:Tag');
        Config::deleteConfigurationValues('plugin:Tag', array_keys($config));
        return true;
    }
}
