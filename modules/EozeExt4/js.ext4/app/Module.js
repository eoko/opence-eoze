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
 *
 * @since 2013-07-08 14:02
 */
Ext.define('Eoze.app.Module', {
	extend: 'Eoze.app.Controller'

	,inject: ['injector', 'router']

	/**
	 * @cfg {String}
	 * @protected
	 */
	,moduleName: null

	/**
	 * @cfg {Object}
	 * @protected
	 */
	,provides: null

	/**
	 * @cfg {String/String[]/Object}
	 * @protected
	 */
	,routes: null

	,init: function() {
		this.initModuleFactory();
		this.initIoc();
		this.initRouter();
	}

	,initModuleFactory: function() {
		var moduleName = this.moduleName;
		Oce.registerModuleFactory(moduleName, this.createModule, this);
	}

	,initIoc: function() {
		var provides = this.provides;

		if (provides) {
			this.getInjector().configure(provides);
		}
	}

	,initRouter: function() {
		var routes = this.routes,
			router = this.getRouter();

		if (routes) {
			router.register(routes);
		}
//		if (routes) {
//			if (Ext.isArray(routes)) {
//				routes.forEach(function(route) {
//					router.register(route);
//				});
//			} else {
//				router.register(routes);
//			}
//		}
	}

	,createModule: function(callback) {
		callback(this);
	}

	/**
	 * @public
	 */
	,executeAction: function(name, callback, scope, args) {

		if (Ext.isObject(name)) {
			callback = name.callback || name.fn;
			scope = name.scope || this;
			args = name.args;
			name = name.action || name.name;
		}

		switch (name) {
			case 'open':
				var destination = args && args[0];

				if (!destination) {
					destination = Oce.mx.application.getMainDestination();
				}

				this.getView()
					.then({
						success: function(view) {
							destination.add(view);
							view.show();
						}
					})
					.always(function(view) {
						Ext4.callback(callback, scope, [view]);
					});

				break;

			default:
				throw new Error('Unsupported action: ' + name);
		}
	}

	,getView: function() {
		var view = this.view;

		if (!view) {
			view = this.createView();

			view.on('destroy', function() {
				delete this.view;
			}, this);

			this.view = view;
		}

		return Deft.Promise.when(view);
	}

	/**
	 * @protected
	 * @template
	 */
	,createView: function() {
		throw new Error('This method must be implemented.');
	}

});
})(window.Ext4 || Ext.getVersion && Ext);
