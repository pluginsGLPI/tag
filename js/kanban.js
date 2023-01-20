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
      const tags = card.data('tags') ?? {};
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

$(document).on('click', '.kanban .kanban-item .kanban-plugin-content .tag_choice', (e) => {
   const clicked_tag = $(e.currentTarget);
   const kanban_obj = clicked_tag.closest('.kanban').data('js_class');
   const tag_value = clicked_tag.text();
   // Quote tag_value if it contains spaces
    const tag_value_quoted = tag_value.includes(' ') ? `"${tag_value}"` : tag_value;
   /** @type {SearchInput} */
   const filter_input = kanban_obj.filter_input;
   const token = {
      term: tag_value_quoted,
      tag: 'tag',
      raw: 'tag:' + tag_value_quoted,
   };
   // This param should be a SearchToken but we cannot import in this file
   const new_filter_node = filter_input.tokenToTagHtml(token);
   filter_input.displayed_input.find('.search-input-tag[data-tag="tag"]').remove();
   // Insert new filter node before the ".search-input-tag-input" node
   const last_inserted = $(new_filter_node).insertBefore(filter_input.displayed_input.find('.search-input-tag-input'));
   last_inserted.data('token', token);
   // Refresh filters
   filter_input.displayed_input.trigger('result_change');
   kanban_obj.filter();
});
