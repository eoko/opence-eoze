/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
Oce.deps.waitIn('eo.form.contact', 'AbstractFieldSet', function(NS) {
	
var sp  = NS.AbstractFieldSet,
	spp = sp.prototype;
	
eo.form.contact.OrganisationFieldSet = Ext.extend(eo.form.contact.AbstractFieldSet, {

	fieldConfig: NS.config.organisation
	
	,getFieldConstructor: function() {
		return NS.OrganisationField;
	}
});

Ext.reg('organisationsfieldset', NS.OrganisationFieldSet);
	
}); // deps