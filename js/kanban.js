/**
 * -------------------------------------------------------------------------
 * Tag plugin for GLPI
 * Copyright (C) 2003-2021 by the Tag Development Team.
 *
 * https://github.com/pluginsGLPI/tag
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
 *  --------------------------------------------------------------------------
 */

/**
 * Kanban plugin for Tags
 */
$(document).on('kanban:refresh_tokenizer', (e, tokenizer) => {
   // Refresh tokenizer
   tokenizer.setAutocomplete('tagged', ["true", "false"]);
});

$(document).on('kanban:filter', (e, data) => {
   const filters = data.filters;

   $(data.kanban_element + ' .kanban-item').each(function(i, item) {
      const card = $(item);

      let shown = true;
      const tags = card.data('tags');
      //lowercase tags
      const tags_lower = Object.values(tags).map(tag => tag.toLowerCase());
      const tagged = tags_lower.length > 0;

      if (filters.tagged !== undefined) {
         const tagged_term = filters.tagged.term.toLowerCase() === 'false' ? false : true;
         if ((tagged !== tagged_term) !== filters.tagged.exclusion) {
            shown = false;
         }
      }

      if (filters.tag !== undefined) {
         if (!tags_lower.includes(filters.tag.term.toLowerCase()) !== filters.tag.exclusion) {
            shown = false;
         }
      }

      if (!shown) {
         card.addClass('filtered-out');
      }
   });
});
