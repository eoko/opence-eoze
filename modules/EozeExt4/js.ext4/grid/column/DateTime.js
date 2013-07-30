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
 * A column displaying both date and time information, in the {@link Eoze.Ext.Date.defaultDateTimeFormat}.
 *
 * @since 2013-06-28 18:24
 */
Ext.define('Eoze.grid.column.DateTime', {
	extend: 'Ext.grid.column.Date'

	,alias: 'widget.datetimecolumn'

	,format: 'defaultDateTime'
});
})(window.Ext4 || Ext.getVersion && Ext);
