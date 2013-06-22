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
 * @since 2013-06-18 12:52
 */
Ext4.define('Eoze.data.proxy.CqlixCache', {
	extend: 'Eoze.data.proxy.Cqlix'

	,requires: [
		'Eoze.util.LzString'
	]

	,alias: 'proxy.cqlixcache'

	/**
	 *	Default cache values
	 */
	,config: {
		cacheTimeout: 3600,
		cacheKey: 'proxyCache',
		noCache: false
	}

	,VERSION: 8

	,cache: null

	,constructor: function() {
		this.callParent(arguments);

		if (this.keplerReloadEvent) {
			eo.Kepler.on(this.keplerReloadEvent, function() {
				// invalid the cache
				this.VERSION++;
				this.cache = null;
				this.fireEvent('cacheexpire', this);
			}, this);
		}
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
		var requestKey = this.getRequestKey(request);

		var cache = this.getCache();
		this.runGarbageCollection();

		var requestCache = cache[requestKey];

		if ((requestCache !== undefined) && !this.isExpired(requestCache.expires)) {
			var response = requestCache.data;

			if (requestCache.compressed) {
				Eoze.util.LzString.decompress(response).then({
					scope: this
					,success: function(response) {
						response = Ext.decode(response);

						requestCache.data = response;
						requestCache.compressed = false;

						if (requestCache.type === 'xml') {
							response.responseXML = this.xmlToDocument(response.responseText);
						}

						response._cached = true;

						this.processResponse(true, operation, request, response, callback, scope);
					}
				});
			} else {
				//response = Ext.decode(response);
				response._cached = true;
				this.processResponse(true, operation, request, response, callback, scope);
			}

			return true;
		} else {
			return false;
		}
	}

	,getRequestKey: function(request) {
		return request.url + '::' + Ext4.encode(request.params);
	}

	,isExpired: function(expires) {
		return expires <= Date.now();
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
				cacheKey = this.getCacheKey(),
				requestKey = this.getRequestKey(request),
				requestCache = cache[requestKey];

			if (requestCache === undefined) {
				requestCache = cache[requestKey] = {};
			}

			requestCache.expires = Date.now() + (this.getCacheTimeout() * 1000);
			requestCache.type = 'json';

			if (response.responseText) {
				requestCache.data = {responseText: response.responseText};
			} else {
				requestCache.data = response;
			}
			if (response.responseXML) {
				requestCache.type = 'xml';
			}

			Eoze.util.LzString
				.compress(Ext.encode(requestCache.data))
				.then({
					success: function(data) {
						var uncompressedData = requestCache.data;

						requestCache.data = data;
						requestCache.compressed = true;

						window.localStorage.setItem(cacheKey, Ext.encode(cache));

						requestCache.data = uncompressedData;
						delete requestCache.compressed;
					}
				});
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
				cache = this.cache = {$version: currentVersion};
			} else {
				cache = this.cache = Ext4.decode(cache);
				if (cache.$version !== currentVersion) {
					cache = this.cache = {$version: currentVersion};
				}
			}
		}
		return cache;
	}

	/**
	 * Clears cache entries which have passed their expiration time.
	 */
	,runGarbageCollection: function() {
		var now = Date.now(),
			modified = false,
			cache = this.cache;
		for (var key in cache) {
			if (this.isExpired(cache[key].expires)) {
				delete cache[key];
				modified = true;
			}
		}
		if (modified) {
			window.localStorage.setItem(this.getCacheKey(), Ext4.encode(cache));
		}
	}

	/**
	 * Override the processResponse function so that we can add the response to the cache after we have
	 * received it from the server.
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
		return this.callParent([success, operation, request, response, function() {
			this.processResponseOperation(operation);
			Ext4.callback(callback, scope, [operation]);
		}, this]);
	}

	/**
	 * Hook method for result set post processing by proxy.
	 *
	 * @param {Ext.data.Operation} operation The operation being executed
	 * @protected
	 * @template
	 */
	,processResponseOperation: function(operation) {}

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
