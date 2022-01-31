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

class PluginTagProfile extends Profile {

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      return self::createTabEntry(__('Tag management', 'tag'));
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $tagprofile = new self();
      $tagprofile->showForm($item->getID());
      return true;
   }

   /**
    * Print the Tag plugin right form for the current profile
    *
    * @param int  $profiles_id  Current profile ID
    * @param bool $openform     Open the form (true by default)
    * @param bool $closeform    Close the form (true by default)
   **/
   function showForm($profiles_id = 0, $openform = true, $closeform = true) {
      global $CFG_GLPI;

      if (!self::canView()) {
         return false;
      }

      echo "<div class='spaced'>";
      $profile = new Profile();
      $profile->getFromDB($profiles_id);
      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
          && $openform) {
         echo "<form method='post' action='".$profile->getFormURL()."'>";
      }

      $rights = [['itemtype'  => 'PluginTagTag',
                            'label'     => PluginTagTag::getTypeName(Session::getPluralNumber()),
                            'field'     => 'plugin_tag_tag']];
      $matrix_options['title'] = __('Tag management', 'tag');
      $profile->displayRightsChoiceMatrix($rights, $matrix_options);

      if ($canedit
          && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }
}