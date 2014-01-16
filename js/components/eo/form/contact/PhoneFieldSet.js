/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
Oce.deps.waitIn('eo.form.contact', 'AbstractFieldSet', function(NS) {
	
var sp  = NS.AbstractFieldSet,
	spp = sp.prototype;
	
eo.form.contact.PhoneFieldSet = Ext.extend(eo.form.contact.AbstractFieldSet, {

	fieldConfig: NS.config.phone

	,textKeyItem: 'phoneNumber'

	,title: NS.locale('phone')

	,getFieldConstructor: function() {
		return NS.PhoneNumberField;
	}
});

Ext.reg('phonenumbersfieldset', 'eo.form.contact.PhoneFieldSet');
	
}); // deps