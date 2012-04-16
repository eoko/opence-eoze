/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
Oce.deps.waitIn('eo.form.contact', 'AbstractField', function(NS, ns) {

var spp = NS.AbstractField.prototype;

eo.form.contact.PhoneNumberField = Ext.extend(eo.form.contact.AbstractField, {
	
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
				,enableKeyEvents: true
				,flex: 1
			}),
			this.unlistedCheckbox = new Ext.form.Checkbox({
				name: this.unlistedName
				,boxLabel: this.textUnlisted
				,width: Ext.isChrome ? 90 : undefined // chrome doesn't seem able to correctly
						// calculate this value automatically (maybe it's just my Ubuntu :/)
						// FireFox does it correctly
						// TODO test other browsers
			})
		];
	}

	,isValid: function() {
		return this.numberFormField.getValue();
	}
	
});

Ext.reg('phonenumberfield', NS.PhoneNumberField);

}); // deps