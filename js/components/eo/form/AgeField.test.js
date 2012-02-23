/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 23 févr. 2012
 */
eo.Testing.addUnitTest('AgeField', function() {
	
	var jsonPanel = new eo.JsonPanel({
		decode: false
		,flex: 1
	});
	
	var field = new eo.form.AgeField({
		name: 'age'
		,flex: 1
	});
	
	var fp = new Ext.FormPanel({
		items: [field]
	});
	
	var form = fp.form;
	
	var win = new Ext.Window({
		
		height: 200
		,width: 300
		
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