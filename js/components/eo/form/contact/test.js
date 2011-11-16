/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */

eo.deps.wait('eo.form.contact.locale', function() {

	var output = new eo.JsonPanel;
	
	var win = new Ext.Window({
		width: 450
		,height: 350
		,x: 200
		,layout: 'fit'
		,items: {
			xtype: 'contactpanel'
		}
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
	
	setInterval(function() {
		output.setValue(Ext.encode(win.items.get(0).getValue()))
	}, 500);

});