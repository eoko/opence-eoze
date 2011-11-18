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
	
	fieldConfig: 'address'
	
	,textStreet:  NS.locale('number, street')
	,textZipCode: NS.locale('zip')
	,textCity:    NS.locale('city')
	
	,streetName:  'street'
	,zipCodeName: 'zip'
	,cityName:    'city'
	
	,fieldsLayout: 'v'
	
	,createFields: function() {
		return [
			this.streetField = new Ext.form.TextArea({
				name: this.streetName
				,emptyText: this.textStreet
				,height: 36
				,emptyValue: null
			}),

			this.zipField = new Ext.form.TextField({
				name: this.zipCodeName
				,emptyText: this.textZipCode
				,emptyValue: null
			}),

			this.cityField = new Ext.form.TextField({
				name: this.cityName
				,emptyText: this.textCity
				,emptyValue: null
			})
		];
	}
	
	,isValid: function() {
		return this.streetField.getValue()
				|| this.zipField.getValue()
				|| this.cityField.getValue();
	}
	
});

Ext.reg('addressfield', eo.form.contact.AddressField);

}); // deps