/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 24 mai 2012
 */
Oce.deps.wait(['eo.Testing', 'Ext.form.Field.overrides'], function() {
eo.Testing.addUnitTest('Field.overrides', function() {
		
	var items = [{
		fieldLabel: 'Test 1'
		,msgTarget: 'side'
		,allowBlank: false
	},{
		fieldLabel: 'Test 2'
		,msgTarget: 'side'
	}];

	var win = new Ext.Window({
		width: 300
		,height: 400
		,layout: 'fit'
		,items: [{
			xtype: 'form'
			,defaultType: 'textfield'
			,defaults: {
				anchor: '100%'
				,msgTarget: 'side'
			}
			,padding: 15
			,items: items
		}]
	});
	
	win.show();
	
}); // test
}); // wait deps