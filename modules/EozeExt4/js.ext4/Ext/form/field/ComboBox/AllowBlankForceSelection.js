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
 * Change behaviour of {@link #forceSelection} to allow setting an empty value (i.e.
 * clearing the field), in the case {@link #allowBlank} permits it.
 *
 * @since 2013-09-06 10:26
 */
Ext.define('Eoze.Ext.form.field.ComboBox.AllowBlankForceSelection', {
	override: 'Ext.form.field.ComboBox'

	,assertValue: function() {
		var me = this,
			value = me.getRawValue(),
			rec, currentValue;

		if (me.forceSelection) {
			if (me.multiSelect) {
				// For multiselect, check that the current displayed value matches the current
				// selection, if it does not then revert to the most recent selection.
				if (value !== me.getDisplayValue()) {
					me.setValue(me.lastSelection);
				}
			} else {
				// For single-select, match the displayed value to a record and select it,
				// if it does not match a record then revert to the most recent selection.
				rec = me.findRecordByDisplay(value);
				if (rec) {
					currentValue = me.value;
					// Prevent an issue where we have duplicate display values with
					// different underlying values.
					if (!me.findRecordByValue(currentValue)) {
						me.select(rec, true);
					}
				// <rx>
				} else if (Ext.isEmpty(value) && this.allowBlank) {
					me.setValue(value);
				// </rx>
				} else {
					me.setValue(me.lastSelection);
				}
			}
		}
		me.collapse();
	}
});
})(window.Ext4 || Ext.getVersion && Ext);
