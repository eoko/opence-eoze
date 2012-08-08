/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 16 janv. 2012
 */
eo.Testing.addUnitTest('FormattedTextField', function() {
	
	var field = Ext.create({
		xtype: 'formattedtextfield'
		,format: 'UCFirst'
	});
	
	var win = new Ext.Window({
		
		width: 300
		,height: 200
		,layout: 'form'
		
		,items: [field]
	});
	
	win.show();
});