/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
Oce.deps.waitIn('eo.form.contact', 'AbstractField', function(NS, ns) {

var sp  = NS.AbstractField,
	spp = sp.prototype;

NS.PhoneNumberField = Ext.extend(sp, {
	
	fieldConfig: 'phone'
	
	,textUnlisted: NS.locale('unlisted')
	,textItem: NS.locale('number')
	
	,numberName:   'number'
	,unlistedName: 'unlisted'

	,createFields: function(layout) {
		return [
			this.numberFormField = new Ext.form.TextField({
				name: this.numberName
				,emptyText: this.textItem
				,allowBlank: false
				,flex: 1
			}),
			this.unlistedCheckbox = new Ext.form.Checkbox({
				name: this.unlistedName
				,boxLabel: this.textUnlisted
			})
		];
	}

	,isValid: function() {
		return this.numberFormField.getValue();
	}
	
});

Ext.reg('phonenumberfield', NS.PhoneNumberField);

}); // deps