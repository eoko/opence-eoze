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
 * Overrides {@link Ext.menu.CheckItem} to add a third possible state (undetermined).
 *
 * @class Ext.menu.CheckItem
 * @since 2012-11-27 23:15
 */
Ext.apply(Ext.menu.CheckItem.prototype, {

	/**
	 * @cfg {Boolean}
	 * Undetermined state of the check item.
	 */
	undetermined: false

	/**
	 * Sets the undetermined state of the check item.
	 *
	 * @param {Boolean} state
	 */
	,setUndetermined: function(state) {
		if (this.isUndetermined() !== state) {
			this.undetermined = state;
			if (state) {
				this.addClass('undetermined');
			} else {
				this.removeClass('undetermined');
			}
			this.fireEvent('undeterminedchange', this, state);
		}
	}

	/**
	 * Gets undetermined state of this menu item.
	 *
	 * @return {Boolean}
	 */
	,isUndetermined: function() {
		return !!this.undetermined;
	}
});

