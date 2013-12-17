(function(Ext) {
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
 * Adds {@link #normalize} and {@link #hash} methods to {@link Ext.Object}.
 *
 * @since 2013-06-25 19:22
 */
Ext.define('Eoze.Ext.Object', {
	override: 'Ext.Object'

	/**
	 * Returns a normalized copy of the object. Normalized here means that the object itself, and all
	 * objects that it contains, either directly or indirectly in a child object or array, will have
	 * their keys sorted alphabetically.
	 *
	 * This method is mainly used by {@link #hash} to ensure that objects which contain the same
	 * values produce the same hash.
	 *
	 * @param {Object} object
	 * @return {Object}
	 */
	,normalize: function() {

		var isObject = Ext.isObject,
			isArray = Ext.isArray;

		function normalize(value) {
			return isObject(value)
				? normalizeObject(value)
				: (isArray(value) ? normalizeArray(value) : value);
		}

		function normalizeObject(object) {
			var normalizedKeys = Ext.Object.getKeys(object).sort(),
				normalizedObject = {};

			normalizedKeys.forEach(function(name) {
				normalizedObject[name] = normalize(object[name]);
			});

			return normalizedObject;
		}

		function normalizeArray(array) {
			var normalizedArray = [];
			for (var i=0, l=array.length; i<l; i++) {
				normalizedArray.push(normalize(array[i]));
			}
		}

		return function(object) {
			return normalize(object);
		}
	}()

	/**
	 * Produces a hash from the specified object. Two objects that contain the same values but not
	 * necessarily in the same order will produce the same hash.
	 *
	 * @param {Object} object
	 * @return {String}
	 */
	,hash: function(object) {
		return Ext.encode(Ext.Object.normalize(object));
	}

});
})(Ext4);
