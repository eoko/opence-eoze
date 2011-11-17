/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */

eo.deps.wait('eo.form.contact.locale', function() {
	
	return;

	var output = new eo.JsonPanel;
	
	var formPanel = new Oce.FormPanel({
		layout: 'fit'
		,bodyStyle: 'padding: 0'
		,border: false
		,items: {
			xtype: 'tabpanel'
			,border: false
			,deferredRender: false
			,items: [
				{
					xtype: 'container'
					,title: 'Tab 1'
					,layout: 'form'
					,items: [{
						xtype: 'textfield'
						,fieldLabel: 'nom'
						,name: 'last_name'
					},{
						xtype: 'textfield'
						,fieldLabel: 'prénom'
						,name: 'first_name'
					}]
				},{
					xtype: 'contactpanel'
					,title: 'Contact'
//					,name: 'ContactInfos'
					,value: {
						phoneNumbers: [{
							type: 'MOBILE'
							,'default': false
							,number: '062028'
							,unlisted: true
						},{
							type: 'MOBILE'
							,'default': true
							,number: '062028'
							,unlisted: false
						}]
					}
				}
			]
		}
	});

	var win = new Ext.Window({
		width: 450
		,height: 350
		,x: 200
		,layout: 'fit'
		,items: formPanel
//		,items: {
//			xtype: 'contactpanel'
////			,value: {
////				phoneNumbers: [{
////					type: 'MOBILE'
////					,'default': false
////					,number: '062028'
////					,unlisted: true
////				},{
////					type: 'MOBILE'
////					,'default': true
////					,number: '062028'
////					,unlisted: false
////				}]
////			}
//		}
	});
	
	win.show();
	
	var resultWin = new Ext.Window({
		width: 400
		,height: 300
		,x: win.el.getRight()
		,layout: 'fit'
		,items: output
	});
	
	resultWin.show();

	var isField = win.get(0).isField(win.get(0).get(0).get(1));

	setInterval(function() {
//		output.setValue(Ext.encode(win.items.get(0).getValue()))
		output.setValue(Ext.encode(win.items.get(0).getForm().getFieldValues()))
//		output.setValue(Ext.encode(Oce.form.getFormData(win.items.get(0).getForm())))
	}, 500);
	
});