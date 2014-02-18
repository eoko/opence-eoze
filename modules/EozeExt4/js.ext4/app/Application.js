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
 * Global state & events holder.
 *
 * @since 2013-03-28 13:03
 */
Ext4.define('Eoze.app.Application', {
	extend: 'Ext.app.Application'

	,inject: {
		authService: 'auth'
	}

	,requires: [
		'Eoze.UserPreferences.Manager'
	]

	,mixins: {
		observable: 'Ext.util.Observable'
	},

	/**
	 * @property {Eoze.app.LoginManager}
	 * @private
	 */
	loginManager: undefined

	/**
	 * @property {Eoze.UserPreferences.Manager}
	 * @private
	 */
	,prefsManager: undefined

	,EVENT_CONFIGURED: 'configured'
	,EVENT_STARTED: 'started'
	,EVENT_DATE_CHANGED: 'datechanged'

	,constructor: function(config) {
		this.callParent(arguments);

		this.mixins.observable.constructor.call(this, config);

		this.addEvents(
			this.EVENT_CONFIGURED,
			this.EVENT_STARTED,
			this.EVENT_DATE_CHANGED
		);

		// set login manager
		this.loginManager = this.getAuthService();

		// pref manager
		this.prefsManager = Ext4.create('Eoze.UserPreferences.Manager');

		// Helper methods
		this.registerEozeHelpers();

		// Legacy bootstrap
		this.legacyBootstrap();

		// Legacy -- Fires dependencies
		Oce.deps.reg('Eoze.app.Application');
	}

	,legacyBootstrap: function() {
		// TODO #legacy

		// mx
		Oce.mx = {};
		Ext.iterate(Oce.functionality, function(name, fn){
			Oce.mx[name] = fn.get();
		});

		Oce.deps.reg('Oce.Bootstrap.start');
	}

	/**
	 * Setups Eoze global helper methods.
	 *
	 * @private
	 */
	,registerEozeHelpers: function() {
		var me = this;
		/**
		 * @return {eo.app.Application}
		 */
		eo.getApplication = function() {
			return me;
		};
		/**
		 * @return {Opence.Opence.model.Configuration}
		 */
		eo.getOpenceConfiguration = function() {
			return me.app.openceConfiguration;
		}
	}

	/**
	 * @return eo.app.LoginManager
	 */
	,getLoginManager: function() {
		return this.loginManager;
	}

	,setEozeApplication: function(app) {
		this.app = app;

		app.onConfigure = Ext.Function.createSequence(app.onConfigure, function() {

			// fire event
			this.fireEvent(this.EVENT_CONFIGURED, this);

			// set year manager
			this.yearManager = Oce.mx.application.YearManager;
			this.relayEvents(this.yearManager, [this.EVENT_DATE_CHANGED]);
		}, this);

		app.afterStart = Ext.Function.createSequence(app.afterStart, function() {
			this.started = true;
			this.fireEvent(this.EVENT_STARTED, this);
		}, this);
	}

	/**
	 * Register a callback that will be executed after the application has been started. If the
	 * application is already started, the callback will be executed immediately.
	 *
	 * @param {Function} callback
	 * @param {Object} scope
	 */
	,onStarted: function(callback, scope) {
		if (this.started) {
			// If some requires have been added with Ext.require, we want them loaded before we proceed
			Ext4.onReady(function() {
				Ext4.callback(callback, scope || this, [this]);
			}, this);
		} else {
			this.on({
				single: true
				,started: callback
				,scope: scope
			});
		}
	}

	,getYearManager: function() {
		return this.yearManager;
	}

	,getDate: function() {
		return this.getYearManager().getDate();
	}

	,getPreferences: function(path, callback, scope) {
		this.prefsManager.get(path, callback, scope);
	}

	/**
	 * Gets the version of the currently running Opence client/server pair.
	 *
	 * Can be used to clean version dependent cache, like for example user preferences depending
	 * on the existence of some specific fields.
	 *
	 * @return {String}
	 */
	,getOpenceVersion: function () {
		return eo.getOpenceConfiguration().get('versionId');
	}
});
