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

    isDark(hexColor) {
        if (!hexColor) return false;
        hexColor = hexColor.replace('#', '');
        const r = parseInt(hexColor.substr(0, 2), 16);
        const g = parseInt(hexColor.substr(2, 2), 16);
        const b = parseInt(hexColor.substr(4, 2), 16);
        const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        return luminance < 0.5;
    }

    getBackgroundColor(option) {
        return this.tagsColor[option.id] ?? '#DDDDDD';
    }

    tagStyle(option) {
        const backgroundColor = this.getBackgroundColor(option);
        return {
            'background-color': backgroundColor,
            'color': this.isDark(backgroundColor) ? '#fff' : '',
            'padding': '2px 4px',
            'border-radius': '2px',
        }
    }

    applyTagColorsResults(option) {
        if (option.itemtype == 'Entity') {
            return $('<span></span>').text(option.text);
        }
        return $('<span class="tag_choice"></span>')
            .text(option.text)
            .css(this.tagStyle(option));
    }

    applyTagColorsSelection(option, container) {
        $(container).css(this.tagStyle(option));
        return $('<span></span>').text(option.text);
    }

    init() {
        const $select = this.$container.find(this.selector);
        const select2Instance = $select.data('select2');
        if (!select2Instance) {
            return;
        }
        select2Instance.options.set('templateResult', this.applyTagColorsResults.bind(this));
        select2Instance.options.set('templateSelection', this.applyTagColorsSelection.bind(this));

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
