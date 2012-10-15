/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 déc. 2011
 */
eo.Testing.addUnitTest('DayMonthPicker', function() {

	Ext.QuickTips.init();

	var testField = new eo.form.DayMonthPicker({
		name: 'dm'
		,dayField: 'day'
		,monthField: 'month'
	});

	var formPanel = Ext.widget({
		xtype: 'form'
		,padding: 10
		,hideLabel: true
		,defaults: {
			anchor: '100%'
		}
		,items: [
			testField,
			{xtype: 'textfield', name: 'otherField', allowBlank: false}
		]
	});
	
	var jsonPanel = new eo.JsonPanel({
		decode: false
	});

	var win = new Ext.Window({
		width: 600
		,height: 300
		,layout: {
			type: 'hbox'
			,align: 'stretch'
		}
		,defaults: {
			flex: 1
		}
		,items: [formPanel, jsonPanel]
		,tbar: [{
			text: 'markInvalid()'
			,handler: function() {
				testField.markInvalid();
			}
		},{
			text: 'isValid()'
			,handler: function() {
				alert(testField.isValid());
			}
		}]
	});
	
	win.show();

	setInterval(function() {
//		jsonPanel.setValue(formPanel.form.getValues());
		var data = eo.form.JsonForm.prototype.getSubmitFieldValues.call(formPanel.form);
		Ext.apply(data, {
			isDirty: testField.isDirty()
		})
		jsonPanel.setValue(data);
	}, 500)

});