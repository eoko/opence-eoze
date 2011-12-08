/**
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
 * 
 * Using the {@link #modalTo} and {@link #modalGroup} options, this 
 * class can automatically make that there there will always be only 
 * one modal {#Oce.Modules.GridModule.AlertWindow AlertWindow} modal
 * dialog for the given target window & group. If an event trigger
 * the creation of another modal dialog for the same target and
 * group, then the previous one will be closed and replaced by the
 * new one.
 */
Oce.Modules.GridModule.AlertWindow = Ext.extend(eo.Window, {
	
	width: 205
	,height: 135
	
	,minimizable: false
	,closable: false
	
	,plain: true
	,padding: 10
	,bodyStyle: 'border: 0'
	
	,defaultButton: 0
	
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
	 * @return {Ext.Window/Boolean} `true` if there isn't any other visible dialog
	 * already targetting the given window for the given modal group.
	 * 
	 * @private
	 */
	,getPreviousInModalGroup: function(modalTo, modalGroup) {
		
		// Set the arguments for instance call
		if (!modalTo) {
			modalTo = this.modalTo;
		}
		if (!modalGroup) {
			modalGroup = this.modalGroup;
		}
		
		return modalTo 
				&& modalGroup 
				&& modalTo.modalWindowGroups 
				&& modalTo.modalWindowGroups[modalGroup];
	}

	/**
	 * Overrides the `show()` method to register itself as the current modal dialog
	 * for the configured modal target & group.
	 *
	 * @private
	 */
	,show: function() {
		var modalTo = this.modalTo,
			modalGroup = this.modalGroup;
		if (modalTo && modalGroup) {
			var groups = modalTo.modalWindowGroups = modalTo.modalWindowGroups || {};
			groups[modalGroup] = this;
			this.on({
				single: true
				,hide: function() {
					delete groups[modalGroup];
				}
			});
		}
		return Oce.Modules.GridModule.AlertWindow.superclass.show.apply(this, arguments);
	}
	
	// private
	,initComponent: function() {
		
		var buttons = this.buttons = this.buttons || [],
			scope = this.scope || this,
			modalTo = this.modalTo,
			msg = this.message || this.msg;
			
		// Position relatively to modal target
		if (modalTo) {
			if (!Ext.isDefined(this.x)) {
				this.x = modalTo.x + (modalTo.getWidth() - (this.width || 250)) / 2;
			}
			if (!Ext.isDefined(this.y)) {
				this.y = modalTo.y + (modalTo.getHeight() - (this.height || 250)) / 2;
			}
		}

		// Message to html content
		if (msg) {
			this.html = '<p>' + msg + '</p>';
		}
		
		// Buttons
		if (Ext.isObject(buttons)) {
			buttons = [];
			var fn = this.fn || Ext.emptyFn;
			Ext.iterate(this.buttons, function(name, label) {
				buttons.push({
					text: label
					,name: name
					,scope: this
					,handler: function(b) {
						this.close();
						fn.call(scope, name)
					}
				})
			}, this);
			this.buttons = buttons;
		}

		else {
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
		}
		
		// If no button is configured, then let's go for a simple 'Ok' window
		if (!buttons.length) {
			buttons.push({
				text: 'Ok'
				,scope: this
				,handler: function() {
					this.close();
				}
			});
		}
		
		var previous = this.getPreviousInModalGroup();
		if (previous) {
			previous.close();
		}
		Oce.Modules.GridModule.AlertWindow.superclass.initComponent.call(this);
	}
});

/**
 * Construct & show method alternative to the {@link Oce.Modules.GridModule.AlertWindow
 * AlertWindow}'s constructor.
 * 
 * @param {Object} config The configuration of the AlertWindow to create.
 * 
 * @return {AlertWindow}
 */
Oce.Modules.GridModule.AlertWindow.show = function(config) {

	if (!config) {
		config = {};
	}
	
	var modalTo = config.modalTo,
		modalGroup = config.modalGroup,
		previous = modalTo && this.prototype.getPreviousInModalGroup(modalTo, modalGroup);
		
	if (previous) {
		previous.close();
	}
	
	var win = new this(config);
	win.show();
	return win;
};
