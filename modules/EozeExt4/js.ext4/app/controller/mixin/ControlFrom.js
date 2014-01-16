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
 * Adds {@link #controlFrom} and {@link #getRoot} methods to the controller.
 *
 * The root selector is configured in {@link #rootSelector}.
 *
 * @since 2013-06-27 21:58
 */
Ext.define('Eoze.app.controller.mixin.ControlFrom', {

	/**
	 * Selector for the component that *must* be the root for {@link #controlFrom}, and
	 * that is looked up by {@link #getRoot}.
	 *
	 * @cfg {String}
	 */
	rootSelector: null

	,defaultRootSelector: '[floating], [rootForm]'

	/**
	 * Control by making the selectors relative to the specified root selector,
	 * or to the {@link #rootSelector configured one.
	 *
	 * @param {String} rootSelector
	 * @param {Object} control
	 */
	,controlFrom: function(rootSelector, control) {

		if (arguments.length === 1) {
			control = rootSelector;
			rootSelector = this.rootSelector;
		}

		if (rootSelector === null) {
			return this.control(control);
		}

		var fullyQualifiedControl = {};

		if (rootSelector.substr(-1) !== ' ') {
			rootSelector += ' ';
		}

		Ext.iterate(control, function(name, spec) {
			fullyQualifiedControl[rootSelector + name] = spec;
		});

		return this.control(fullyQualifiedControl);
	}

	/**
	 * Get the root component for the given field, as specified by the configured
	 * {@link #rootSelector}
	 *
	 * @param {Ext.Component} field
	 *
	 * @return {Ext.Component/undefined}
	 */
	,getRoot: function(field) {
		var rootSelector = this.rootSelector;

		if (rootSelector !== null) {
			var root = field.up(this.rootSelector)
			if (root) {
				return root;
			}
		}

		// default
		return field.up(this.defaultRootSelector);
	}

});
})(Ext4);
