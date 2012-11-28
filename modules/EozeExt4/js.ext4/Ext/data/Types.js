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

	/**
	 * @property {Object}
	 */
	,DAYDATE: {
		convert: function(v, data) {
			debugger
			var date = this.DATE.convert(v, data);
			if (date) {
				date.ignoreTime = true;
			}
			return date;
		}
		,sortType: function(v) {
			return Ext4.Date.format(v, 'Ymd');
		}
		,type: 'daydate'
	}
});
