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
 * Eoze override of Deft.mvc.ViewController that allows to use `callParent` in overridden generated getters.
 *
 * For example, this makes this possible:
 *
 *     Ext.define('MyController', {
 *         extend: 'Deft.mvc.ViewController'
 *
 *         ,control: {
 *             myComponent: true
 *         }
 *
 *         ,getMyComponent: function() {
 *             // Here's the magic!!
 *             var component = this.callParent(arguments);
 *
 *             // do something...
 *
 *             return component;
 *         }
 *     });
 *
 * @since 2013-04-12 17:16
 */
Ext4.define('Eoze.Deft.mvc.ViewController', {
	override: 'Deft.mvc.ViewController'

	/**
	 * This method is overridden to allow calling `callParent` from overridden generated getters.
	 */
	,addComponentReference: function(id, selector, live) {
		var getterName,
			matches,
			getterMethod;
		if (live == null) {
			live = false;
		}
		Deft.Logger.log("Adding '" + id + "' component reference for selector: '" + selector + "'.");
		if (this.registeredComponentReferences[id] != null) {
			Ext4.Error.raise({
				msg: "Error adding component reference: an existing component reference was already registered as '" + id + "'."
			});
		}
		if (id !== 'view') {
			getterName = 'get' + Ext4.String.capitalize(id);

			if (live) {
				getterMethod = Ext4.Function.pass(this.getViewComponent, [selector], this);
			} else {
				matches = this.getViewComponent(selector);
				if (matches == null) {
					Ext4.Error.raise({
						msg: "Error locating component: no component(s) found matching '" + selector + "'."
					});
				}
				getterMethod = function() {
					return matches;
				};
			}
			getterMethod.generated = true;

			if (this[getterName] == null) {
				this[getterName] = getterMethod;
			} else if (!this[getterName].$previous) {
				this[getterName].$previous = getterMethod;
			}
		}
		this.registeredComponentReferences[id] = true;
	}

});
