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
 * AjaxRouter provides routing for the client-side.
 *
 * @since 2012-12-17 14:42
 */
Ext.define('eo.AjaxRouter', {

	singleton: true

	,configController: 'AjaxRouter.config'

	/**
	 * @property {Object}
	 */
	,priority: {
		FIRST: 1000
		,MIDDLE: 2000
		,LAST: 3000

		,BEFORE: -500
		,AFTER: 500

		,BEFORE_FIRST: 1000-500
		,BEFORE_MIDDLE: 2000-500
		,BEFORE_LAST: 3000-500

		,AFTER_FIRST: 1000+500
		,AFTER_MIDDLE: 2000+500
		,AFTER_LAST: 3000+500
	}

	/**
	 * True when the routes are sorted by priority, else false.
	 *
	 * @var {Boolean}
	 * @private
	 */
	,sorted: false

	/**
	 * @private
	 */
	,constructor: function() {
		this.routes = Ext.create('Ext.util.MixedCollection');
		this.lookup = {};
	}

	/**
	 * @param {String} [path]
	 * @return {eo.AjaxRouter.Route}
	 */
	,route: function(path) {
		if (!path) {
			path = this.getCurrentPath();
		}
		var match = null;
		this.getSortedRoutes().each(function(routes) {
			Ext.each(routes, function(route) {
				if (route.run(path)) {
					match = route;
					return false;
				}
			});
			return !match;
		});
		return match;
	}

	/**
	 * @param {String} path
	 * @return {eo.AjaxRouter.Route}
	 */
	,getMatch: function(path) {
		var matchedRoute;
		this.getSortedRoutes().each(function(routes) {
			Ext.each(routes, function(route) {
				if (route.test(path)) {
					matchedRoute = route;
					return false;
				}
			});
			return !matchedRoute;
		});
		return matchedRoute;
	}

	/**
	 * @private
	 */
	,getCurrentPath: function() {
		var match = /#!\/(.*)$/.exec(window.location.hash);
		return match && match[1] || null;
	}

	/**
	 * @private
	 */
	,getSortedRoutes: function() {
		if (!this.sorted) {
			this.routes.keySort();
			this.sorted = true;
		}
		return this.routes;
	}

	/**
	 *
	 * ### Method signatures
	 *
	 *     // Register one route
	 *     eo.AjaxRouter.register(route)
	 *
	 *     // Register one route with priority
	 *     eo.AjaxRouter.register(priority, route)
	 *
	 *     // Register multiple routes
	 *     eo.AjaxRouter.register([...])
	 *
	 * ### Priority
	 *
	 * Priority can be an integer, or a string. String constant are strongly recommended. They
	 * can be any of:
	 *
	 * - FIRST
	 * - MIDDLE
	 * - LAST
	 * - BEFORE_FIRST
	 * - BEFORE_MIDDLE
	 * - BEFORE_LAST
	 * - AFTER_FIRST
	 * - AFTER_MIDDLE
	 * - AFTER_LAST
	 *
	 * @param {Integer/String} [priority = eo.AjaxRouter.priority.MED]
	 * @param {Object/eo.AjaxRouter.Route} route
	 * @return {Object}
	 */
	,register: function(priority, route) {
		if (arguments.length === 1) {
			route = priority;
			priority = undefined;//this.priority.MIDDLE;
		}
		// Array form
		if (Ext.isArray(route)) {
			var routes = [];
			Ext.each(route, function(route) {
				route = this.register(priority, route);
				routes.push(route);
			}, this);
			return routes;
		}
		// Implementation
		else {
			// Priority
			if (!Ext.isDefined(priority)) {
				if (route.priority) {
					priority = route.priority;
				} else {
					priority = this.priority.MIDDLE;
				}
			}
			// Cast priority
			if (Ext.isString(priority) && /\D/.test(priority)) {
				priority = this.priority[priority.toUpperCase()];
			}

			if (!(route instanceof eo.AjaxRouter.Route)) {
				route = Ext.create(route.xclass || 'eo.AjaxRouter.Route', route);
			}
			if (!this.routes.get(priority)) {
				this.routes.add(priority, []);
			}

			this.routes.get(priority).push(route);
			this.sorted = false;

			// Route lookup
			var lu = this.lookup;
			Ext.each(route.getAliases(), function(alias) {
				lu[alias] = route;
			});

			return route;
		}
	}

	/**
	 * Get a named route by name.
	 *
	 * @return eo.AjaxRouter.Route
	 */
	,getRoute: function(name) {
		return this.lookup[name];
	}
	/**
	 * @deprecated
	 */
	,getByName: function(name) {
		return this.lookup[name];
	}

	,setActivePage: function(page) {
		var hash;
		if (page) {
			if (page.href) {
				hash = page.href;
			}
		}
		if (hash) {
			if (hash.substr(0,2) !== '#!') {
				hash = '#!/' + hash;
			}
		} else {
			hash = '';
		}
		window.location.hash = hash;
	}

}, function() {

	var initialPath = this.prototype.getCurrentPath();

	Ext.onReady(function() {

		var me = eo.AjaxRouter;

		// Load configuration
		eo.Ajax.request({
			params: {
				controller: me.configController
				,action: 'getRoutesConfig'
			}
			,success: function(data) {
				me.register(data.routes);

				eo.getApplication().onStarted(function() {
					me.route(initialPath);
				});

				Ext.fly(window).on('hashchange', function() {
					eo.AjaxRouter.route();
				});
			}
		});
	});
});
