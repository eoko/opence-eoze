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
 * @since 2013-06-18 16:48
 */
Ext4.define('Eoze.data.proxy.mixin.LocaleStorageCache', {

	requires: [
		'Eoze.util.LzString'
	]

	/**
	 *	Default cache values
	 */
	,config: {
		cacheTimeout: 3600,
		cacheKey: 'proxyCache',
		noCache: false
	}

	,VERSION: 2

	,cache: null
//	,constructor: function() {
//		this.cache = {};
//		this.callParent(arguments);
//	}

	,init: function() {
		var me = this;
		function override(name, method) {
			var uber = me[name],
				method = me[name];
			me[name] = function() {
				if (method.apply(this, arguments) !== false) {
					return uber.apply(this, arguments);
				}
			};
			me[name].$previous = uber;
		}

		['processResponse', 'read'].forEach(override);
	}

	/**
	 * Determines if response is cached and processes the cached response if it is.
	 *
	 * @param {Ext.data.Operation} operation The operation being executed
	 * @param {function} callback Callback to be executed when operation has completed
	 * @param {Object} scope Scope for the callback function
	 * @return {Boolean} if response is in cache
	 */
	,inCache: function(operation, callback, scope) {
		var request = this.buildRequest(operation, callback, scope);
		var requestKey = this.computeRequestKey(request);

		var cache = this.getCache();
		this.runGarbageCollection();

		if ((cache[requestKey] !== undefined) && (cache[requestKey].expires >= Date.now())) {
			var response = this.cache[requestKey].data;
//			response = Ext.decode(LZString.decompress(response));

			Eoze.util.LzString.decompress(response).then({
				scope: this
				,success: function(response) {
					response = Ext.decode(response);
					if (cache[requestKey].type === 'xml') {
						response.responseXML = this.xmlToDocument(response.responseText);
					}
					response._cached = true;
					this.processResponse(true, operation, request, response, callback, scope);
				}
			});

			return true;
		} else {
			return false;
		}
	}

	,computeRequestKey: function(request) {
		return request.url + '::' + Ext4.encode(request.params);
	}

	/**
	 * Adds the response to the cache if it does not already exist.
	 *
	 * @param {Ext.data.Request} request Request being sent to the server
	 * @param {Ext.data.Response} response Response returned from the server
	 */
	,addToCache: function(request, response) {
		var Ext = Ext4;
		if (!response._cached && request.action === 'read') {
			var cache = this.getCache(),
				requestKey = this.computeRequestKey(request),
				requestCache = cache[requestKey];

			if (requestCache === undefined) {
				requestCache = cache[requestKey] = {};
			}
			requestCache.expires = Date.now() + (this.getCacheTimeout() * 1000);
			requestCache.type = 'json';
			if (response.responseText) {
				Eoze.util.LzString
					.compress(Ext.encode({responseText: response.responseText}))
					.then({
						success: function(data) {
							requestCache.data = data;
						}
					});
//				this.cache[requestKey].data = {responseText: response.responseText};
//				this.cache[requestKey].data = LZString.compress(Ext.encode({responseText: response.responseText}));
			} else {
//				this.cache[requestKey].data = response;
//				this.cache[requestKey].data = LZString.compress(Ext.encode(response));
				Eoze.util.LzString
					.compress(LZString.compress(Ext.encode(response)))
					.then({
						success: function(data) {
							requestCache.data = data;
						}
					});
			}
			if (response.responseXML) {
				requestCache.type = 'xml';
			}
			window.localStorage.setItem(this.getCacheKey(), Ext.encode(this.cache));
		}
	}

	/**
	 * Loads the local storage cache into memory
	 */
	,getCache: function() {
		var currentVersion = this.VERSION,
			cache = this.cache;
		if (!cache) {
			cache = window.localStorage.getItem(this.getCacheKey());
			if (cache === null) {
				this.cache = {version: currentVersion};
			} else {
				this.cache = Ext4.decode(cache);
				if (this.cache.version !== currentVersion) {
					this.cache = {version: currentVersion};
				}
			}
		}
		return this.cache;
	}

	/**
	 * Clears cache entries which have passed their expiration time.
	 */
	,runGarbageCollection: function() {
		var now = Date.now(),
			modified = false;
		for (var key in this.cache) {
			if (this.cache[key].expires <= now) {
				delete this.cache[key];
				modified = true;
			}
		}
		if (modified) {
			window.localStorage.setItem(this.getCacheKey(), Ext4.encode(this.cache));
		}
	}

	/**
	 * Override the processResponse function so that we can add the response to the cache after we have recieved it from the server.
	 *
	 * @param {Boolean} success Whether the operation was successful or not
	 * @param {Ext.data.Operation} operation The operation being executed
	 * @param {Ext.data.Request} request Request being sent to the server
	 * @param {Ext.data.Response} response Response returned from the server
	 * @param {function} callback Callback to be executed when operation has completed
	 * @param {Object} scope Scope for the callback function
	 */
	,processResponse: function(success, operation, request, response, callback, scope) {
		if (success) {
			this.addToCache(request, response);
		}
		return this.callParent(arguments);
	}

	/**
	 * Override the read function so that we can check if the response is already cached and return it from their instead of going to the server.
	 *
	 * @param {Ext.data.Operation} operation The operation being executed
	 * @param {function} callback Callback to be executed when operation has completed
	 * @param {Object} scope Scope for the callback function
	 */
	,read: function(operation, callback, scope) {
		if (!this.inCache(operation, callback, scope)) {
			return this.callParent(arguments);
		}
	}
});
