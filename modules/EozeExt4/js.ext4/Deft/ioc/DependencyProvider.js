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

(function(Ext) {
/**
 * Adds support for lazy getter injection.
 *
 * @since 2013-03-29 16:47
 */
Ext.define('Eoze.Deft.ioc.DependencyProvider', {
	override: 'Deft.ioc.DependencyProvider'

// This should work bug crashes Ext instead
//	,config: {
//		getter: true
//		,emptyValue: undefined
//	}

	/**
	 * True to make getter injection the default method.
	 *
	 * @cfg {Boolean}
	 */
	,getter: true

	/**
	 * Value for which the property will be considered uninitialized (and so, the dependency will be
	 * injected in the getter).
	 *
	 * @cfg {mixed}
	 */
	,emptyValue: undefined

	/**
	 * Workaround for the config override bug.
	 *
	 * @private
	 */
	,initConfig: function(config) {
		this.callParent(arguments);
		if (!Ext.isEmpty(config.getter)) {
			this.setGetter(config.getter);
		}
		if (config.emptyValue !== undefined) {
			this.setEmptyValue(config.emptyValue);
		}
	}

	/**
	 * Workaround for the config override bug.
	 *
	 * @private
	 */
	,getGetter: function() {
		return this.getter;
	}

	/**
	 * Workaround for the config override bug.
	 *
	 * @private
	 */
	,setGetter: function(getter) {
		if (this.applyGetter) {
			getter = this.applyGetter(getter);
		}
		this.getter = getter;
	}

	/**
	 * Workaround for the config override bug.
	 *
	 * @private
	 */
	,getEmptyValue: function() {
		return this.emptyValue;
	}

	/**
	 * Workaround for the config override bug.
	 *
	 * @private
	 */
	,setEmptyValue: function(emptyValue) {
		if (this.applyEmptyValue) {
			emptyValue = this.applyEmptyValue(emptyValue);
		}
		this.emptyValue = emptyValue;
	}
});
})(Ext4);
