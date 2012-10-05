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
	
	,afterShow: function(animateTarget, cb, scope) {
		if (this.animShow || this.animateTarget !== animateTarget) {
			this.callParent(arguments);
		} else {
			this.callParent([null, cb, scope]);
		}
	}

	,onHide: function(animateTarget, cb, scope) {
		if (!this.animHide || this.animateTarget !== animateTarget) {
			this.callParent([null, cb, scope]);
		} else {
			this.callParent(arguments);
		}
	}
});