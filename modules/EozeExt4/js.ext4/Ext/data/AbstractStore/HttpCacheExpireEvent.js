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
 * @since 2013-06-25 17:55
 */
Ext.define('Eoze.Ext.data.AbstractStore.HttpCacheExpireEvent', {
	override: 'Ext.data.AbstractStore'

	,constructor: function() {

		this.addEvents(
			'cacheexpire'
		);

		this.callParent(arguments);
	}

	,setProxy: function(proxy) {
		var evt = 'cacheexpire',
			handler = this.onCacheExpire,
			previousProxy = this.proxy;

		if (previousProxy && previousProxy.hasCacheExpireEvent) {
			previousProxy.un(evt, handler, this);
		}

		proxy = this.callParent(arguments);

		if (proxy.hasCacheExpireEvent) {
			proxy.on(evt, handler, this);
		}

		return proxy;
	}

	,onCacheExpire: function() {
		this.fireEvent('cacheexpire', this);
	}

	,destroyStore: function() {
		var destroyed = this.isDestroyed,
			proxy = this.proxy;
		if (proxy && proxy.hasCacheExpireEvent && !destroyed) {
			proxy.un('cacheexpire', this.onCacheExpire, this);
		}
		this.callParent(arguments);
	}

	/**
	 * Overridden to clean out the `invalidateHttpCache` from `lastOptions`. We don't want
	 * the cache to be automatically busted on each subsequent requests.
	 */
	,reload: function() {
		this.callParent(arguments);
		delete this.lastOptions.invalidateHttpCache;
	}

	/**
	 * Reloads the store, busting the proxy's HTTP cache, if any.
	 *
	 * @param {Object} options
	 */
	,reloadCache: function(options) {
		options = Ext.apply({
			invalidateHttpCache: true
		}, options);

		this.reload(options);
	}
});
})(Ext4);
