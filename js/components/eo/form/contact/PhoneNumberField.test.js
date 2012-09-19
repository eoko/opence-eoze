/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 22 nov. 2011
 */
eo.Testing.addUnitTest('PhoneNumberField', function() {
Oce.deps.wait('eo.form.contact.PhoneNumberField', function() {
	
	var field = Ext.widget({
		xtype: 'phonenumberfield'
	});
	
	var win = new Ext.Window({
		
		width: 300
		,height: 200
		,layout: 'form'
		
		,items: [field]
	});
	
	win.show();	
}); // deps
}); // test