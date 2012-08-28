/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
eo.deps.waitIn('eo.form.contact', 'AbstractField', function(NS) {

var spp = NS.AbstractField.prototype;
	
eo.form.contact.AddressField = Ext.extend(eo.form.contact.AbstractField, {
	
	fieldConfig: 'address'
	
	,textStreet:  NS.locale('number, street')
	,textZipCode: NS.locale('zip')
	,textCity:    NS.locale('city')
	,textCountry: NS.locale('country')
	
	,streetName:  'street'
	,zipCodeName: 'zip'
	,cityName:    'city'
	,countryName: 'iso3166_alpha3'
	
	,fieldsLayout: 'v'
	
	,createFields: function() {
		var fields = [
			this.streetField = new Ext.form.TextArea({
				name: this.streetName
				,emptyText: this.textStreet
				,height: 36
				,emptyValue: null
				,enableKeyEvents: true
				,allowBlank: true
			}),

			this.zipField = new Ext.form.TextField({
				name: this.zipCodeName
				,emptyText: this.textZipCode
				,emptyValue: null
				,enableKeyEvents: true
				,allowBlank: true
			}),

			this.cityField = new Ext.form.TextField({
				name: this.cityName
				,emptyText: this.textCity
				,emptyValue: null
				,enableKeyEvents: true
				,allowBlank: true
			}),
			
		];
		
		if (this.countryName) {
			
			var C = eo.form && eo.form.country && eo.form.country.CountryComboBox
					|| Ext.form.TextField;
			
			fields.push(
				this.countryField = new C({
					name: this.countryName
					,emptyText: this.textCountry
					,emptyValue: null
					,enableKeyEvents: true
					,allowBlank: true
				})
			);
		}
		
		return fields;
	}
	
//	,createExtraFields: function() {
//		return [
//			new Ext.form.TextField({
//				emptyText: 'Pays'
//			})
//			,new Ext.form.TextArea({
//				height: 36
//				,emptyText: 'Commentaire'
//			})
//		];
//	}
	
	,isValid: function() {
		return !!(this.streetField.getValue()
				|| this.zipField.getValue()
				|| this.cityField.getValue()
				|| (this.countryField && this.countryField.getValue()));
	}
	
});

Ext.reg('addressfield', eo.form.contact.AddressField);

}); // deps