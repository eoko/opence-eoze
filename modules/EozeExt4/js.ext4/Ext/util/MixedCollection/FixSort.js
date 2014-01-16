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
 *
 * @since 2013-07-30 15:29
 */
Ext.define('Eoze.Ext.util.MixedCollection.FixSort', {
	override: 'Ext.util.MixedCollection'
}, function() {
	if (Ext.getVersion().isLessThan('4.2.1')) {
		this.prototype._sort = function(property, dir, fn) {
			var me = this,
				i, len,
				dsc   = String(dir).toUpperCase() == 'DESC' ? -1 : 1,

				c     = [],
				keys  = me.keys,
				items = me.items,
				o;

			fn = fn || function(a, b) {
				return a - b;
			};

			for (i = 0, len = items.length; i < len; i++) {
				c[c.length] = {
					key  : keys[i],
					value: items[i],
					index: i
				};
			}

			// rx: Replaced line
			//Ext.Array.sort(items, function(a, b) {
			Ext.Array.sort(c, function(a, b) {
				return fn(a[property], b[property]) * dsc ||

					(a.index < b.index ? -1 : 1);
			});

			for (i = 0, len = c.length; i < len; i++) {
				o = c[i];
				items[i] = o.value;
				keys[i]  = o.key;
				me.indexMap[o.key] = i;
			}
			me.generation++;
			me.indexGeneration = me.generation;
			me.fireEvent('sort', me);
		};
	}
});
})(window.Ext4 || Ext.getVersion && Ext);
