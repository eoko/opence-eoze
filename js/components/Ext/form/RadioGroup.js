/**
 * Copyright (C) 2014 Eoko
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
 * @copyright Copyright (C) 2014 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

/**
 * Fixes {@link #getValue} and `originalValue` when used before the component has
 * been rendered.
 *
 * @since 2014-04-08 17:12
 */
Ext.override(Ext.form.RadioGroup, function() {
	var spp = Ext.form.RadioGroup.prototype,
		uberGetValue = spp.getValue,
		uberIsDirty = spp.isDirty;
	return {
		setValue: function() {
			if (this.rendered) {
				this.onSetValue.apply(this, arguments);
			} else {
				this.buffered = true;
				this.value = Array.prototype.slice.call(arguments, 0);
			}
			return this;
		}
		,getValue: function() {
			if (this.rendered) {
				return uberGetValue.apply(this, arguments);
			} else {
				return this.value;
			}
		}
		,isDirty: function() {
			return cast(this.getValue()) !== cast(this.originalValue);
			function cast(value) {
				if (value) {
					if (Ext.isArray(value)) {
						value = value[0];
					}
					if (Ext.isDefined(value.inputValue)) {
						value = value.inputValue;
					}
				}
				return value;
			}
		}
	};
}());
