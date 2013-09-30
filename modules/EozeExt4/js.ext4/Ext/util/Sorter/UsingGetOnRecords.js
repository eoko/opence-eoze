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
 * Makes the {@link #defaultSorterFn} use the {@link Ext.data.Model#get} method when the passed objects
 * to compare are models, instead of trying to access the `data` property directly.
 *
 * This override is needed by {@link Eoze.Ext.data.model.ExpandableFieldNames}.
 *
 * @since 2013-06-11 12:50
 */
Ext4.define('Eoze.Ext.util.Sorter.UsingGetOnRecords', {
	override: 'Ext.util.Sorter'

	,defaultSorterFn: function(o1, o2) {
		var me = this,
			transform = me.transform,
			v1 = me.getValue(o1),
			v2 = me.getValue(o2);

		if (transform) {
			v1 = transform(v1);
			v2 = transform(v2);
		}

		return v1 > v2 ? 1 : (v1 < v2 ? -1 : 0);
	}

	,getValue: function(o1) {
		return o1.isModel
			? o1.get(this.property)
			: this.getRoot(o1)[this.property];
	}

});
