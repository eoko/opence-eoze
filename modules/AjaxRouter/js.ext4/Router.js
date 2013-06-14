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
 * @alias Eoze.AjaxRouter.Router
 *
 * @since 2012-12-17 14:42
 */
Ext4.define('Eoze.AjaxRouter.Router', {

	singleton: true

	,requires: ['Eoze.AjaxRouter.Route']

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
		this.routes = Ext4.create('Ext.util.MixedCollection');
		this.lookup = {};
		this.lazyRoutes = [];

		// URL update is buffered for those that burst in at the same time
		this.updateTask = Ext4.create('Ext.util.DelayedTask', function() {
			window.location.hash = this.hash;
		}, this);
	}

	/**
	 * Cleans a string to make it usable as an ajax routing address.
	 *
	 * @param {String} string
	 * @return {String}
	 */
	,slugify: function(string) {
		return string.toLowerCase()
			.replace(/[²]/g, '2')
			.replace(/[æ]/g, 'ae')
			.replace(/[œ]/g, 'oe')
			.replace(/[€]/g, 'euro')
			.replace(/[$]/g, 'dollar')
			.replace(/[^a-z0-9àäâéèêëîïôöûüùÿŷç]/g, '-')
			;
		// Code for replacing accents too
		//
		//return string.toLowerCase()
		//	.replace(/[àäâ]/g, 'a')
		//	.replace(/[éèêë]/g, 'e')
		//	.replace(/[îï]/g, 'i')
		//	.replace(/[ôö]/g, 'o')
		//	.replace(/[ûüù]/g, 'u')
		//	.replace(/[ÿŷ]/g, 'y')
		//	.replace(/[ç]/g, 'c')
		//	.replace(/[²]/g, '2')
		//	.replace(/[æ]/g, 'ae')
		//	.replace(/[œ]/g, 'oe')
		//	.replace(/[€]/g, 'euro')
		//	.replace(/[$]/g, 'dollar')
		//	.replace(/[^a-z0-9]/g, '-')
		//	;
	}

	/**
	 * @param {String} [path]
	 * @return {Eoze.AjaxRouter.Router.Route}
	 */
	,route: function(path) {

		if (!this.started) {
			this.start();
		}

		if (!path) {
			path = this.getCurrentPath();
		}

		// Do not execute the action if the url has been changed with setActivePage
		if (this.hash === '#!/' + path) {
			return undefined;
		}

		var match = null;
		this.getSortedRoutes().each(function(routes) {
			Ext4.each(routes, function(route) {
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
	 * Handler for hash change.
	 *
	 * @private
	 */
	,onHashChange: function() {
		this.route();
	}

	/**
	 * @param {String} path
	 * @return {Eoze.AjaxRouter.Router.Route}
	 */
	,getMatch: function(path) {
		var matchedRoute = undefined;
		this.getSortedRoutes().each(function(routes) {
			Ext4.each(routes, function(route) {
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
			this.routes.sortByKey();
			this.sorted = true;
		}
		return this.routes;
	}

	/**
	 *
	 * ### Method signatures
	 *
	 *     // Register one route
	 *     Eoze.AjaxRouter.Router.register(route)
	 *
	 *     // Register one route with priority
	 *     Eoze.AjaxRouter.Router.register(route, priority)
	 *
	 *     // Register multiple routes
	 *     Eoze.AjaxRouter.Router.register([...])
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
	 * @param {Object/Eoze.AjaxRouter.Router.Route} route
	 * @param {Integer/String} [priority = Eoze.AjaxRouter.Router.priority.MED]
	 * @return {Object}
	 */
	,register: function(route, priority) {

		// Integrity
		if (this.started) {
			throw new Error('Routing has already started.');
		}

		// Proxy form
		if (Ext.isString(route) || route.getRoutes) {
			this.lazyRoutes.push(route);
		}
		// Array form
		else if (Ext.isArray(route)) {
			var routes = [];
			route.forEach(function(route) {
				route = this.register(route, priority);
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

			if (!(route instanceof Eoze.AjaxRouter.Router.Route)) {
				route = Ext4.create(route.xclass || 'Eoze.AjaxRouter.Router.Route', route);
			}
			if (!this.routes.get(priority)) {
				this.routes.add(priority, []);
			}

			this.routes.get(priority).push(route);
			this.sorted = false;

			// Route lookup
			var lu = this.lookup;
			route.getAliases().forEach(function(alias) {
				lu[alias] = route;
			});

			return route;
		}
	}

	/**
	 * @private
	 */
	,start: function() {

		this.lazyRoutes.forEach(function(provider) {
			if (Ext.isString(provider)) {
				provider = Ext4.create(provider);
			}
			this.register(provider.getRoutes());
		}, this);

		// Free memory
		delete this.lazyRoutes;

		this.started = true;
	}

	/**
	 * Get a named route by name.
	 *
	 * @return Eoze.AjaxRouter.Router.Route
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

	/**
	 * Sets the active page. If the passed object has an href property, it will be
	 * set as the current url.
	 *
	 * Front pages
	 * -----------
	 *
	 * Floating components like windows are considered as front pages. If the current
	 * active page is a front page and `setActivePage` is called for a page that is
	 * not a front page, this page will be ignored. That is to say, front pages have
	 * higher priority than normal, non front, pages.
	 *
	 * This behaviour can be overridden be specifying the {@link #previousPage previous
	 * page}. If the currently active page is the same as the passed previous page, then
	 * the active page will be changed, regardless of front priority.
	 *
	 * @param {Object} page
	 * @param {Object} [previousPage]
	 */
	,setActivePage: function(page, previousPage) {

		// Front pages are not replaced with back pages
		var activePage = this.activePage;
		if (page && activePage && activePage !== previousPage
			&& this.isFrontPage(activePage)
			&& !this.isFrontPage(page)) {
			return;
		}

		// Hash
		var hash;

		if (page) {
			if (page.hrefRoute) {
				hash = this.assemble(page.hrefRoute);
			} else if (page.href) {
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

		// Apply
		this.activePage = page;
		this.hash = hash;
		this.updateTask.delay(100);
	}

	/**
	 * @param {Object} page
	 *
	 * @private
	 */
	,isFrontPage: function(page) {
		return page && page.floating;
	}

	/**
	 * @private (implementation not definitive)
	 */
	,assemble: function(config) {
		if (config) {
			if (Ext.isString(config)) {
				return this.getByName(config).assemble();
			} else {
				return this.getByName(config.route).assemble(config);
			}
		} else {
			throw new Error('Illegal argument');
		}
	}

	/**
	 * Process the initial route.
	 */
	,initialRoute: function() {
		this.route();
	}

}, function() {

	var me = this;

	// Legacy aliases
	Eoze.AjaxRouter.Router = this;
	Eoze.AjaxRouter.Router.Route = Eoze.AjaxRouter.Route;

	//noinspection JSUnresolvedFunction
	var initialPath = this.getCurrentPath();

	this.initialRoute = function() {
		this.route(initialPath);
	};

	Ext4.onReady(function() {
		// Load configuration
		eo.Ajax.request({
			params: {
				controller: me.configController
				,action: 'getRoutesConfig'
			}
			,success: function(data) {
				me.register(data.routes);

				eo.app(function(app) {
					app.onStarted(function() {
						me.initialRoute();
					});
				});

				Ext.fly(window).on('hashchange', function() {
					//noinspection JSAccessibilityCheck
					Eoze.AjaxRouter.Router.onHashChange();
				});
			}
		});
	});

	// Hack windows
	var spp = Ext.Window.prototype.initComponent;
	Ext.Window.prototype.initComponent = Ext.Function.createSequence(spp, function() {
		this.on({
			activate: function() {
				Eoze.AjaxRouter.Router.setActivePage(this);
			}
			,deactivate: function() {
				var app = Oce.mx.application,
					module = app.getFrontModule(),
					page = module && module.tab || app.getFrontModuleComponent();
				Eoze.AjaxRouter.Router.setActivePage(page, this);
			}
		});
	});
	// ... Ext4 ones too
	Ext4.define('Eoze.AjaxRouter.Router.override.Ext.Window', {
		override: 'Ext.Window'

		,initComponent: function() {
			this.callParent(arguments);
			this.on({
				activate: function() {
					Eoze.AjaxRouter.Router.setActivePage(this);
				}
				,close: function() {
					var app = Oce.mx.application,
						module = app.getFrontModule(),
						page = module && module.tab || app.getFrontModuleComponent();
					Eoze.AjaxRouter.Router.setActivePage(page, this);
				}
			});
		}
	});

	// Transmit href property from compat wrapped components to their container
	if (eo.ext4 && eo.ext4.compat) {
		eo.ext4.compat.Ext4Container.prototype.afterCreateChild = Ext4.Function.createSequence(
			eo.ext4.compat.Ext4Container.prototype.afterCreateChild,
			function(child) {
				this.href = child.href;
				this.hrefRoute = child.hrefRoute;
			}
		);
	}
});
