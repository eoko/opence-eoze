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
 * @since 2013-03-13 17:19
 */
Ext.define('Eoze.AccessControl.service.Login', {

	mixins: {
		observable: 'Ext.util.Observable'
		,loginManager: 'Eoze.app.LoginManager'
	}

	,loginInfos: null

	,constructor: function(config) {
		var mx = this.mixins;
		mx.observable.constructor.call(this, config);
		mx.loginManager.constructor.call(this, config);

		this.addEvents(
			/**
			 * @event login
			 * @param {Eoze.AccessControl.service.Login} this
			 * @param {Object} loginInfos
			 * @param {Integer} loginInfos.userId
			 * @param {String} loginInfos.userName
			 * @param {Boolean} loginInfos.restricted
			 */
			'login'
		);

		this.initDb().then({
			scope: this
			,success: this.setLoginData
		}).done();
	}

	/**
	 *
	 * @return {Object}
	 * @return {Integer} return.userId
	 * @return {Boolean} return.restricted
	 * @public
	 */
	,getLoginInfos: function() {
		return this.loginInfos;
	}

	// private
	,initDb: function() {
		// Let us open our database
		var deferred = new Deft.Deferred,
			dbRequest = window.indexedDB.open(this.$className, 2),
			me = this;

		dbRequest.onerror = me.dbOnError;

		dbRequest.onupgradeneeded = function(e) {
			var db = e.target.result;
			if (!db.objectStoreNames.contains('auth')) {
				db.createObjectStore('auth');
			}
		};

		dbRequest.onsuccess = function(e) {
			var db = e.target.result,
				transaction = db.transaction(['auth'], 'readonly');

			me.db = db;
			db.onerror = me.dbOnError;

			var keys = ['userId', 'lastActivity', 'token'];

			Deft.Promise.all(keys.map(function(key) {
				return me.requestFromDb(key, transaction)
			})).then(function(result) {
				var data = {};
				result.forEach(function(value, i) {
					data[keys[i]] = value;
				});
				deferred.resolve(data);
			}).otherwise(function() {
				deferred.reject();
			}).done();
		};

		return deferred.promise;
	}

	// private
	,requestFromDb: function(key, transaction) {
		var deferred = new Deft.Deferred,
			db = this.db;

		if (!transaction) {
			transaction = db.transaction(['auth'], 'readonly');
		}

		var store = transaction.objectStore('auth'),
			request = store.get(key);

		request.onsuccess = function() {
			deferred.resolve(request.result);
		};

		request.onerror = function(e) {
			deferred.reject(e);
		}

		return deferred.promise;
	}

	// private
	,dbOnError: function() {
		throw new Error('Authentification indexed database error.');
	}

	,isIdentified: function() {
		throw new Error('Deprecated. Use asynchronous API instead.');
	}

	// public (legacy support?)
	,whenIdentified: function(fn, scope) {
		var loginInfos = this.loginInfos;
		if (loginInfos) {
			fn.call(scope || this, this, loginInfos);
		} else {
			this.on({
				scope: scope || this
				,single: true
				,logged: fn
			});
		}
	}

	// public (mainly legacy)
	,notifyDisconnection: function(data) {
		var me = this;

		// care, data argument is optional
		Ext.Msg.wait(
			"La connection au serveur a été perdue. Tentative de rétablissement en cours, "
				+ "veuillez patienter. Ne rechargez pas la page pour éviter de perdre votre "
				+ "travail en cours.",
			"Connection perdue",
			{width: 300}
		).setWidth(450);

		function retry() {
			var promise = me.doAuthenticate({
					token: me.loginInfos.token
				})
				.then(function() {
					Ext.Msg.hide();
				});

			promise.otherwise(function() {
				setTimeout(retry, 5000);
			});

			return promise;
		}

		return retry();
	}

	/**
	 * Tries to authenticate user with the supplied credentials.
	 *
	 * @param {String} username
	 * @param {String} password
	 * @return {Deft.Promise}
	 */
	,authenticate: function(username, password) {
		return this.doAuthenticate({
			username: username
			,password: password
		});
	}

	/**
	 * Authenticate by credentials or token.
	 *
	 * @param {Object} data
	 * @return {Deft.promise.Promise}
	 */
	,doAuthenticate: function(data) {
		var me = this,
			deferred = new Deft.Deferred;
		eo.Ajax.request({
			params: {
				controller: 'AccessControl.login'
				,action: 'login'
			}
			,jsonData: data
			,callback: function(options, success, data) {
				if (success) {
					if (data.loginInfos) {
						// Success
						me.setLoginInfos(data.loginInfos);
						deferred.resolve(data.loginInfos);
					}else {
						// Failure
						deferred.reject(data);
					}
				} else {
					deferred.reject(data);
				}
			}
		});
		return deferred.promise;
	}

	,logout: function() {
		Ext.getBody().mask("Déconnexion", 'x-mask-loading');
		Oce.Ajax.request({
			params: {
				controller: 'AccessControl'
				,action: 'logout'
			},
			onSuccess: function() {
				window.location.hash = '';
				window.location.reload();
			}
		});
	}

	/**
	 * Applies the login info received from the server.
	 *
	 * @param {Object} loginInfos
	 * @private
	 */
	,setLoginInfos: function(loginInfos) {
		var previous = this.loginInfos;

		// must be set before letting events out
		this.loginInfos = loginInfos;

		this.fireEvent('login', this, loginInfos);

		if (!previous) {
			this.fireEvent('logged', this, loginInfos);
		}
	}

	/**
	 * Applies the login data read from the local storage.
	 *
	 * @param {Object} data
	 * @private
	 */
	,setLoginData: function(data) {
		if (data.userId && data.token) {
			debugger
		}
	}

}, function() {
	// Polyfills
	if (!window.indexedDB) {
		throw new Error('IndexedDB support is required.');
	}
});
}(window.Ext4 || Ext.getVersion && Ext));
