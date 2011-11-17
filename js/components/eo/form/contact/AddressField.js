/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
eo.deps.waitIn('eo.form.contact', 'AbstractField', function(NS) {

var sp  = NS.AbstractField,
	spp = sp.prototype;
	
NS.AddressField = Ext.extend(sp, {
	
	textItem: NS.locale('address')
	
	,textStreet:  NS.locale('number, street')
	,textZipCode: NS.locale('zip')
	,textCity:    NS.locale('city')
	
	,streetName:  'street'
	,zipCodeName: 'zip'
	,cityName:    'city'
	
	,initComponent: function() {
		
		this.streetField = new Ext.form.TextArea({
			emptyText: this.textStreet
			,height: 36
			,emptyValue: null
		});
		
		this.zipField = new Ext.form.TextField({
			emptyText: this.textZipCode
			,emptyValue: null
		});
		
		this.cityField = new Ext.form.TextField({
			emptyText: this.textCity
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
				this.streetField,
				this.zipField,
				this.cityField
			]
		}];
	
		spp.initComponent.call(this);
		
		this.valueFields[this.streetName]  = this.streetField;
		this.valueFields[this.zipCodeName] = this.zipField;
		this.valueFields[this.cityName]    = this.cityField;
	}
	
	,isValid: function() {
		return this.streetField.getValue()
				|| this.zipField.getValue()
				|| this.cityField.getValue();
	}
	
});

Ext.reg('addressfield', eo.form.contact.AddressField);

}); // deps