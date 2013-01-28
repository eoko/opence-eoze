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
 * A proxy to a field of a {@link Eoze.context.Context}.
 *
 * The proxy will fire a `change` event when the associated field is modified, and a `beforeload`
 * event when a asynchronous load operation that implies the bound field is started (this has to
 * be implemented in each context subclass).
 *
 * The proxy also provides method to read or write the field value without having to know its
 * name (see {@link Eoze.context.Proxy#set} and {@link Eoze.context.Proxy#get}).
 *
 * @since 2013-01-28 21:43
 */
Ext4.define('Eoze.context.Proxy', {
	mixins: {
		observable: 'Ext.util.Observable'
	}

	,constructor: function(config) {
		// Observable
		this.mixins.observable.constructor.call(this, config);

		// Events
		this.addEvents(
			/**
			 * @event
			 * @param {Eoze.context.Context} context
			 * @param {Mixed} newValue
			 * @param {Mixed} previousValue
			 */
			'change'
			,'beforeload'
		);

		// Integrity
		if (!this.context) {
			throw new Error('context is required');
		}
		if (!this.field) {
			throw new Error('field is required');
		}
	}

	/**
	 * Get the parent context of this proxy.
	 *
	 * @return {Eoze.context.Context}
	 */
	,getContext: function() {
		return this.context;
	}

	/**
	 * Set the value of the field bound to this proxy.
	 *
	 * @param {Mixed} value
	 * @return {Boolean} True if the value has changed, else false.
	 */
	,setValue: function(value) {
		return this.context.set(this.field, value);
	}

	/**
	 * Get the value of the field bound to this proxy.
	 *
	 * @return {Mixed}
	 */
	,getValue: function() {
		return this.context.get(this.field);
	}

	/**
	 * Proxy method for {@link Eoze.context.Context#set}.
	 *
	 * @param {String} field
	 * @param {Mixed} value
	 * @return {Boolean}
	 */
	,set: function(field, value) {
		var context = this.getContext();
		context.set.apply(context, arguments);
	}

	/**
	 * Proxy method for {@link Eoze.context.Context#get}.
	 *
	 * @param {String} field
	 * @return {Mixed}
	 */
	,get: function(field) {
		return this.getContext().get(field);
	}

	/**
	 * Proxy method for {@link Eoze.context.Context#getProxy}.
	 *
	 * @param {String} field
	 * @return {Eoze.context.Proxy}
	 */
	,getProxy: function(field) {
		return this.getContext().getProxy(field);
	}

	/**
	 * Get the proxy for the display field associated to this field.
	 *
	 * @return {Eoze.context.Proxy}
	 */
	,getDisplayProxy: function() {
		return this.getContext().getDisplayProxy(this.field);
	}
});
