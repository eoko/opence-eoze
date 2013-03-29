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
 * @since 2013-03-13 16:49
 */
Ext.define('Eoze.AccessControl.controller.LoginWindow', {
	extend: 'Deft.mvc.ViewController'

	,requires: [
		'Eoze.AccessControl.service.Login'
	]

	,control: {
		formPanel: true
		,loginField: {
			specialKey: 'onSpecialKey'
		}
		,passwordField: {
			specialKey: 'onSpecialKey'
		}
		,okButton: {
			click: 'onSubmit'
		}
		,resetButton: {
			click: 'onReset'
		}
		,exitButton: {
			live: true
			,listeners: {
				click: 'onExit'
			}
		}
	}

	,init: function() {
		this.callParent(arguments);

		var exitButton = this.getExitButton();
		if (exitButton) {
			exitButton.mon(Ext4.getBody(), {
				scope: this
				,mousemove: this.stopExitButton
				,keydown: this.stopExitButton
			});
		}
	}

	/**
	 * Gets the attached login service.
	 *
	 * @return {Eoze.AccessControl.service.Login}
	 * @public
	 */
	,getLoginService: function() {
		if (!this.loginService) {
			this.loginService = new Eoze.AccessControl.service.Login();
		}
		return this.loginService;
	}

	,onSubmit: function() {
		var loginWindow = this.getView(),
			form = this.getFormPanel().getForm();

		if (!form.isValid()) {
			return;
		}

		var username = this.getLoginField().getValue(),
			password = this.getPasswordField().getValue();

		var maskEl = loginWindow.el;
		if (maskEl) {
			maskEl.mask(
				"Interrogation du serveur", // i18n
				'x-mask-loading'
			);
		}

		this.getLoginService()
			.authenticate(username, password)
			.then({
				scope: this
				,success: function(loginInfos) {
					loginWindow.close();
				}
				,failure: function(error) {
					var defaultField = this.getLoginField();

					// Mask
					var maskEl = loginWindow.el;
					if (maskEl) {
						maskEl.unmask();
					}

					// Message
					Ext.Msg.alert(
						error.title || 'Erreur'
						,error.errorMessage || error.message || error.msg
							|| "L'identification a échoué."
						,function() {
							if (defaultField) {
								defaultField.focus();
							}
						}
					);
				}
			});
	}

	,onReset: function() {
		this.getFormPanel().getForm().reset();
		this.getLoginField().focus();
	}

	,onExit: function() {
		window.location.reload();
	}

	,onSpecialKey: function(field, e) {
		if (e.getKey() == e.ENTER) {
			this.onSubmit();
		}
	}

	,stopExitButton: function() {
		var exitButton = this.getExitButton();
		exitButton.stopClock();

		setTimeout(function() {
			exitButton.resetClock();
			exitButton.startClock();
		}, 3*60*1000);
	}
});
