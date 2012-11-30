/**
 * Adds {@link #animShow} and {@link #animHide} options.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 5 oct. 2012
 */
Ext4.define('Eoze.Ext.window.Window', {
	override: 'Ext4.window.Window'
	
	/**
	 * @cfg {Boolean}
	 * True to animate the window with the default {@link #animateTarget} when it 
	 * is shown. If an animateTarget is explicitely passed to the {@link #show}
	 * method, this option will not prevent it.
	 */
	,animShow: true
	/**
	 * @cfg {Boolean}
	 * True to animate the window with the default {@link #animateTarget} when it 
	 * is hidden. If an animateTarget is explicitely passed to the {@link #hide}
	 * method, this option will not prevent it.
	 */
	,animHide: true
	
	/**
	 * @protected
	 * 
	 * Implements {@link #animShow} option.
	 * 
	 * @since 05/10/12 14:54
	 * @since Ext 4.1.1
	 */
	,afterShow: function() {
		if (!this.animShow) {
			var back = this.animateTarget;
			delete this.animateTarget;
			this.callParent(arguments);
			this.animateTarget = back;
		} else {
			this.callParent(arguments);
		}
	}

	/**
	 * @protected
	 * 
	 * Fixes Window doClose implementation: it should not be overridding animateTarget 
	 * behaviour. This is perfectly handled by the Component, and doesn't need to be
	 * tweaked here.
	 * 
	 * However, forcing the animateTarget the way it was done in Ext 4.1.1 prevents
	 * proper overriding of {@link #onHide}.
	 * 
	 * @since 05/10/12 14:54
	 * @since Ext 4.1.1
	 */
	,doClose: function() {
		var me = this;

		// Being called as callback after going through the hide call below
		if (me.hidden) {
			me.fireEvent('close', me);
			if (me.closeAction == 'destroy') {
				this.destroy();
			}
		} else {
			// Not forcing animateTarget, letting onHide do its job as intented
			me.hide(undefined, me.doClose, me); // rx+
			// close after hiding
			// me.hide(me.animateTarget, me.doClose, me); // rx-
		}
	}

	/**
	 * @protected
	 * 
	 * Implements {@link #animHide} option.
	 * 
	 * @since 05/10/12 14:54
	 * @since Ext 4.1.1
	 */
	,onHide: function(animateTarget, cb, scope) {
		if (!this.animHide) {
			var back = this.animateTarget;
			delete this.animateTarget;
			this.callParent(arguments);
			this.animateTarget = back;
		} else {
			this.callParent(arguments);
		}
	}
});