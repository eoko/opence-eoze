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
 * Implements {@link Ext.util.Filter#getRemoteData} option.
 *
 * @since 2013-08-27 12:39
 */
Ext.define('Eoze.Ext.data.proxy.Server.FilterGetRemoteData', {
	override: 'Ext.data.proxy.Server'

	// Adding support for `getRemoteData`
	,encodeFilters: function(filters) {
		var min = [],
			length = filters.length,
			i = 0,
			filter, data;

		for (; i < length; i++) {
			filter = filters[i];
			if (filter.getRemoteData) {
				data = filter.getRemoteData();
				if (!Ext.isEmpty(data)) {
					if (Ext.isArray(data)) {
						min = min.concat(data);
					} else {
						min.push(data);
					}
				}
			} else {
				min.push({
					property: filter.property,
					value: filter.value
				});
			}
		}
		return this.applyEncoding(min);
	}
});

/**
 * @class Ext.util.Filter
 * @cfg {Function} getRemoteData
 *
 * If present, this method will be used by {@link Ext.data.proxy.Server#encodeFilter}
 * instead of the default strategy, to get the filter data to send to the remote
 * server.
 *
 * @return {Array/Object/undefined}
 */

})(window.Ext4 || Ext.getVersion && Ext);
