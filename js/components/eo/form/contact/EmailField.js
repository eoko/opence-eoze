/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
Oce.deps.wait('eo.form.contact.AbstractField', function() {

var NS  = eo.form.contact,
	spp = NS.AbstractField.prototype;

eo.form.contact.EmailField = Ext.extend(eo.form.contact.AbstractField, {
	
	fieldConfig: 'email'
	
	,textInvalidEmail: NS.locale('invalidEmail')
	
	,emailName: 'email'
	
	,createFields: function() {
		return [
			this.emailField = Ext.create({
				name: this.emailName
				,xtype: 'textfield'
				,allowBlank: false
				,enableKeyEvents: true
				,emptyText: NS.locale(this.textKeyItem)
				,flex: 1
//				,regex: /.+@.+\..+$/
				,regexText: this.textInvalidEmail
				,vtype: 'email'
			})
		];
	}
	
});

Ext.reg('emailfield', 'eo.form.contact.EmailField');

});