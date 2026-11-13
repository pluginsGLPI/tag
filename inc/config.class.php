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

class PluginTagConfig extends CommonDBTM
{
    protected static $notable = true;

    /**
     * Configured location of the tags field, memoized for the current request.
     */
    private static ?string $tags_location = null;

    /**
     * Get the configured location of the tags field in item forms.
     *
     * @return string '1' for the bottom of the form, '0' for the top (default)
     */
    public static function getTagsLocation(): string
    {
        return self::$tags_location ??= (string) (Config::getConfigurationValues('plugin:Tag', ['tags_location'])['tags_location'] ?? '0');
    }

    /**
     * Forget the memoized location.
     */
    public static function resetTagsLocation(): void
    {
        self::$tags_location = null;
    }

    /**
     * Called by core when the configuration form of the plugin is saved (`config_class` input).
     *
     * @param array $input submitted configuration values
     * @return array
     */
    public static function configUpdate($input)
    {
        self::resetTagsLocation();
        return $input;
    }

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

        echo "<form name='form' action=\"" . Toolbox::getItemTypeFormURL('Config') . "\" method='post'>";
        echo "<input type='hidden' name='config_class' value='" . self::class . "'>";
        echo "<input type='hidden' name='config_context' value='plugin:Tag'>";
        echo "<div class='center' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'><thead>";
        echo "<th colspan='4'>" . __s('Tag Management', 'tag') . '</th></thead>';
        echo '<td>' . __s('Tags location', 'tag') . '</td><td>';
        Dropdown::showFromArray(
            'tags_location',
            [
                __s('Top'),
                __s('Bottom'),
            ],
            [
                'value'  => $config['tags_location'] ?? 0,
            ],
        );
        echo '</td></tr>';
        echo '</table>';

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='4' class='center'>";
        echo "<input type='submit' name='update' class='submit' value=\"" . _sx('button', 'Save') . '">';
        echo '</td></tr>';
        echo '</table>';
        echo '</div>';
        Html::closeForm();
        return null;
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
        self::resetTagsLocation();
        return true;
    }
}
