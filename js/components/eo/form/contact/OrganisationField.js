/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
eo.deps.waitIn('eo.form.contact', 'AbstractField', function(NS, ns) {

var spp = NS.AbstractField.prototype;

eo.form.contact.OrganisationField = Ext.extend(eo.form.contact.AbstractField, {
	
	fieldConfig: 'organisation'
	
	,textSociety: NS.locale('society')
	,textTitle: NS.locale('title')
	
	,organisationName: 'organisation'
	,titleName: 'title'
	
	,createFields: function() {
		return [
			this.organisationField = new Ext.form.TextField({
				name: this.organisationName
				,emptyText: this.textSociety
				,emptyValue: null
				,enableKeyEvents: true
			})
			,this.titleField = new Ext.form.TextField({
				name: this.titleName
				,emptyText: this.textTitle
				,emptyValue: null
				,enableKeyEvents: true
			})
		]
	}
	
	,isValid: function() {
		return this.organisationField.getValue()
				|| this.titleField.getValue();
	}
	
});

Ext.reg('organisationfield', 'eo.form.contact.OrganisationField');

});