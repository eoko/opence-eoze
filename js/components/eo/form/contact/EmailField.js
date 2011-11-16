/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
Oce.deps.wait('eo.form.contact.AbstractContactField', function() {

var NS  = eo.form.contact,
	sp  = NS.AbstractContactField,
	spp = sp.prototype;

eo.form.contact.EmailField = Ext.extend(sp, Ext.apply({
	
	textItem: NS.locale('email')
	,textKeyItem: 'email'
	
	,textInvalidEmail: NS.locale('invalidEmail')
	
	,emailName: 'email'
	
	,initComponent: function() {
		
		this.items = [];
		
		this.emailField = Ext.create({
			xtype: 'textfield'
			,emptyText: this.textItem
			,flex: 1
			,regex: /.+@.+\..+$/
			,regexText: this.textInvalidEmail
		});
		
		this.items.push(this.emailField);

		spp.initComponent.call(this);
		
		this.valueFields[this.emailName] = this.emailField;
	}
	
	,isValid: function() {
		if (!this.emailField.getValue()) {
			return false;
		} else {
			return this.emailField.isValid();
		}
	}
	
	,getValue: function() {
		var data = spp.getValue.call(this);
		data[this.emailName]   = this.emailField.getValue();
		return data;
	}
	
}, NS.config.email));

Ext.reg('emailfield', NS.EmailField);

});