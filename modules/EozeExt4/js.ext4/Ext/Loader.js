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
 * @since 2013-09-09 16:23
 */
Ext.define('Eoze.Ext.Loader', {}, function() {

	var uber = Ext.Loader,
		loadScript = uber.loadScript,
		loadScriptFile = uber.loadScriptFile;

	Ext.apply(Ext.Loader, {
		loadScript: function (options) {
			var config = this.getConfig(),
				isString = typeof options == 'string',
				url = isString ? options : options.url;

			if (isString) {
				options = {
					url: url
				};
			}

			if (config.cachingKey) {
				options.url += '?' + config.cachingParam + '=' + config.cachingKey;
			}

			return loadScript.call(this, options);
		}

		/**
		 * Load a script file, supports both asynchronous and synchronous approaches
		 * @private
		 */
		,loadScriptFile: function(url, onLoad, onError, scope, synchronous) {
			var config = this.getConfig();
			if (config.cachingKey) {
				url += '?' + config.cachingParam + '=' + config.cachingKey;
			}
			return loadScriptFile.apply(this, [url, onLoad, onError, scope, synchronous]);
		}
	});

});
})(window.Ext4 || Ext.getVersion && Ext);
