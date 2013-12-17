/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 19 juin 2012
 */
eo.Testing.addUnitTest('FormattedPhoneNumberField', function() {
	
	var field = Ext.widget({
		xtype: 'formattedphonefield'
	});
	
	var enabled = true;
	var button = new Ext.Button({
		text: 'Switch formatting'
		,handler: function() {
			enabled = !enabled;
			field.setFormattingAvailable(enabled);
		}
	})
	
	var win = new Ext.Window({
		
		width: 300
		,height: 200
		,layout: 'form'
		
		,items: [field, button]
	});
	
	win.show();
});