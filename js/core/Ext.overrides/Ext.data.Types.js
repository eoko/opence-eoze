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
 * @since 2012-12-18 12:48 (Code previously living in overrides.js)
 */

// Fix Int data type to actually return NULL if the value cannot be parsed
Ext.data.Types.INT.convert = function(v) {
	var r = v !== undefined && v !== null && v !== '' ?
		parseInt(String(v).replace(Ext.data.Types.stripRe, ''), 10)
		: (this.useNull ? null : 0);
	return Ext.isNumber(r) ? r : (this.useNull ? null : 0);
};

(function() {
	var o = Ext.data.Types.DATE,
		uber = o.convert,
		iso = {
			dateFormat: 'Y-m-d\\TH:i:s'
		},
		sql = {
			dateFormat: 'Y-m-d H:i:s'
		};

	o.convert = function(v) {
		if (!v) return null;
		if (Ext.isDate(v)) return v;
		return uber.call(this, v) || uber.call(iso, v) || uber.call(sql, v);
	}
})();

Oce.deps.reg('eo.Ext.data.Types');
