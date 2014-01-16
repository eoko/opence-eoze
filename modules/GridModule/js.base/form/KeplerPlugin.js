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
 * @since 2013-03-20 12:26
 */
Ext.define('Eoze.GridModule.form.KeplerPlugin', {

	/**
	 * @cfg {String} modelName
	 */

	texts: {
		byOther: " par un autre utilisateur"
		,changeMessage: "L'enregistrement a été modifiée{0}. {1}" // 0: external, 1: action
		,changeTitle: "Modifications{0}" // 0: external
		,dataWillBeReladed: "Les données vont être rechargées."
		,externalChanges: " extérieures"
		,windowWillBeClosed: "La fenêtre va être fermée."
	}

	,constructor: function(config) {
		Ext.apply(this, config);

		if (!this.modelName) {
			throw new Error('Missing configuration');
		}
	}

	,init: function(win) {
		var event = Ext.String.format('{0}#{1}:modified', this.modelName, recordId);

		win.mon(eo.Kepler, event, function(e, origin) {

			var instanceId = Oce.mx.application.instanceId;

			if (origin !== instanceId + '/' + win.id) {
				var matches = /^([^/]+)\//.exec(Oce.mx.application.instanceId),
					external = matches && matches[0] === instanceId;

				origin.substr(0, Oce.mx.application.instanceId.length);

				this.onExternalChange(win, external);
			}
		}, this);
	}

	/**
	 * Behaviour to produce when a window is externally modified. In this
	 * context, externally means out of the actual window, not necessarily
	 * from another user or session.
	 *
	 * If the window's form contains unsaved modifications, then a message
	 * will inform the user and prompt them if they want to reload the
	 * window, else the window will be silently reloaded.
	 *
	 * If no refresh method is available in the passed `win` object, then
	 * the window will be closed instead.
	 *
	 * @param {eo.Window} window The window that is concerned.
	 * @param {Boolean} [external=true] `true` to specify in the information
	 * message that the modification came from another user/session.
	 *
	 * @protected
	 */
	,onExternalChange: function(win, external) {

		var AlertWindow = Oce.Modules.GridModule.AlertWindow,
			texts = this.texts,
			actionMsg, okHandler;

		if (win.refresh) {

			// If the form is not dirty, reload without confirmation
			if (!win.formPanel.isModified()) {
				win.refresh(true);
				return;
			}

			else {
				actionMsg = texts.dataWillBeReladed;
				okHandler = function() {
					this.close(); // AlertWindow scope
					win.refresh(true);
				};
			}
		} else {
			actionMsg = texts.windowWillBeClosed;
			okHandler = function() {
				this.close(); // AlertWindow scope
				win.close();
			}
		}

		AlertWindow.show({

			modalTo: win
			,modalGroup: 'refresh'

			,title: Ext.String.format(texts.changeTitle, external ? texts.externalChanges : '')
			,message: Ext.String.format(texts.changeMessage, external ? texts.byOther : '', actionMsg)
//				"L'enregistrement a été modifié"
//				+ (external ? ' par un autre utilisateur. ' : '. ')
//				+ actionMsg

			,okHandler: okHandler
			,cancelHandler: function() {
				// Restore the normal warning on close behavior
				win.forceClosing = false;
				this.close(); // AlertWindow scope
			}
		});
	}

// For reference
//
//	,onEditWindowExternallyDeleted: function(win) {
//		NS.AlertWindow.show({
//
//			modalTo: win
//			// Same group as modified, since a deleting overrides any modifications
//			,modalGroup: 'refresh'
//
//			,title: 'Modification extérieure'
//			,message: "L'enregistrement a été supprimé par un autre utilisateur."
//				+ " La fenêtre va être fermée."
//
//			,okHandler: function() {
//				this.close();
//				win.close();
//			}
//
//			,cancelHandler: function() {
//				// Restore the normal warning on close behavior
//				win.forceClosing = false;
//				this.close();
//			}
//		});
//	}

});
