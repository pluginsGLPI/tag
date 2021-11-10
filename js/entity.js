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

$(function() {
    // only where main tabs exist (probably in create/edit form of items)
    if ($('#tabspanel').length == 0) {
        return;
    }

    $(document).on('glpi.tab.loaded', function() {
        setTimeout(function() {
            setEntityTag();
        }, 100);
    });
});

var setEntityTag = function() {
    $('.entity-name, .entity-badge')
        .each(function() {
            var entity_element = $(this);
            entity_name = entity_element.attr('title');
            if (entity_element.hasClass('tags_already_set')) {
                return; // consider this return as a continue in a jquery each
            }
            entity_element.addClass('tags_already_set');

            $.ajax({
                url: CFG_GLPI.root_doc + '/' + GLPI_PLUGINS_PATH.tag + '/ajax/get_entity_tags.php',
                data: {
                'name': entity_name,
                },
                success: function(response) {
                entity_element.html(function() {
                    if ($(this).html().indexOf(')') > 0) {
                        return $(this).html().replace(/\)$/, response + ')');
                    } else {
                        return $(this).html() + response;
                    }
                });
                }
            });
        });
};
