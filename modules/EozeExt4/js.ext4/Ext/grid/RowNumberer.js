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

(function(Ext) {
/**
 * - Fixes row index in paged grids.
 *
 * @since 2013-06-25 03:04
 */
Ext.define('Eoze.Ext.grid.RowNumberer', {
	override: 'Ext.grid.RowNumberer'

	,renderer: function(value, metaData, record, rowIdx, colIdx, store) {
		var rowspan = this.rowspan;
		if (rowspan) {
			metaData.tdAttr = 'rowspan="' + rowspan + '"';
		}

		var store = this.getStore();
		if (store.pageSize) {
			rowIdx += (store.currentPage - 1) * store.pageSize;
		}

		metaData.tdCls = Ext.baseCSSPrefix + 'grid-cell-special';
		return rowIdx + 1;
	}
});
})(Ext4);
