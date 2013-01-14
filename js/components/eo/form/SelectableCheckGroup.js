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
 * Utility class managing the relation between a group of check items and a matching "check all"
 * item.
 *
 * @since 2012-11-27 21:59
 */
Ext.define('eo.form.SelectableCheckGroup', {

	/**
	 * Creates new {@link eo.form.SelectableCheckGroup}.
	 *
	 * @param {Ext.menu.CheckItem} parent
	 */
	constructor: function(parent) {
		this.children = [];
		this.parent = parent;

		parent.on('checkchange', this.onParentCheckChange, this);

		this.init = this.onChildCheckChange;
	}

	/**
	 * Adds a child item to the group.
	 *
	 * @param {Ext.menu.CheckItem} item
	 */
	,add: function(item) {
		this.children.push(item);
		item.on({
			scope: this
			,checkchange: this.onChildCheckChange
			,undeterminedchange: this.onChildCheckChange
		});
	}

	/**
	 * Handler for parent check change event.
	 *
	 * @param {Ext.menu.CheckItem} item
	 * @param {Boolean} check
	 *
	 * @private
	 */
	,onParentCheckChange: function(item, check) {
		if (this.bulk) {
			return;
		}

		item.setUndetermined(false);

		this.bulk = true;
		Ext.each(this.children, function(item) {
			item.setChecked(check);
		});
		this.bulk = false;
	}

	/**
	 * Handler for children check change event.
	 *
	 * @param {Ext.menu.CheckItem} item
	 * @param {Boolean} check
	 *
	 * @private
	 */
	,onChildCheckChange: function() {
		if (this.bulk) {
			return;
		}

		var parent = this.parent,
			checked = true,
			hasSelection = false;
		Ext.each(this.children, function(item) {
			if (item.checked || item.undetermined) {
				hasSelection = true;
			} else {
				checked = false;
			}
		});
		// update parent check
//		parent.setChecked(checked, true);
		this.bulk = true;
		parent.setChecked(checked);
		this.bulk = false;

		// update undetermined state
		parent.setUndetermined(!checked && hasSelection);
	}

});
