/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
Oce.deps.waitIn('eo.form.contact', 'AbstractField', function(NS, ns) {

var sp  = NS.AbstractField,
	spp = sp.prototype;

NS.PhoneNumberField = Ext.extend(sp, Ext.apply({
	
	textUnlisted: NS.locale('unlisted')
	,textItem: NS.locale('number')
	
	,numberName:   'number'
	,unlistedName: 'unlisted'
	
	,initComponent: function() {
		
		this.numberFormField = new Ext.form.TextField({
			emptyText: this.textItem
			,allowBlank: false
		});
		
		this.unlistedCheckbox = new Ext.form.Checkbox({
			boxLabel: this.textUnlisted
		});
		
		this.numberFormField.flex = 1;
	
		var items = [
			this.numberFormField,
			this.unlistedCheckbox
		];
		
		Ext.apply(this, {
			items: items
		});
		
		spp.initComponent.call(this);
		
		this.valueFields[this.numberName] = this.numberFormField;
		this.valueFields[this.unlistedName] = this.unlistedCheckbox;
	}
	
	,isValid: function() {
		return this.numberFormField.getValue();
	}
	
}, NS.config.phone));

Ext.reg('phonenumberfield', NS.PhoneNumberField);

}); // deps