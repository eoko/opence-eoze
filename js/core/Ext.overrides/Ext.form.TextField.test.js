/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 4 juin 2012
 */
eo.Testing.addUnitTest('TextField.overrides', function() {
Oce.deps.wait('Ext.form.Field.overrides', function() {
	
	var items = [{
		fieldLabel: 'Test 1'
		,msgTarget: 'side'
		,allowBlank: false
		,maxLength: 4
		,enforceMaxLength: true
	},{ // This second field has been used as playfield...
		fieldLabel: 'Test 2'
		,msgTarget: 'side'
		,xtype: 'numberfield'
		,regex: /^\d{0,5}([.,]\d{0,2})?$/
		,regexText: 'La valeur maximale de ce champ est de xxx'
//		,fullMaskRe: true
//		,plugin :  [new Ext.ux.InputTextMask('99/99/9999 99:99', true)]
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
//				,msgTarget: 'side'
			}
			,padding: 15
			,items: items
		}]
	});
	
	win.show();
	
}); // wait deps
}); // test