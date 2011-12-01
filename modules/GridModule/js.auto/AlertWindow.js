/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 déc. 2011
 */
Ext.ns('Oce.Modules.GridModule');

/**
 * A basic alert window, that can be modal to another window, and
 * can be required to be unique in a given modal group to that 
 * window. This class is mainly intended to offer a common basis 
 * for future developments.
 */
Oce.Modules.GridModule.AlertWindow = Ext.extend(eo.Window, {
	
	width: 205
	,height: 135
	
	,minimizable: false
	,closable: false
	
	,plain: true
	,padding: 10
	,bodyStyle: 'border: 0'
	
	/**
	 * @cfg {Ext.Window} modalTo If set, this dialog will be modal
	 * to the given window, that is the parent window will be masked
	 * and unusable as long as this dialog is visible. The default
	 * behaviour is to mask the content of the parent window (thus
	 * letting its buttons actionnable -- if the parent window is
	 * closed, the dialog will be automatically closed too). However,
	 * if the parent windows have exactly 0 children, the whole 
	 * parent window will be masked by the modal dialog.
	 */
	,modalTo: undefined
	/**
	 * @cfg {String} modalGroup An arbitrary unique identifier to be
	 * used as the modal group. Only one modal dialog can be shown
	 * in a given modal group at a given time.
	 */
	,modalGroup: undefined

	/**
	 * Asses whether there is already a dialog displayed for the given
	 * modal target and the given modal group. This method can be
	 * called either statically, by passing the two arguments, or it can
	 * be called directly on an instance from which the modal target and
	 * group will be inferred.
	 * 
	 * @param {Ext.Window} modalTo The the modal target window.
	 * @param {String} modalGroup The arbitrary unique identifier of the
	 * modal group for the given target window.
	 * 
	 * @return {Boolean} `true` if there isn't any other visible dialog
	 * already targetting the given window for the given modal group.
	 * 
	 * @private
	 */
	,isUniqueInModalGroup: function(modalTo, modalGroup) {
		
		if (!modalTo) {
			modalTo = this.modalTo;
		}
		if (!modalGroup) {
			modalGroup = this.modalGroup;
		}
						
		if (!modalTo || !modalGroup) {
			return true;
		}
		if (!modalTo.modalWindowGroups) {
			modalTo.modalWindowGroups = {};
			return true;
		}
		if (!modalTo.modalWindowGroups[modalGroup]) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Registers the dialog as the current one for the configured 
	 * modal target window and the given modal group.
	 * 
	 * @private
	 */
	,registerInModalGroup: function() {
		var modalTo = this.modalTo,
			modalGroup = this.modalGroup;
		if (modalTo && modalGroup) {
			var group = modalTo.modalWindowGroups = modalTo.modalWindowGroups || {};
			group[modalGroup] = this;
			this.on('hide', function() {
				delete group[modalGroup];
			});
		}
	}
	
	// private
	,initComponent: function() {
		
		var buttons = this.buttons = this.buttons || [],
			scope = this.scope || this;
			
		if (this.message) {
			this.html = '<p>' + this.message + '</p>';
		}
		
		if (this.okHandler) {
			buttons.push({
				text: 'Ok'
				,handler: this.okHandler
				,scope: scope
			});
		}
		if (this.cancelHandler) {
			buttons.push({
				text: 'Annuler'
				,handler: this.cancelHandler
				,scope: scope
			});
		}
		
		if (this.isUniqueInModalGroup()) {
			this.registerInModalGroup();
			Oce.Modules.GridModule.AlertWindow.superclass.initComponent.call(this);
		} else {
			this.hidden = true;
			this.show = Ext.emptyFn;
			Oce.Modules.GridModule.AlertWindow.superclass.initComponent.call(this);
			this.destroy();
		}
	}
});

Oce.Modules.GridModule.AlertWindow.show = function(config) {

	if (!config) {
		config = {};
	}
	
	if (this.prototype.isUniqueInModalGroup(config.modalTo, config.modalGroup)) {
		var win = new this(config);
		win.show();
		return win;
	}
};
