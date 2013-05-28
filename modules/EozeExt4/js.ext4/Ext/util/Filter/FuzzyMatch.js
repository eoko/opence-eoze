/**
 * Copyright (C) 2013 Eoko
 *
 * This file is part of Opence.
 *
 * Opence is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Opence is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Opence. If not, see <http://www.gnu.org/licenses/gpl.txt>.
 *
 * @copyright Copyright (C) 2013 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

/**
 * This override adds a {@link #fuzzyMatch} option to Filter.
 *
 * @since 2013-05-28 14:15
 */
Ext4.define('Eoze.Ext.util.Filter.FuzzyMatch', {
	override: 'Ext.util.Filter'

	/**
	 * True to allow individual words in the search string to match any other word in the subject.
	 *
	 * If true, this option will interact with {@link #anyMatch}, and {@link #exactMatch} in the
	 * following way:
	 *
	 * -   If `anyMatch` is true, then individual words in the search string will match any part of
	 *     searched words;
	 *
	 * -   Else, if `exactMatch` is true (and `anyMatch` is false), the individual words will match only
	 *     full words; while if `exactMatch` is false, search words will match the beginning of any
	 *     word in the searched string.
	 *
	 * @cfg {Boolean}
	 */
	,fuzzyMatch: false

	/**
	 * @inheritdoc
	 */
	,createValueMatcher: function() {
		var me = this,
			value = me.value,
			anyMatch = me.anyMatch,
			exactMatch = me.exactMatch,
			fuzzyMatch = true,
			caseSensitive = me.caseSensitive,
			escapeRe = Ext.String.escapeRegex,
			parts;

		if (value === null) {
			return value;
		}

		if (!value.exec) { // not a regex
			value = String(value);

			if (fuzzyMatch) {
				parts = [];
				value.split(' ').forEach(function(value) {
					if (anyMatch === true) {
						value = escapeRe(value);
					} else {
						value = '\\b' + escapeRe(value);
						if (exactMatch === true) {
							value += '\\b';
						}
					}
					parts.push(new RegExp(value, caseSensitive ? '' : 'i'));
				});
				return {
					test: function(value) {
						var i = parts.length;
						while (i--) {
							if (!parts[i].test(value)) {
								return false;
							}
						}
						return true;
					}
				};
			} else {
				if (anyMatch === true) {
					value = escapeRe(value);
				} else {
					value = '^' + escapeRe(value);
					if (exactMatch === true) {
						value += '$';
					}
				}
			}
			value = new RegExp(value, caseSensitive ? '' : 'i');
		}

		return value;
	}

});
