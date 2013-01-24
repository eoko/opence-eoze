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
 * Eoze custom formats.
 *
 * @since 2013-01-24 19:46
 */
Ext4.define('Eoze.Ext.util.Format', {
	override: 'Ext.util.Format'

	/**
	 * Checks a reference and converts it to the default value if it's {@link Ext#isEmpty emtpy}.
	 *
	 * @param {Object} value Reference to check.
	 * @param {String} [defaultValue = ""] The value to insert if it's {@link Ext#isEmpty emtpy}.
	 */
	,ifEmpty: function(value, defaultValue) {
		return Ext.isEmpty(value)
				? (Ext.isDefined(defaultValue) ? defaultValue : '')
			 	: value;
	}
});
