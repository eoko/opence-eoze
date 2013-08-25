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
 * @since 2013-03-13 09:50
 */
Ext4.define('Eoze.modules.AccessControl.view.LoginWindow', {
	extend: 'Ext.Window'

	,requires: [
		'Eoze.button.Countdown'
	]

	,controller: 'Eoze.modules.AccessControl.controller.LoginWindow'

	,title: "Identification" // i18n

	/**
	 * @cfg {Boolean}
	 */
	,exitButton: true

	,defaultFocus: 'loginField'
	,width: 380

	,modal: true

	,closable: false
	,maximizable: false
	,minimizable: false
	,collapsible: false
	,draggable: false
	,resizable: false

	,items: {
		xtype: 'form'
		,itemId: 'formPanel'
		,border: false
		,padding: 20
		,bodyStyle: {
			backgroundColor: 'transparent'
		}
		,defaults: {
			validateOnChange: false
		}
		,items: [{
			xtype: 'component'
			,itemId: 'messageBox'
			,tpl: '<div class="app-msg">{message}<br/><br/></div>'
		},{
			xtype: 'textfield'
			,itemId: 'loginField'
			,name: 'username'
			,fieldLabel: "Identifiant"
			,allowBlank: false
			,minLength: 3
			,maxLength: 45
		},{
			xtype: 'textfield'
			,itemId: 'passwordField'
			,name: 'password'
			,fieldLabel: "Mot de passe"
			,inputType: 'password'
			,allowBlank: false
			,minLength: 4
			,maxLength: 255
		}]
	}

	,buttons: [{
		text: "Ok" // i18n
		,itemId: 'okButton'
	},{
		text: "Réinitialiser" // i18n
		,itemId: 'resetButton'
	}]

	,initComponent: function() {

		if (this.exitButton) {
			this.buttons = Ext.clone(this.buttons);
			this.buttons.unshift(new Eoze.button.Countdown({
				text: "Quitter" // i18n
				,itemId: 'exitButton'
				,minutes: 15
				,autoStart: true
			}), ' ')
		}

		this.callParent(arguments);

		if (this.message) {
			var messageBox = this.down('#messageBox');
			messageBox.update(this);
			messageBox.show();
		}
	}

});
