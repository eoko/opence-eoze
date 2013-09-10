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
 * Fixes floatParent bug. In the original code, the date picker is assigned the picker field
 * as its ownerCt, but it shouldn't (no containing relationship). This incorrect behaviour,
 * coupled with another bug that prevents fieldsets from updating their collapsed state in
 * hierarchicalState, may causes the Date picker to be incorrectly considered hidden.
 *
 * @since 2013-09-11 16:28
 */
Ext.define('Eoze.fix.Ext.form.field.Date', {
	override: 'Ext.form.field.Date'

	,createPicker: function() {
		var me = this,
			format = Ext.String.format;

		return new Ext.picker.Date({
			pickerField: me,
			// From {@link Ext.util.Floating#registerWithOwnerCt}:
			// Developers must only use ownerCt if there is really a containing relationship.
			//ownerCt: me.ownerCt,
			renderTo: document.body,
			floating: true,
			hidden: true,
			focusOnShow: true,
			minDate: me.minValue,
			maxDate: me.maxValue,
			disabledDatesRE: me.disabledDatesRE,
			disabledDatesText: me.disabledDatesText,
			disabledDays: me.disabledDays,
			disabledDaysText: me.disabledDaysText,
			format: me.format,
			showToday: me.showToday,
			startDay: me.startDay,
			minText: format(me.minText, me.formatDate(me.minValue)),
			maxText: format(me.maxText, me.formatDate(me.maxValue)),
			listeners: {
				scope: me,
				select: me.onSelect
			},
			keyNavConfig: {
				esc: function() {
					me.collapse();
				}
			}
		});
	}
});
})(window.Ext4 || Ext.getVersion && Ext);
