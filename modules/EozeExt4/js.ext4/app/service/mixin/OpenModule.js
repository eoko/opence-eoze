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
 * @since 2013-07-08 15:16
 */
Ext.define('Eoze.app.service.mixin.OpenModule', {

	/**
	 * @cfg {String}
	 * @protected
	 */
	moduleName: null

	/**
	 * Optionnal constructor; allows for creation of instance by configuration.
	 *
	 * @param {Object} config
	 * @param {String} config.moduleName
	 */
	,constructor: function(config) {
		Ext.apply(this, config);
	}

	/**
	 * Get the Folder {@link Oce.GridModule module}.
	 *
	 * @param {Function} callback Callback that will be called in the instance scope.
	 * @private
	 */
	,getModule: function(callback) {
		var me = this;
		Oce.getModule(this.moduleName, function(module) {
			callback.call(me, module);
		});
	}

	/**
	 * Opens the folder module.
	 *
	 * @param {Function} [callback]
	 * @param {Object} [scope]
	 */
	,openModule: function(callback, scope) {
		this.getModule(function(module) {
			module.executeAction('open', callback, scope);
		});
	}

});
})(window.Ext4 || Ext.getVersion && Ext);
