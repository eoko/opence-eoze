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
 * This override adds support for lazy **getter** injection.
 *
 * Usage:
 *
 *     Deft.Injector.configure({
 *       // Standard (constructor) injection
 *       foo: {
 *         className: 'Example.Foo'
 *       }
 *       // Lazy injection on the first call of the getter
 *       ,lazyFoo: {
 *         className: 'Example.Foo'
 *         ,getter: true               // getter injection!
 *       }
 *       ,lazyFooWithDefault: {
 *         className: 'Example.Foo'
 *         ,getter: true
 *         ,emptyValue: 0              // value to be considered empty
 *       }
 *     });
 *
 * Lazy getter injection will work whether the injected class already has a getter for the
 * property or not.
 *
 * Empty value
 * -----------
 *
 * The injection will be triggered the first time the getter is called if, and only if, the
 * target property is strictly equal to the configured `emptyValue` (which defaults to
 * `undefined`).
 *
 * @since 2013-03-29 16:26
 */
Ext.define('Eoze.Deft.ioc.Injector', {
	override: 'Deft.ioc.Injector'

	,requires: [
		'Eoze.Deft.ioc.DependencyProvider'
	]

	,inject: function(identifiers, targetInstance, targetInstanceIsInitialized) {
		var otherIdentifiers = {};

		Ext.Object.each(identifiers, function(key, value) {
			var targetProperty = Ext.isArray(identifiers) ? value : key,
				identifier = value,
				provider = this.getProvider(identifier, false);

			// If provider cannot be resolved right now, it will be attempted to resolve it when
			// the getter is called (that's why we also need the identifier here).
			if (!provider || provider.getGetter()) {
				this.createInjectableGetter(provider, targetInstance, targetProperty, identifier);
			} else {
				otherIdentifiers[targetProperty] = identifier;
			}
		}, this);

		return this.callParent([otherIdentifiers, targetInstance, targetInstanceIsInitialized]);
	}

	/**
	 * @param {String} identifier
	 * @param {Boolean} [require=true]
	 * @return {Deft.ioc.DependencyProvider}
	 * @protected
	 */
	,getProvider: function(identifier, require) {
		var provider = this.providers[identifier];
		if (provider != null) {
			return provider;
		} else if (require !== false) {
			Ext.Error.raise({
				msg: "Error while resolving value to inject: no dependency provider found for '" + identifier + "'."
			});
		}
	}

	/**
	 * Creates a getter method for the given target and provider. If the provider is not provided,
	 * it will be attempted to resolve it with the given identifier, when the getter is called.
	 *
	 * @param {Deft.ioc.DependencyProvider/undefined} provider
	 * @param {Object} targetInstance
	 * @param {String} targetProperty
	 * @param {String} identifier
	 * @private
	 */
	,createInjectableGetter: function(provider, targetInstance, targetProperty, identifier) {
		var me = this,
			capitalizedName = Ext.String.capitalize(targetProperty),
			getterName = 'get' + capitalizedName,
			setterName = 'set' + capitalizedName,
			originalGetter = targetInstance[getterName],
			setter = targetInstance[setterName];

		if (!provider) {
			Deft.Logger.info('Cannot resolve "' + identifier + '" right away. Hoping for late resolving.');
		}

		if (originalGetter) {
			targetInstance[getterName] = function() {
				// Late provider resolving
				if (!provider) {
					provider = me.getProvider(identifier);
				}

				var value = originalGetter.apply(this, arguments),
					resolvedValue;

				if (value === provider.getEmptyValue()) {
					resolvedValue = provider.resolve(this)

					// set value
					if (setter) {
						setter.call(this, resolvedValue);
					} else {
						this[targetProperty] = resolvedValue;
					}

					// replace getter with original one
					this[getterName] = originalGetter;
				}

				// return value with original getter
				return originalGetter.apply(this, arguments);
			};
		} else {
			targetInstance[getterName] = function() {
				// Late provider resolving
				if (!provider) {
					provider = me.getProvider(identifier);
				}

				var value = this[targetProperty],
					resolvedValue;

				if (value === provider.getEmptyValue()) {
					resolvedValue = provider.resolve(this);

					// set value
					this[targetProperty] = resolvedValue;

					// return
					return resolvedValue;
				} else {
					return value;
				}
			};
		}
	}
});
})(Ext4);
