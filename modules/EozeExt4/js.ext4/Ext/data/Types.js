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

Ext4.define('Eoze.Ext.data.Types', {
	override: 'Ext.data.Types'
}, function() {

	var Ext = Ext4,
		DATE = Ext.data.Types.DATE,
		extDate = Ext.Date,
		clearTime = extDate.clearTime,
		clone = Ext.clone,
		dateConvert;

	Ext.apply(this, {

		DATE: {
			convert: dateConvert = function(v, data) {
				if (Ext.isEmpty(v)) {
					return null;
				} else {
					if (!(v instanceof Date)) {
						v = new Date(v);
					}
					return v;
				}
			}
		}

		/**
		 * @property {Object}
		 */
		,DATETIME: this.DATE

		/**
		 * @property {Object}
		 */
		,DAYDATE: {
			convert: function(v, data) {
				var date = dateConvert(v, data);
				if (date) {
					date.ignoreTime = true;
					clearTime(date);
				}
				return date;
			}
			,sortType: function(v) {
				return Ext4.Date.format(v, 'Ymd');
			}
			,type: 'daydate'
		}
	});

	this.DATETIME = this.DATE;
});
