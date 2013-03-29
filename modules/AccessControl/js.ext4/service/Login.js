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
	}

	,constructor: function(config) {
		this.mixins.observable.constructor.call(this, config);

		this.addEvents(
			/**
			 * @event login
			 * @param {Eoze.AccessControl.service.Login} this
			 * @param {Object} loginInfos
			 */
			'login'
		);
	}

	,authenticate: function(username, password) {

		var deferred = Ext4.create('Deft.Deferred');

		eo.Ajax.request({

			params: {
				controller: 'AccessControl.login'
				,action: 'login'
			}

			,jsonData: {
				username: username
				,password: password
			}

			,scope: this
			,callback: function(options, success, data) {
				if (success) {
					// Success
					if (data.loginInfos) {
						deferred.resolve(data.loginInfos);
						this.fireEvent('login', this, data.loginInfos);
					}
					// Failure
					else {
						deferred.reject(data);
					}
				} else {
					// TODO handle error
					debugger
				}
			}
		});

		return deferred.promise;
	}
});
