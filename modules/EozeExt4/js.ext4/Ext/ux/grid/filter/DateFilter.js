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
 * This override:
 *
 * - Adds the {@link #dateSubmitFormat} config option, and adds the date format
 *   in the filter arguments.
 *
 * - Replaces exclusive comparisons with inclusive ones.
 *
 * @since 2013-05-17 16:11
 */
Ext4.define('Eoze.Ext.ux.grid.filter.DateFilter', {
	override: 'Ext.ux.grid.filter.DateFilter'

	/**
	 * Format of the date submitted to the server.
	 *
	 * @cfg {String}
	 */
	,dateSubmitFormat: 'Y-m-d'

	/**
	 * Overridden to use {@link #dateSubmitFormat}, and include date format in the args.
	 * @protected
	 */
	,compareMap : {
		before: 'lte',
		after:  'gte',
		on:     'eq'
	}

	/**
	 * Overridden to use {@link #dateSubmitFormat}, and include date format in the args.
	 * @protected
	 */
	,getSerialArgs: function() {
		var args = [];
		for (var key in this.fields) {
			if(this.fields[key].checked){
				args.push({
					type: 'date'
					,comparison: this.compareMap[key]
					,value: Ext.Date.format(this.getFieldValue(key), this.dateSubmitFormat)
					,dateFormat: this.dateSubmitFormat
				});
			}
		}
		return args;
	}

	/**
	 * Overridden to make inclusive comparisons.
	 */
	,validateRecord: function (record) {
		var key,
			pickerValue,
			val = record.get(this.dataIndex),
			clearTime = Ext.Date.clearTime;

		if(!Ext.isDate(val)){
			return false;
		}
		val = clearTime(val, true).getTime();

		for (key in this.fields) {
			if (this.fields[key].checked) {
				pickerValue = clearTime(this.getFieldValue(key), true).getTime();
				// rx: Replaced <= by <
				if (key == 'before' && pickerValue < val) {
					return false;
				}
				// rx: Replaced >= by >
				if (key == 'after' && pickerValue > val) {
					return false;
				}
				if (key == 'on' && pickerValue != val) {
					return false;
				}
			}
		}
		return true;
	}

});
