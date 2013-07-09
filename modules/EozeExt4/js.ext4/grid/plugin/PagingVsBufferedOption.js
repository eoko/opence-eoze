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
 * @since 2013-07-09 16:40
 */
Ext.define('Eoze.grid.plugin.PagingVsBufferedOption', {

	/**
	 * True to enable grid paging.
	 *
	 * @cfg {Boolean}
	 */
	pagingEnabled: null

	/**
	 * @property {Boolean}
	 * @private
	 */
	,defaultPagingEnabled: true

	,init: function(grid) {

		this.grid = grid;

		grid.addEvents('pagingtoggled');

		grid.addPlugin({
			ptype: 'bufferedrenderer'
		});

		// --- Methods ---

		Ext.apply(grid, {
			createStore: Ext.bind(this.createStore, this)

			,isPagingEnabled: this.isPagingEnabled
			,setPagingEnabled: this.setPagingEnabled
		});

		// --- State ---

		// This has to be done before plugin initialization to be taken into account...
		//grid.stateEvents = grid.stateEvents || [];
		//grid.stateEvents.push('pagingtoggled', 'beforedestroy');

		// Initial state
		var initialEnabled = grid.lastState && grid.lastState.pagingEnabled;

		grid.applyState = Ext.Function.createSequence(
			grid.applyState
			,function(state) {
				this.setPagingEnabled(state.pagingEnabled, true);
			}
		);

		var uberGetState = grid.getState;
		grid.getState = function() {
			var state = uberGetState.apply(this, arguments);
			state.pagingEnabled = this.isPagingEnabled();
			return state;
		};

		grid.on({
			delay: 10
			,afterrender: function() {
				this.setPagingEnabled(initialEnabled, true);
			}
		});
	}

	/**
	 * Creates a store for this grid, either buffered or not.
	 *
	 * @param {Boolean} paginated
	 * @param {Object} config
	 * @return {Ext.data.Store}
	 */
	,createStore: function(paginated, config) {
		var grid = this.grid;
		return Ext4.create('Ext.data.Store', Ext.apply({
			model: grid.model

			,remoteSort: paginated
			,remoteFilter: paginated
			,autoLoad: true

			,buffered: false
			,pageSize: paginated ? 100 : 999999
		}, config));
	}

	/**
	 * Enable or disable grid paging.
	 *
	 * @param {Boolean} enabled
	 * @param {Boolean} force
	 */
	,setPagingEnabled: function(enabled, force) {
		var grid = this,
			previousStore = grid.getStore(),
			pagingToolbar = grid.down('#pagingToolbar'),
			filters = grid.filters;

		if (!force && enabled === this.pagingEnabled) {
			return;
		}

		this.pagingEnabled = enabled;

		if (!this.rendered) {
			return;
		}

		if (enabled) {
			grid.bindStore(grid.createStore(true));
			pagingToolbar.bindStore(grid.getStore());
		} else {
			grid.bindStore(grid.createStore(false));
			pagingToolbar.bindStore(null);
		}

		// Column filters
		if (filters) {
			filters.local = !enabled;
			filters.bindStore(grid.getStore());
			filters.reload();
		}

		if (previousStore) {
			previousStore.destroyStore();
		}

		pagingToolbar.items.each(function(item) {
			if (item.itemId === 'refresh') {
				return false;
			} else {
				item.setVisible(enabled);
			}
		});

		// Fire paging toggle event
		this.fireEvent('pagingtoggled', this, enabled);
	}

	,isPagingEnabled: function() {
		return this.pagingEnabled !== null
			? this.pagingEnabled
			: this.defaultPagingEnabled;
	}

});
})(window.Ext4 || Ext.getVersion && Ext);
