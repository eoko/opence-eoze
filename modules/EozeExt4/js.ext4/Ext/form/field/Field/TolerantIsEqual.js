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
 * Overrides {@link Ext.form.field.Field#isEqual} to compare values on their **content**.
 * In particular all nullish values (i.e. null, undefined) are considered the same.
 *
 * @since 2013-09-06 13:18
 */
Ext.define('Eoze.Ext.form.field.Field.TolerantIsEqual', {
	override: 'Ext.form.field.Field'

	/**
	 * Consider nullish values (i.e. undefined and null) as equals, and compare actual
	 * composed values (i.e. arrays and objects) on their *content*. This method uses
	 * {@link Ext.Object#equals}, so objects won't be compared past the first level
	 * of nesting.
	 */
	,isEqual: function() {
		var equals = Ext.Object.equals;
		return function(a, b) {
			return (a === undefined || a === null) && (b === undefined || b === null)
				|| equals(a, b);
		};
	}()

});
})(window.Ext4 || Ext.getVersion && Ext);
