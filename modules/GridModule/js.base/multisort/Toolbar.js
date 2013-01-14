/**
 * Copyright (C) 2012 Eoko
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
 * @copyright Copyright (C) 2012 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

/**
 *
 * @since 2012-12-12 17:55
 */
Ext.define('Eoze.GridModule.multisort.Toolbar', {
	extend: 'Ext.Toolbar'

	,cls: 'eo-grid-multisort-toolbar'

	,hasMultiSort: false
	,ignoreSingleSort: false

	,helpText: [
		"<h1>Trier les données</h1>",
		"<p>Cliquez sur l'en-tête d'une colonne pour trier les enregistrements en fonction",
		"de la valeur de cette colonne. Cliquez à nouveau pour inverser le sens du tri.</p>",
		"<h2>Tri multiple</h2>",
		"<p>Vous pouvez glisser et déposer plusieurs en-têtes de colonne dans cette barre",
		"pour trier selon plusieurs critères successifs.</p>",
		"<p>Cliquez sur la croix pour réinitialiser le tri.</p>",
		"<h3>Astuces</h3>",
		"<p>Lorsque le tri multiple est activé, vous pouvez réorganiser les étiquettes dans",
		"cette barre pour changer l'ordre des critères sans avoir à tout recommencer.</p>",
		"<p>Lorsque vous avez déjà ajouté deux colonnes dans cette barre, vous pouvez ajouter",
		"les suivantes simplement sur leur en-tête. Pas besoin de les glisser, vous irez plus",
		"vite !</p>"
	].join(" ")

	/**
	 * @property {Ext.grid.Panel} grid
	 * @private
	 */

	/**
	 * @inheritdoc
	 */
	,initComponent: function() {

		this.plugins = [
			Ext.create('Ext.ux.ToolbarReorderer'),
			this.createToolbarDroppable()
		];

		this.items = [{
			xtype: 'tbhelp'
			// i18n
			,style: 'margin-left: 0'
			,help: this.helpText
		}, {
			iconCls: 'ico clear'
			,scope: this
			,handler: this.clearSort
		}];

		this.callParent(arguments);

		this.on({
			scope: this
			,reordered: function(button) {
				this.changeSortDirection(button, false);
			}
		})
	}

	/**
	 * @private
	 */
	,createToolbarDroppable: function() {
		var me = this;
		return this.toolbarDroppable = new Ext.ux.ToolbarDroppable({

			createItem: Ext.bind(this.onCreateItem, this)

			// afterLayout is called after an item has been dropped
			,afterLayout: Ext.bind(this.doSort, this)

			// Fixing OCE-661
			,notifyOver: Ext.Function.createSequence(Ext.ux.ToolbarDroppable.prototype.notifyOver, function() {
				// Hide the arrows that indicates that the column can be moved,
				// in case they are still visible (OCE-661)
				var cd = this.grid.view.columnDrop;
				if (cd) {
					cd.onNodeOut();
				}
			}, this)

			,calculateEntryIndex: function(e) {
				return Math.max(2, this.calculateBaseEntryIndex(e)); // label cannot be moved
			}

			/**
			 * Custom canDrop implementation which returns true if a column can be added to the toolbar.
			 *
			 * @param {Object} data Arbitrary data from the drag source
			 * @return {Boolean} True if the drop is allowed
			 */
			,canDrop: function(dragSource, event, data) {
				var sorters = me.getSorters(),
					column  = me.getColumnFromDragDrop(data);

				for (var i=0; i < sorters.length; i++) {
					if (sorters[i].field == column.dataIndex) {
						return false;
					}
				}

				return true;
			}
		});
	}

	/**
	 * @public
	 */
	,bindGrid: function(grid) {

		this.grid = grid;

		// Init drag group
		grid.on('afterrender', function(grid) {
			this.toolbarDroppable.addDDGroup(grid.getView().columnDrag.ddGroup);
		}, this);

		grid.store.on({
			scope: this
			,beforesinglesort: function(store, field, dir) {
				if (this.getSorters().length > 1) {
					this.addSortField(field, true);
					return false;
				}
			}
			,singlesort: function(store, field, dir) {
				this.clearSortToolbar();
				this.addSortField(field, dir, true);
			}
		});
	}

	/**
	 * @private
	 */
	,onCreateItem: function(data) {
		var column = this.getColumnFromDragDrop(data);
		return this.createSorterButton({
			text : column.header,
			sortData: {
				field: column.dataIndex,
				direction: "ASC"
			}
		});
	}

	/**
	 * Adds a sort item for the specified `dataIndex`. If a sort item for this `dataIndex` is
	 * already present, then its direction will be updated with the one specified.
	 *
	 * @private
	 */
	,addSortField: function(dataIndex, direction, preventReload) {

		// See if already present
		var button = this.items.find(function(button) {
			return button.sortData && button.sortData.field === dataIndex;
		});
		if (button) {
			if (button.sortData.direction !== direction) {
				this.changeSortDirection(button, true, preventReload);
			}
			return;
		}

		var cols = this.grid.colModel.getColumnsBy(function(c) { return dataIndex === c.dataIndex }),
			col = cols && cols.length && cols[0];

		if (!col) {
			return;
		}

		if (direction === true) {
			direction = 'ASC';
		}

		this.add(
			this.createSorterButton({
				text: col.header
				,sortData: {
					field: dataIndex
					,direction: direction
				}
			})
		);

		this.doLayout();

		if (!preventReload) {
			this.doSort();
		}
	}

	/**
	 * @private
	 */
	,getColumnFromDragDrop: function(data) {
		var index    = data.header.cellIndex,
			colModel = this.grid.colModel;
		return colModel.getColumnById(colModel.getColumnId(index));
	}

	/**
	 /**
	 * Convenience function for creating Toolbar Buttons that are tied to sorters.
	 *
	 * @param {Object} config Optional config object
	 * @return {Ext.SplitButton} The new Button object
	 * @private
	 */
	,createSorterButton: function(config) {
		config = config || {};

		Ext.applyIf(config, {
			listeners: {
				scope: this
				,click: function(button) {
					this.changeSortDirection(button, true);
				}
				,deleted: function(button) {
					this.remove(button);
					this.doSort();
				}
			}
			,iconCls: 'sort-' + config.sortData.direction.toLowerCase()
			,reorderable: true
		});

		return Ext.create('Eoze.GridModule.multisort.Pill', config);
	}

	/**
	 * Callback handler used when a sorter button is clicked or reordered.
	 *
	 * @param {Ext.Button} button The button that was clicked
	 * @param {Boolean} changeDirection True to change direction (default). Set to false for reorder
	 * operations as we wish to preserve ordering there
	 */
	,changeSortDirection: function (button, changeDirection, preventReload) {
		var store = this.grid.store,
			sortData = button.sortData,
			iconCls  = button.iconCls;

		if (sortData != undefined) {
			if (changeDirection !== false) {
				if (Ext.isString(changeDirection)) {
					button.sortData.direction = changeDirection;
					button.setIconClass('sort-' + changeDirection.toLowerCase());
				} else {
					button.sortData.direction = button.sortData.direction.toggle('ASC', 'DESC');
					button.setIconClass(iconCls.toggle('sort-asc', 'sort-desc'));
				}
			}
			if (!preventReload) {
				store.clearFilter(); // TODO why that?
				this.doSort();
			}
		}
	}

	,doSort: function() {
		var store = this.grid.store,
			sorters = this.getSorters();
		if (sorters.length > 0) {
			this.hasMultiSort = true;
			this.ignoreSingleSort = true;
			store.sort(sorters, 'ASC');
		} else {
			this.clearSort();
		}
	}

	/**
	 * Returns an array of sortData from the sorter buttons.
	 *
	 * @return {Object[]} Ordered sort data from each of the sorter buttons
	 */
	,getSorters: function getSorters() {
		var sorters = [];

		Ext.each(this.findByType('button'), function(button) {
			if (button.reorderable) {
				sorters.push(button.sortData);
			}
		}, this);

		return sorters;
	}

	,clearSortToolbar: function() {
		this.hasMultiSort = false;
		Ext.each(this.findByType('button'), function(button) {
			if (button.reorderable) {
				this.remove(button);
			}
		}, this);
	}

	/**
	 * @template
	 * @public
	 */
	,getDefaultSortParams: Ext.emptyFn

	,clearSort: function() {
		var store = this.grid.store;

		this.clearSortToolbar();

		store.sort.apply(store, this.getDefaultSortParams());
		store.reload();
	}

});
