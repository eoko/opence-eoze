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
 * Base class for creating application specific contexts, intended for centralizing model logic
 * of a view, and the associated events.
 *
 *
 * Proxies
 * -------
 *
 * The context fields events are accessed through {@link Eoze.context.Proxy proxies} for each
 * field. The proxy also provides facilities to change the field value.
 *
 *
 * Fields
 * ------
 *
 * The fields of the `Context` class are configured with the {@link Eoze.context.Context#fields}
 * option. This configuration can be applied statically (with the {@link Eoze.context.Context#configureFields}
 * method), else it will be applied dynamically at runtime.
 *
 * Setters and getters will be automatically generated for each of the fields configured this way.
 *
 *
 * Display fields
 * --------------
 *
 * A display field can be associated to another field. The concept of display field is semantic,
 * that is the users of the context must be aware of them.
 *
 * A display field is configured by specifying a `displayField` option in the field configuration.
 *
 * When a display field is configured, it can be accessed by component to which the context is
 * attached with the {@link Eoze.context.Context#getDisplayProxy} method.
 *
 * Example:
 *
 *     Ext.define('MyContext', {
 *         extend: 'Eoze.context.Context'
 *
 *         ,fields: [
 *             'field1',
 *             {name: 'field2', displayField: 'field1'},
 *             'field3
 *         ]
 *     });
 *
 *
 * Static fields configuration
 * ---------------------------
 *
 * Fields can be configured at the prototype level to save execution time (that is, instead of
 * being configured at runtime, each time an instance is created). This is done by calling the
 * {@link Eoze.context.Context#configureFields} method in your context class post-create function.
 *
 * Example:
 *
 *     Ext.define('My.Context', {
 *         extend: 'Eoze.context.Context'
 *         // ...
 *     }, function() {
 *         Eoze.context.Context.configureFields(this.prototype);
 *     });
 *
 * @since 2013-01-28 21:43
 */
Ext4.define('Eoze.context.Context', {
	mixins: {
		observable: 'Ext.util.Observable'
	}

	,uses: [
		'Eoze.context.Proxy'
	]

	,statics: {
		/**
		 * Applies the fields configuration of the specified object.
		 *
		 * The target can either be a class prototype (static configuration) or an instance of
		 * {@link Eoze.context.Context Context} (dynamic configuration). In the former case, the
		 * method should most probably be called from the class post-create function.
		 *
		 * @param {Object} target The object in which the field configuration will be applied.
		 */
		configureFields: function(target) {

			target.fieldNames = [];
			target.fieldMap = {};

			Ext.each(target.fields, function(field) {
				var eventName,
					config;
				if (Ext.isObject(field)) {
					config = field;
					field = field.name;
				} else {
					config = {name: field};
				}

				target.fieldNames.push(field);
				target.fieldMap[field] = config;

				var base = field.substr(0,1).toUpperCase() + field.substr(1),
					apply = 'apply' + base,
					getter = 'get' + base,
					setter = 'set' + base,
					previousSetter = target[setter],
					previousGetter = target[getter];

				function setValue(value) {
					return this.onSet(field, value);
				}

				function getValue() {
					return this.onGet(field);
				}

				if (previousSetter) {
					target[setter].$previous = setValue;
				} else {
					target[setter] = setValue;
				}

				if (previousGetter) {
					target[getter].$previous = getValue;
				} else {
					target[getter] = getValue;
				}
			});


			target.contextFieldsConfigured = true;
		}
	}

	/**
	 * @property {Object} data
	 * @private
	 */

	/**
	 * @cfg {String[]/Object[]} fields
	 *
	 * Configuration for the fields of this context.
	 *
	 * Setter & getter methods will automatically be generated for each of these fields. These setters
	 * and getters can be overridden by subclasses, either partially by calling
	 * {@link Ext.Base.callParent callParent}, or completely.
	 *
	 * Example:
	 *
	 *     Ext.define('DeepSeasContext', {
	 *         extend: 'Eoze.context.Context'
	 *
	 *         ,fields: ['firstName', 'lastName']
	 *
	 *         // Partial override
	 *         ,setFirstName: function(value) {
	 *             return this.callParent('Captain ' + value);
	 *         }
	 *
	 *         // Complete override
	 *         ,getLastName: function() {
	 *             return 'I am a pirate, no matter what you say.';
	 *         }
	 *     });
	 */

	/**
	 * @property {Object} fieldMap
	 * @private
	 */
	/**
	 * @property {String[]} fieldNames
	 * @private
	 */

	/**
	 * Creates a new Context object.
	 * @param {Object} [config]
	 */
	,constructor:function (config) {

		// Observable
		this.mixins.observable.constructor.call(this, config);

		// --- Fields configuration
		if (!this.contextFieldsConfigured) {
			Eoze.context.Context.configureFields(this);
		}

		// --- Init

		// Data
		var data = {};
		Ext.each(this.fieldNames, function(field) {
			data[field] = null;
		});
		this.data = data;

		// Proxies
		this.proxies = {};

		// Fields
		Ext.each(this.fieldNames, function(field) {
			if (Ext.isDefined(config[field])) {
				this.set(field, config && config[field] || null);
			}
		}, this);
	}

	/**
	 * Set the value of the field.
	 *
	 * @param {String} field
	 * @param {Mixed} value
	 * @return {Boolean} True if the field value has changed, else false.
	 * @private
	 */
	,set: Ext4.Function.flexSetter(function(field, value) {
		var method = 'set' + field.substr(0, 1).toUpperCase() + field.substr(1);
		return this[method].call(this, value);
	})

	/**
	 * Get the value of the field.
	 *
	 * @param {String} field
	 * @return {Mixed}
	 */
	,get: function(field) {
		var method = 'get' + field.substr(0, 1).toUpperCase() + field.substr(1);
		return this[method].call(this);
	}

	/**
	 * Set the value of the field.
	 *
	 * This method is the core implementation to actually change the value of any field.
	 * It is used internally by the auto generated setters. **It is not intended for being
	 * modified outside of this class.**
	 *
	 * @param {String} field
	 * @param {Mixed} value
	 * @return {Boolean} True if the field value has changed, else false.
	 * @private
	 */
	,onSet: function(field, value) {
		var data = this.data,
			previousValue = data[value];

		value = Ext4.value(value, null);

		if (value !== previousValue) {
			data[field] = value;
			this.fireProxy(field, 'change', [value, previousValue]);
			return true;
		}

		return false;
	}

	/**
	 * Get the value of the field (internal).
	 *
	 * This method is the core implementation to actually retrieve the value of any field.
	 * It is used internally by the auto generated getters. **It is not intended for being
	 * modified outside of this class.**
	 *
	 * @param {String} field
	 * @return {Mixed}
	 * @private
	 */
	,onGet: function(field) {
		return this.data[field];
	}

	/**
	 * Fire the specified proxy event for the given field.
	 *
	 * @param {String} field
	 * @param {String} event
	 * @param {Array} [params]
	 * @protected
	 */
	,fireProxy: function(field, event, params) {
		var proxy = this.proxies[field];

		if (!proxy) {
			return;
		}

		params = params || [];
		params.unshift(this);

		params.unshift(event);

		return proxy.fireEvent.apply(proxy, params);
	}

	/**
	 * Get the proxy event for the specified field.
	 *
	 * @param {String} field Name of the field.
	 * @return {Eoze.context.Proxy}
	 */
	,getProxy: function(field) {
		var proxies = this.proxies;
		if (proxies[field]) {
			return proxies[field];
		} else {
			return proxies[field] = Ext4.create('Eoze.context.Proxy', {
				context: this
				,field: field
			});
		}
	}

	/**
	 * Get the proxy for the display field associated to the specified field.
	 *
	 * @param {String} field The name of the field.
	 * @return {Eoze.context.Proxy}
	 */
	,getDisplayProxy: function(field) {
		var f = this.fieldMap[field],
			displayFieldName = f.displayField;
		if (!displayFieldName) {
			// We must die here. We cannot use the field itself, because we don't want it
			// to be set a display value.
			throw new Error('The field "' + field + '" has no display field configured!');
		} else {
			return this.getProxy(displayFieldName);
		}
	}

	/**
	 * Attach this context to the specified component. This is done by calling the component
	 * `setContext` method (by default).
	 *
	 * The called method can be changed by passing an array of the form `[component, methodName]`,
	 * like this:
	 *
	 *     context.attachToComponent('myField', [myComponent, 'setCustomContext']);
	 *
	 * @param {String} field
	 * @param {Ext.Component/Array} component
	 */
	,attachToComponent: Ext4.Function.flexSetter(function(field, component) {
		var method = 'setContext';
		if (Ext.isArray(component)) {
			method = component[1];
			component = component[0];
		}
		component[method](this.getProxy(field));
	})
});
