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

    applyTagColors($select) {
        const selectedIds = $select.find('option:selected').map(function() {
            return $(this).val();
        }).get();

        const $container = $select.nextAll('.select2').find('.select2-selection__rendered');
        $container.find('.select2-selection__choice').each((index, element) => {
            const id = selectedIds[index];
            const color = this.tagsColor[id];
            if (color) {
                $(element).css('background-color', color);
                $(element).css('color', this.isDark(color) ? '#eeeeee' : '');

                // Also style the remove button for better visibility
                $(element).find('.select2-selection__choice__remove').css('color', this.isDark(color) ? '#eeeeee' : '');
            }
        });
    }

    init() {
        const $select = this.$container.find(this.selector);

        $select.each((index, element) => {
            this.applyTagColors($(element));
        });

        $select.on('change select2:select select2:unselect', (event) => {
            this.applyTagColors($(event.target));
        });

        $select.on('select2:open', () => {
            setTimeout(() => {
                $('.select2-results__option').each((index, element) => {
                    const matches = element.id.match(/result-[^-]+-(\d+)$/);
                    if (matches && matches[1]) {
                        const color = this.tagsColor[matches[1]];
                        // Cible uniquement le span SANS la classe select2-rendered__match
                        $(element).find('span:not(.select2-rendered__match)').css({
                            'background-color': color ? color : '',
                            'padding': color ? '2px' : '',
                            'color': (color && this.isDark(color)) ? '#fff' : '',
                            'border-radius': '2px'
                        });
                    }
                });
            }, 0);
        });
    }
}
