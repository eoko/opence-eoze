/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 23 févr. 2012
 */
eo.Testing.addUnitTest('AgeField', function() {
	
	Ext.QuickTips.init();
	
	var jsonPanel = new eo.JsonPanel({
		decode: false
		,flex: 1
	});
	
	var field = new eo.form.AgeField({
		name: 'age'
		,fieldLabel: 'Le champ'
		,flex: 1
	});
	
	var fp = new Ext.FormPanel({
		defaultType: 'textfield'
		,defaults: {
			anchor: '100%'
		}
		,items: [
			field
			,{fieldLabel: 'Un autre'}
			,{fieldLabel: 'Et encore un'}
		]
	});
	
	var form = fp.form;
	
	var win = new Ext.Window({
		
		height: 200
		,width: 400
		
		,layout: {
			type: 'vbox'
			,align: 'stretch'
		}
		
		,items: [fp, jsonPanel]
	});
	
	win.show();
	
	setInterval(function() {
		jsonPanel.setValue(form.getFieldValues());
	}, 500);
	
});