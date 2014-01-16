/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 24 mai 2012
 */
(function() {

var spp = Ext.form.Field.prototype,
	onRender = spp.onRender;
	
Ext.override(Ext.form.Field, {
	
	onRender: function(ct, position) {
		onRender.call(this, ct, position);
//		
//		var dh = DomHelper;
//		dh.append(this.el.dom.parentNode, this.)
	}
});

Oce.deps.reg('Ext.form.Field.overrides');

})(); // closure