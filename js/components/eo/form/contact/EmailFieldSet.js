/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
Oce.deps.waitIn('eo.form.contact', 'AbstractFieldSet', function(NS) {
	
var spp = NS.AbstractFieldSet.prototype;
	
eo.form.contact.EmailFieldSet = Ext.extend(eo.form.contact.AbstractFieldSet, {

	fieldConfig: NS.config.email
	
	,getFieldConstructor: function() {
		return NS.EmailField;
	}
});

Ext.reg('emailsfieldset', NS.EmailFieldSet);

}); // deps