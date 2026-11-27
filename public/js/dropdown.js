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

(function () {
    'use strict';

    // Loaded once per page through ADD_JAVASCRIPT (setup.php), in the page footer.
    // dropdown_init.js handles markers inserted later (AJAX); markers already in the page are handled below.
    window.PluginTagDropdown = {
        colorize: function (marker, $container) {
            var colors = JSON.parse(marker.getAttribute('data-tag-colors'));
            var selector = 'select[data-glpi-plugin-tag-dropdown-uuid="' + marker.getAttribute('data-tag-dropdown-uuid') + '"]';
            import(GLPI_PLUGINS_PATH.tag + '/js/modules/TagDropdownColorizer.js').then(function (m) {
                new m.GlpiPluginTagTagDropdownColorizer(colors, selector, $container);
            });
        },
        initMarkers: function (scope) {
            scope.querySelectorAll('[data-tag-colorizer]:not([data-tag-colorizer-ready])').forEach(function (marker) {
                marker.setAttribute('data-tag-colorizer-ready', '');
                window.PluginTagDropdown.colorize(marker, $(document));
            });
        }
    };

    // Markers rendered inline before the footer (e.g. /Form/Render) ran dropdown_init.js before this file existed.
    window.PluginTagDropdown.initMarkers(document);

    $(document).on('glpi-form-editor-question-type-changed', function (event, question, type) {
        if (type !== 'PluginTagQuestionType') {
            return;
        }
        $(question).find('[data-tag-colorizer]').each(function () {
            window.PluginTagDropdown.colorize(this, question);
        });
    });

    $(document).on('click', '#save_tag', function (e) {
        e.preventDefault();
        var $button = $(this);
        $.ajax({
            url: $button.attr('data-tag-url'),
            type: 'POST',
            data: {
                'itemtype': $button.attr('data-tag-itemtype'),
                'items_id': $button.data('tagItemsId'),
                '_plugin_tag_tag_values': $('select[data-glpi-plugin-tag-dropdown-uuid="' + $button.attr('data-tag-dropdown-uuid') + '"]').val(),
                '_plugin_tag_tag_process_form': 1,
            }
        }).done(function () {
            displayAjaxMessageAfterRedirect();
            window.glpiUnsavedFormChanges = false;
        });
    });
})();
