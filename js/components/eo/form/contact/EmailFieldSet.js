/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
Oce.deps.waitIn('eo.form.contact', 'AbstractFieldSet', function(NS) {
	
var sp  = NS.AbstractFieldSet,
	spp = sp.prototype;
	
NS.EmailFieldSet = Ext.extend(sp, {

	fieldConfig: NS.config.email
	
	,numTitle: 1

	,getFieldClass: function() {
		return NS.EmailField;
	}
});

Ext.reg('emailsfieldset', NS.EmailFieldSet);

}); // deps