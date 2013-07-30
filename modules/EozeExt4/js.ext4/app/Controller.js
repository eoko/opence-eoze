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
 * Application controller with support for child application-level controllers.
 *
 * @since 2013-06-27 17:59
 */
Ext.define('Eoze.app.Controller', {
	extend: 'Ext.app.Controller'

	/**
	 * Children controllers.
	 *
	 * @config {String[]} controllers
	 */

	/**
	 * Overridden to support child controllers.
	 */
	,constructor: function() {
		this.callParent(arguments);

		if (this.controllers) {
			this.initControllers();
		}
	}

	/**
	 * Initializes child controllers.
	 *
	 * @private
	 */
	,initControllers: function() {
		var me = this,
			controllers = Ext.Array.from(me.controllers);

		me.controllers = new Ext.util.MixedCollection();

		for (var i = 0, ln = controllers.length; i < ln; i++) {
			me.getController(controllers[i]);
		}
	}

});
})(Ext4);
