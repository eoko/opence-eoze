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
 * Adds support for keeping empty values last in every sorting operations.
 *
 * @since 2013-06-12 10:25
 */
Ext4.define('Eoze.Ext.util.Sorter.EmptyValuesAlwaysLast', {
	override: 'Ext.util.Sorter'

	/**
	 * True to keep empty values last in any sort.
	 *
	 * @cfg {Boolean}
	 */
	,emptyValuesLast: true

	,requires: [
		'Eoze.Ext.util.Sorter.UsingGetOnRecords'
	]

	,createSortFunction: function(sorterFn) {
		var me        = this,
			direction = me.direction || "ASC",
			modifier  = direction.toUpperCase() == "DESC" ? -1 : 1;

		//create a comparison function. Takes 2 objects, returns 1 if object 1 is greater,
		//-1 if object 2 is greater or 0 if they are equal
		return function(o1, o2) {
			var v1 = me.getValue(o1),
				v2 = me.getValue(o2);

			if (me.emptyValuesLast) {
				var empty1 = v1 === undefined || v1 === null || v1 === '',
					empty2 = v2 === undefined || v2 === null || v2 === '';

				if (empty1) {
					if (empty2) {
						return 0;
					} else {
						return 1;
					}
				} else if (empty2) {
					return -1;
				}
			}

			return modifier * sorterFn.call(me, o1, o2);
		};
	}

});
