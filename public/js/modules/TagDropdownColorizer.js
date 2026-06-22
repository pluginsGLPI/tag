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

export class GlpiPluginTagTagDropdownColorizer {
    constructor(tagsColor, selector, $container) {
        this.tagsColor = tagsColor;
        this.selector = selector;
        this.$container = $container;

        this.init();
    }

    /**
     * Check if color is more dark than light.
     * 
     * @param {string} hexColor 
     * @returns {boolean}
     */
    isDark(hexColor) {
        if (!hexColor) return false;
        hexColor = hexColor.replace('#', '');
        const r = parseInt(hexColor.substr(0, 2), 16);
        const g = parseInt(hexColor.substr(2, 2), 16);
        const b = parseInt(hexColor.substr(4, 2), 16);
        const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        return luminance < 0.5;
    }

    /**
     * Get background color for a given tag options.
     * 
     * @param {array} options 
     * @returns {string}
     */
    getBackgroundColor(options) {
        return this.tagsColor[options.id] ?? '#DDDDDD';
    }

    /**
     * Get style for a given tag options.
     * 
     * @param {array} options 
     * @returns {object}
     */
    tagStyle(options) {
        const backgroundColor = this.getBackgroundColor(options);
        return {
            'background-color': backgroundColor,
            'color': this.isDark(backgroundColor) ? '#fff' : '',
            'padding': '2px 4px',
            'border-radius': '2px',
        }
    }

    /**
     * Apply tag colors to the results of the select2 dropdown.
     * 
     * @param {array} options 
     * @returns {object}
     */
    applyTagColorsResults(options) {
        if (options.itemtype == 'Entity') {
            return;
        }
        return $('<span class="tag_choice"></span>')
            .text(options.text)
            .css(this.tagStyle(options));
    }

    /**
     * Apply tag colors to the selected items of the select2 dropdown.
     * 
     * @param {array} options 
     * @param {HTMLElement} container
     * @returns {object}
     */
    applyTagColorsSelection(options, container) {
        $(container).css(this.tagStyle(options));
        return $('<span></span>').text(options.text);
    }

    /**
     * Initialize the colorizer for the select2 dropdown.
     * 
     * @returns {void}
     */
    init() {
        const $select = this.$container.find(this.selector);
        const select2Instance = $select.data('select2');
        if (!select2Instance) {
            return;
        }

        //Set the templates
        select2Instance.options.set('templateResult', this.applyTagColorsResults.bind(this));
        select2Instance.options.set('templateSelection', this.applyTagColorsSelection.bind(this));

        // Apply colors to selected elements when the page loads
        const selectedIds = $select.find('option:selected').map(function() {
            return $(this).val();
        }).get();

        $select.nextAll('.select2').find('.select2-selection__choice').each((index, selected_tag) => {
            if (selectedIds[index]) {
                $(selected_tag).css(this.tagStyle({ id: selectedIds[index] }));
            }
        });
    }
}
