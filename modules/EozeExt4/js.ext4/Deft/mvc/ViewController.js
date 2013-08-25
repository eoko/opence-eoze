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

			var existingMethod = this[getterName];

			if (existingMethod) {

				// /!\ /!\ /!\
				//
				// This will most probably bind to the method owned by *the prototype*:
				//
				// So:
				//
				// either (a) the getterMethod had better not be bound to a given scope,
				// or (b) the function to which is attached the $previous should be unique
				//        to the object scope.
				//
				// Here I choose (b) because we're running from the constructor, and not the
				// class definition time.
				//
				// Unfortunately, this strategy makes the previous method's result unreachable
				// (probably no big deal).

				// Clone existing method so that we can set it's $previous without affecting the
				// prototype.
				var instanceMethod;
				eval('instanceMethod = ' + existingMethod.toString());

				//noinspection JSUnusedAssignment
				this[getterName] = instanceMethod;

				//noinspection JSUnusedAssignment
				instanceMethod.$previous = getterMethod;

				// ... indeed, all this was a bit ugly :/
			} else {
				this[getterName] = getterMethod;
			}
		}
		this.registeredComponentReferences[id] = true;
	}

});
