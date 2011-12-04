/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 22 nov. 2011
 */
Ext.onReady(function() {
	
if (window.location.hash.substr(1).split('&').indexOf('test=addressfield') === -1) {
	return;
}

var testField = new eo.form.contact.AddressField({
	hideLabel: true
});

var formPanel = Ext.create({
	xtype: 'form'
	,padding: 10
	,hideLabel: true
	,items: [
		testField,
		{xtype: 'textfield'}
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
	,items:[
		formPanel
		,jsonPanel
	]
});

win.show();

setInterval(function() {
	jsonPanel.setValue(testField.getValue());
}, 500)
	
});