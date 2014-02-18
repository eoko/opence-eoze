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
 * @since 2013-03-28 12:09
 */
Ext4.define('Eoze.app.LoginManager', {

	EVENT_LOGGED: 'logged'
	,EVENT_LOGIN: 'login'
	,EVENT_LOGOUT: 'logout'

	,constructor: function(config) {
		this.addEvents(
			/**
			 * @event logged
			 *
			 * Fires one time, when the user is logged.
			 *
			 * @param {Eoze.app.LoginManager} this
			 * @param {Object} loginInfos
			 * @param {Integer} loginInfos.userId
			 * @param {Boolean} loginInfos.restricted
			 */
			this.EVENT_LOGGED,
			/**
			 * @event login
			 *
			 * Fires each time the user successfully authenticate themselves.
			 *
			 * @param {Eoze.app.LoginManager} this
			 * @param {Object} loginInfos
			 * @param {Integer} loginInfos.userId
			 * @param {Boolean} loginInfos.restricted
			 */
			this.EVENT_LOGIN,
			/**
			 * @even logout
			 *
			 * Fires each time the user successfully close their authenticated session.
			 *
			 * @param {Eoze.app.LoginManager} this
			 */
			this.EVENT_LOGOUT
		);
	}
});
