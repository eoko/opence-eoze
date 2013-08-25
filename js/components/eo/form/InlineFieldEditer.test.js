/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 4 juin 2012
 */
eo.Testing.addUnitTest('InlineFieldEditer', function() {
	
	var items = [{
		fieldLabel: 'Inline edit'
		,value: 'Éric'
		,plugins: new eo.form.TextFieldInlineSubmitter
	},{
		fieldLabel: 'Inline edit combo'
		,xtype: 'combo'
		,store: ['eric', 'sandrine']
		,plugins: new eo.form.TextFieldInlineSubmitter({
			emptyText: 'Aucun'
		})
		,emptyText: 'Nom'
	},{ // This second field has been used as playfield...
		fieldLabel: 'Inline spinner edit'
		,xtype: 'spinnerfield'
		,value: 12
		,plugins: new eo.form.TextFieldInlineSubmitter
	},{ // This second field has been used as playfield...
		fieldLabel: 'Spinner edit'
		,xtype: 'spinnerfield'
		,value: 12
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
	
}); // test