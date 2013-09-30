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
 * Loader for overrides of Filters feature.
 *
 * Adds support for {@link Eoze.Ext.data.Types#DAYDATE} filter.
 *
 * Replace calls to {@link Ext.data.Store#filterBy} with a unique {@link Ext.util.Filter}
 * instance. Using `filterBy` prevents any other source from using the store's filters
 * without interference. This approach fixes that.
 *
 * @since 2013-05-17 16:15
 */
Ext4.define('Eoze.Ext.ux.grid.FiltersFeature', {
	override: 'Ext.ux.grid.FiltersFeature'

	,requires: [
		'Ext.util.Filter',
		'Eoze.Ext.data.proxy.Server.FilterGetRemoteData',
		'Eoze.Ext.ux.grid.filter.DateFilter',
		'Eoze.Ext.ux.grid.filter.Filter.EmptyValues'
	]

	,constructor: function() {
		var me = this;

		this.callParent(arguments);

		// Create unique filter
		this.storeFilter = new Ext.util.Filter({
			id: Ext.id()
			,filterFn: this.getRecordFilter()
			,property: null
			,getRemoteData: function() {
				return me.buildFilterData(me.getFilterData());
			}
		});
	}

	/**
	 * Adds support for {@link Eoze.Ext.data.Types#DAYDATE}.
	 */
	,getFilterClass: function(type) {
		switch (type) {
			case 'daydate':
				type = 'date';
				break;
		}
		return this.callParent([type]);
	}

	,bindStore: function(store) {
		var previousStore = this.store,
			filter = this.storeFilter;

		if (previousStore) {
			previousStore.removeFilter(filter);
		}

		if (store && this.local) {
			store.addFilter(filter);
		}

		// Parent method only binds event listeners to load methods,
		// which have been rendered useless by the implementation
		// changes in this override.

		// this.callParent(arguments);
	}

	,reload: function() {
		var me = this,
			store = me.view.getStore(),
			filter = me.storeFilter;
		if (me.local) {
			filter.setFilterFn(me.getRecordFilter());
			store.filter(filter);
		} else {
			this.callParent(arguments);
		}
	}

	// Rendered useless by the unique filter instance strategy.
	,onLoad: function(store) {
		// Intentionally empty
	}

	// Rendered useless by the unique filter instance strategy.
	,onBeforeLoad : function (store, options) {
		// Intentionally empty
	}

	// Builds filter data to be sent to the server.
	,buildFilterData: function (filters) {
		var p = {}, i, f,
			len = filters.length,
			data = [];

		for (i = 0; i < len; i++) {
			f = filters[i];
			data.push(Ext.apply(
				{},
				// rx: adding isGridFilter tag
				{field: f.field, isGridFilter: true},
				f.data
			));
		}
		// only build if there is active filter
		if (data.length > 0){
			return data;
		}
	}

});
})(window.Ext4 || Ext.getVersion && Ext);
