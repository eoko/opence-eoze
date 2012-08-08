/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
Oce.deps.waitIn('eo.form.contact', 'AbstractFieldSet', function(NS) {
	
var spp = NS.AbstractFieldSet.prototype;

eo.form.contact.AddressFieldSet = Ext.extend(eo.form.contact.AbstractFieldSet, {

	fieldConfig: NS.config.address
	
	,getFieldConstructor: function() {
		return NS.AddressField;
	}
});

Ext.reg('addressesfieldset', NS.AddressFieldSet);
	
}); // deps