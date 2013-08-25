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
 * @since 2013-03-20 12:09
 */
Ext.define('Eoze.GridModule.form.CloseConfirmPlugin', {

	texts: {
		cancel: "Annuler"
		,confirmCloseTitle: "Confirmer la fermeture"
		,confirmCloseMessage:
			"Cette fenêtre comporte des modifications qui n'ont pas été enregistrées. Souhaitez-vous "
			+ "les enregistrer ?"
		,confirmReloadMessage: [
			"Cette fenêtre comporte des modifications qui n'ont pas été enregistrées. Si elle",
			"est rechargée maintenant, ces modifications seront perdues. Souhaitez-vous continuer",
			"en abandonnant les modifications ?"
		].join(' ')
		,confirmReloadTitle: "Confirmer le rechargement"
		,no: "Non"
		,reload: "Recharger"
		,yes: "Oui"
	}

	,init: function(win) {

		var AlertWindow = Oce.Modules.GridModule.AlertWindow,
			texts = this.texts;

		win.forceClosing = false;

		win.forceClose = function() {
			win.forceClosing = true;
			win.close();
		};

		win.on({
			scope: this
			,beforerefresh: function(win) {
				if (win.formPanel.isModified()) {
					AlertWindow.show({
						modalTo: win
						// i18n
						,title: texts.confirmReloadTitle
						,msg: texts.confirmReloadMessage
						,buttons: {
							yes: texts.reload
							,cancel: texts.cancel
						}
						,fn: function(btn) {
							switch (btn) {
								case 'yes':
									win.refresh(true);
									break;
								case 'cancel':
									break;
							}
						}
					});

					return false;
				}
				return true;
			}
			,beforeclose: function() {
				if (win.forceClosing) {
					return true;
				}
				if (win.formPanel.isModified()) {
					// i18n
					AlertWindow.show({
						modalTo: win
						,title: texts.confirmCloseTitle
						,msg: texts.confirmCloseMessage
						,buttons: {
							yes: texts.yes
							,no: texts.no
							,cancel: texts.cancel
						}
						,fn: function(btn) {
							switch (btn) {
								case 'yes':
									win.formPanel.save(function(){
										win.forceClosing = true;
										win.close();
									});
									break;
								case 'no':
									win.forceClosing = true;
									win.close();
									break;
								case 'cancel':
									break;
							}
						}
					});
					return false;
				}
				return true;
			}
		});
	}
});
