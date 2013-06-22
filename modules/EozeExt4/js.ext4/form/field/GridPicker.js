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
 * @since 2013-06-19 21:55
 */
Ext.define('Eoze.form.field.GridPicker', {
	extend: 'Ext.form.field.ComboBox'

	,requires: [
		'Eoze.Ext.form.field.ComboBox.GetOptions',
		'Ext.grid.Panel',
		'Eoze.form.field.GridPickerKeyNav'
	]

	,defaultGridConfig: {
		xclass: 'Ext.grid.Panel'

		,floating: true
		,focusOnToFront: false
		,resizable: true

		,hideHeaders: true
		,stripeRows: false

		,viewConfig: {
//			stripeRows: false
		}
		,rowLines: false

//		,tbar: [{text: 'Top item'}]
//		//			,bbar: [{text: 'Bot item'}]
//		,bbar: {
//			xtype: 'pagingtoolbar'
//		}

		,initComponent: function() {
			Ext.grid.Panel.prototype.initComponent.apply(this, arguments);

			var store = this.getStore();

			this.query('pagingtoolbar').forEach(function(pagingToolbar) {
				pagingToolbar.bindStore(store);
			});
		}
	}

	/**
	 * Configuration object for the picker grid. It will be merged with {@link #defaultGridConfig}
	 * before creating the grid with {@link #createGrid}.
	 *
	 * @cfg {Object}
	 */
	,gridConfig: null

//	/**
//	 * @cfg {Boolean}
//	 */
//	,multiSelect: false

	/**
	 * Overriden: delegates to {@link #createGrid}.
	 *
	 * @protected
	 */
	,createPicker: function() {
		// We must assign it for Combo's onAdded method to work
		return this.picker = this.createGrid();
	}

	/**
	 * Creates the picker's grid.
	 *
	 * @protected
	 */
	,createGrid: function() {
		var grid = Ext.create(this.getGridConfig());
		this.bindGrid(grid);
		return grid;
	}

	,setValue: function() {
		this.callParent(arguments);
	}

//	,onAdded: function() {
//		var me = this;
//		me.callParent(arguments);
//		if (me.getPicker()) {
//			debugger
//			me.picker.ownerCt = me.up('[floating]');
//			me.picker.registerWithOwnerCt();
//		}
//	}

	/**
	 * @return {Ext.grid.Panel}
	 */
	,getGrid: function() {
		return this.getPicker();
	}

	/**
	 * Gets the configuration for the picked's grid.
	 *
	 * The returned object will be modified, so it must be an instance dedicated to
	 * this object.
	 *
	 * @return {Object}
	 * @protected
	 */
	,getGridConfig: function() {
		var config = {};
		Ext.apply(config, this.defaultGridConfig);
		Ext.apply(config, this.gridConfig);

		Ext.applyIf(config, {
			store: this.store
			,columns: columns = [{
				dataIndex: this.displayField || this.valueField
				,flex: 1
			}]
		});

		return config;
	}

	,bindGrid: function(grid) {

		this.grid = grid;

//		grid.ownerCt = this;
//		grid.registerWithOwnerCt();

		this.mon(grid, {
			scope: this

			,itemclick: this.onItemClick
			,refresh: this.onListRefresh

			,beforeselect: this.onBeforeSelect
			,beforedeselect: this.onBeforeDeselect
			,selectionchange: this.onListSelectionChange

			// fix the fucking buffered view!!!
			,afterlayout: function(grid) {
				if (grid.getStore().getCount())
				if (!grid.fixingTheFuckingLayout) {
					var el = grid.getView().el;
					grid.fixingTheFuckingLayout = true
					el.setHeight('100%');
					el.setStyle('overflow-x', 'hidden');
					grid.fixingTheFuckingLayout = false;
				}
			}

		});

		// Prevent deselectAll, that is called liberally in combo box code, to actually deselect
		// the current value
		var me = this,
			sm = grid.getSelectionModel(),
			uber = sm.deselectAll;
		sm.deselectAll = function() {
			if (!me.ignoreSelection) {
				uber.apply(this, arguments);
			}
		};
	}

	,highlightRecord: function(record) {
		var grid = this.getGrid(),
			sm = grid.getSelectionModel(),
			view = grid.getView(),
			node = view.getNode(record),
			bufferedPlugin = grid.plugins.filter(function(p) {
				return p instanceof Ext4.grid.plugin.BufferedRenderer
			})[0];

		sm.select(record, false, true);

		if (node) {
			Ext.fly(node).scrollIntoView(view.el, false);
		} else if (bufferedPlugin) {
			bufferedPlugin.scrollTo(grid.store.indexOf(record));
		}
	}

	,highlightAt: function(index) {
		var grid = this.getGrid(),
			sm = grid.getSelectionModel(),
			view = grid.getView(),
			node = view.getNode(index),
			bufferedPlugin = grid.plugins.filter(function(p) {
				return p instanceof Ext4.grid.plugin.BufferedRenderer
			})[0];

		sm.select(index, false, true);

		if (node) {
			Ext.fly(node).scrollIntoView(view.el, false);
		} else if (bufferedPlugin) {
			bufferedPlugin.scrollTo(index);
		}
	}

	// --- combo

	,onExpand: function() {
		var me = this,
			keyNav = me.listKeyNav,
			selectOnTab = me.selectOnTab,
			picker = me.getPicker();

		// Handle BoundList navigation from the input field. Insert a tab listener specially to enable selectOnTab.
		if (keyNav) {
			keyNav.enable();
		} else {
			keyNav = me.listKeyNav = Ext.create('Eoze.form.field.GridPickerKeyNav', {
				target: this.inputEl
				,forceKeyDown: true
				,pickerField: this
				,grid: this.getGrid()

				,tab: function(e) {
					if (selectOnTab) {
						this.selectHighlighted(e);
						me.triggerBlur();
					}
					// Tab key event is allowed to propagate to field
					return true;
				}
			});
		}

		// While list is expanded, stop tab monitoring from Ext.form.field.Trigger so it doesn't short-circuit selectOnTab
		if (selectOnTab) {
			me.ignoreMonitorTab = true;
		}

		Ext.defer(keyNav.enable, 1, keyNav); //wait a bit so it doesn't react to the down arrow opening the picker

		this.focusWithoutSelection(10);
	}

	,focusWithoutSelection: function(delay) {
		function focus() {
			var me = this,
				previous = me.selectOnFocus;
			me.selectOnFocus = false;
			me.inputEl.focus();
			me.selectOnFocus = previous;
		}

		return function(delay) {
			if (Ext.isNumber(delay)) {
//				Ext.defer(focus, delay, me.inputEl);
				Ext.defer(focus, delay, this);
			} else {
				focus.call(this);
			}
		};
	}()

	,doAutoSelect: function() {
		var me = this,
			picker = me.picker,
			lastSelected, itemNode;
		if (picker && me.autoSelect && me.store.getCount() > 0) {
			// Highlight the last selected item and scroll it into view
			lastSelected = picker.getSelectionModel().lastSelected;
			if (lastSelected) {
				picker.getSelectionModel().select(lastSelected, false, true);
//				debugger
			}
//			itemNode = picker.getNode(lastSelected || 0);
//			if (itemNode) {
//				picker.highlightItem(itemNode);
//				picker.listEl.scrollChildIntoView(itemNode, false);
//			}
		}
	}

	,getLoadOptions: function(queryString) {
		var filter = this.queryFilter;
		if (filter) {
			filter.disabled = false;
			filter.setValue(this.enableRegEx ? new RegExp(queryString) : queryString);
			return {
				filters: [filter]
			};
		}
	}
//	,getParams: function() {
//		var params
//	}
//
//	,onLoad: function() {
//		debugger
//		return this.callParent(arguments);
//	}

	,onTypeAhead: function() {
		var me = this,
			displayField = me.displayField,
			record = me.store.findRecord(displayField, me.getRawValue()),
			grid = me.getPicker(),
			newValue, len, selStart;

		if (record) {
			newValue = record.get(displayField);
			len = newValue.length;
			selStart = me.getRawValue().length;

			//grid.highlightItem(grid.getNode(record));
			this.highlightRecord(record);

			if (selStart !== 0 && selStart !== len) {
				me.setRawValue(newValue);
				me.selectText(selStart, newValue.length);
			}
		}
	}
});
})(Ext4);
