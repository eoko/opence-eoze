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
 * This overrides uses records `get()` method instead of reading directly from their data object. This enables
 * reading expandable field names.
 *
 * @since 2013-05-06 14:58
 */
Ext.define('Eoze.Ext.view.Table.UsingGetterInsteadOfDataObject', function() {

	var Ext = Ext4;

	/**
	 * Ensures that the version is the same as the one against which has been applied the patch.
	 */
	(function ensureExtVersionHasNotChanged() {
		var version = Ext.getVersion(),
			testedWorkingVersions = [
				'4.2.0.663'
			],
			untestedVersion = true;

		Ext.each(testedWorkingVersions, function(testVersion) {
			if (version.equals(testVersion)) {
				untestedVersion = false;
			}
			return untestedVersion;
		});

		if (untestedVersion) {
			Ext4.Logger.warn(
				'Please ensure that the code of this class has not changed with this version.'
					+ ' See documentation of this function above.'
			);
			debugger
		}
	})();

	return {
		override: 'Ext.view.Table'

		/**
		 * @inheritdoc Ext.view.Table#renderCell
		 *
		 * Emits the HTML representing a single grid cell into the passed output stream (which is an array of strings).
		 *
		 * @param {Ext.grid.column.Column} column The column definition for which to render a cell.
		 * @param {Number} recordIndex The row index (zero based within the {@link #store}) for which to render the cell.
		 * @param {Number} columnIndex The column index (zero based) for which to render the cell.
		 * @param {String[]} out The output stream into which the HTML strings are appended.
		 */
		,renderCell: function(column, record, recordIndex, columnIndex, out) {
			var me = this,
				selModel = me.selModel,
				cellValues = me.cellValues,
				classes = cellValues.classes,
				// <rx> Changed:
				//fieldValue = record.data[column.dataIndex],
				// To:
				fieldValue = record.get(column.dataIndex),
				// </rx>
				cellTpl = me.cellTpl,
				value, clsInsertPoint;

			cellValues.record = record;
			cellValues.column = column;
			cellValues.recordIndex = recordIndex;
			cellValues.columnIndex = columnIndex;
			cellValues.cellIndex = columnIndex;
			cellValues.align = column.align;
			cellValues.tdCls = column.tdCls;
			cellValues.style = cellValues.tdAttr = "";
			cellValues.unselectableAttr = me.enableTextSelection ? '' : 'unselectable="on"';

			if (column.renderer && column.renderer.call) {
				value = column.renderer.call(column.scope || me.ownerCt, fieldValue, cellValues, record, recordIndex, columnIndex, me.dataSource, me);
				if (cellValues.css) {
					// This warning attribute is used by the compat layer
					// TODO: remove when compat layer becomes deprecated
					record.cssWarning = true;
					cellValues.tdCls += ' ' + cellValues.css;
					delete cellValues.css;
				}
			} else {
				value = fieldValue;
			}
			cellValues.value = (value == null || value === '') ? '&#160;' : value;

			// Calculate classes to add to cell
			classes[1] = Ext.baseCSSPrefix + 'grid-cell-' + column.getItemId();

			// On IE8, array[len] = 'foo' is twice as fast as array.push('foo')
			// So keep an insertion point and use assignment to help IE!
			clsInsertPoint = 2;

			if (column.tdCls) {
				classes[clsInsertPoint++] = column.tdCls;
			}
			if (me.markDirty && record.isModified(column.dataIndex)) {
				classes[clsInsertPoint++] = me.dirtyCls;
			}
			if (column.isFirstVisible) {
				classes[clsInsertPoint++] = me.firstCls;
			}
			if (column.isLastVisible) {
				classes[clsInsertPoint++] = me.lastCls;
			}
			if (!me.enableTextSelection) {
				classes[clsInsertPoint++] = Ext.baseCSSPrefix + 'unselectable';
			}

			classes[clsInsertPoint++] = cellValues.tdCls;
			if (selModel && selModel.isCellSelected && selModel.isCellSelected(me, recordIndex, columnIndex)) {
				classes[clsInsertPoint++] = (me.selectedCellCls);
			}

			// Chop back array to only what we've set
			classes.length = clsInsertPoint;

			cellValues.tdCls = classes.join(' ');

			cellTpl.applyOut(cellValues, out);

			// Dereference objects since cellValues is a persistent var in the XTemplate's scope chain
			cellValues.column = null;
		}
	};
});
