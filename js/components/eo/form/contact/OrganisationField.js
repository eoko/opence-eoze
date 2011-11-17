/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
eo.deps.waitIn('eo.form.contact', 'AbstractField', function(NS, ns) {

var sp  = NS.AbstractField,
	spp = sp.prototype;

eo.form.contact.OrganisationField = Ext.extend(sp, {
	
	fieldConfig: 'organisation'
	
	,textSociety: NS.locale('society')
	,textTitle: NS.locale('title')
	
	,organisationName: 'organisation'
	,titleName: 'title'
	
	,initComponent: function() {
		
		this.organisationField = new Ext.form.TextField({
			emptyText: this.textSociety
			,emptyValue: null
		});
		
		this.titleField = new Ext.form.TextField({
			emptyText: this.textTitle
			,emptyValue: null
		});

		this.items = [{
			xtype: 'container'
			,flex: 1
			,layout: {
				type: 'form'
			}
			,cls: 'closer-items'
			,defaults: {
				hideLabel: true
				,anchor: '100%'
			}
			,items: [
				this.organisationField,
				this.titleField
			]
		}];
	
		spp.initComponent.call(this);
		
		this.valueFields[this.organisationName] = this.organisationField;
		this.valueFields[this.titleName] = this.titleField;
	}
	
	,isValid: function() {
		return this.organisationField.getValue()
				|| this.titleField.getValue();
	}
	
});

Ext.reg('organisationfield', NS.OrganisationField);

});