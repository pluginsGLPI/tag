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

var rgb2hex = function(rgb){
   rgb = rgb.match(/^rgba?[\s+]?\([\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?/i);
   return (rgb && rgb.length === 4) ? "#" +
      ("0" + parseInt(rgb[1],10).toString(16)).slice(-2) +
      ("0" + parseInt(rgb[2],10).toString(16)).slice(-2) +
      ("0" + parseInt(rgb[3],10).toString(16)).slice(-2) : '';
};

var idealTextColor = function(hexTripletColor) {
   var nThreshold = 105;
   if (hexTripletColor.indexOf('rgb') != -1) {
      hexTripletColor = rgb2hex(hexTripletColor);
   }
   hexTripletColor = hexTripletColor.replace('#','');
   var components = {
      R: parseInt(hexTripletColor.substring(0, 2), 16),
      G: parseInt(hexTripletColor.substring(2, 4), 16),
      B: parseInt(hexTripletColor.substring(4, 6), 16)
   };
   var bgDelta = (components.R * 0.299) + (components.G * 0.587) + (components.B * 0.114);
   return ((255 - bgDelta) < nThreshold) ? "#000000" : "#E6E6E6";
}

var formatOptionSelection = function(option, container) {
   var color = (typeof option.color !== 'undefined' && option.color !== '')
      ? option.color
      : '#DDDDDD';
   var invertedcolor = idealTextColor(color);

   $(container)
      .css("background-color", color)
      .css("border-color", invertedcolor)
      .css("color", invertedcolor)
      .children('.select2-selection__choice__remove')
         .css("color", invertedcolor);

   var _elt = $('<span></span>');
   _elt.html(escapeMarkupText(option.text));
   return _elt;
};

var formatOptionResult = function(option) {

   var color = (typeof option.color !== 'undefined' && option.color !== '')
      ? option.color
      : '#DDDDDD';
   var invertedcolor = idealTextColor(color);

   var template = `
      <span class="tag_choice" style="background-color: ${color}; color: ${invertedcolor}">
         ${option.text}
      </span>
   `;

   return $(template);
};
