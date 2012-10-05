/**
 * {@link eo.modules.DataSnapshots.model.DataSnapshot DataSnapshot} record edit
 * {@link Ext4.form.Panel form Panel}.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 4 oct. 2012
 */
Ext4.define('Eoze.modules.DataSnapshots.view.EditPanel', {
	
	extend: 'Ext4.form.Panel'
	,alias: ['widget.Eoze.modules.DataSnapshots.view.EditPanel']
	
	// requires & uses are in the ViewController class
	
	,itemId: 'editPanel'
	
	,plugins: ['i18n.Form']
	
	,locale: 'eo.modules.DataSnapshots.locale.FR-fr'
	
	,bodyCls: 'eo-form-body'
	,defaults: {
		width: 300
	}

	,items: [
//		,{name: 'id', xtype: 'displayfield', fieldLabel: "ID"}
//		,{name: 'name', xtype: 'textfield', fieldLabel: "Nom"}
//		,{name: 'description', xtype: 'textarea', fieldLabel: "Description"}
//		,{name: 'date', xtype: 'datefield', readOnly: true, format: 'd/m/Y H:i:s', fieldLabel: "Date"}
		{name: 'id', xtype: 'displayfield'}
		,{name: 'name', xtype: 'textfield'}
		,{name: 'description', xtype: 'textarea', fieldLabel: "locale:description"}
		,{name: 'date', xtype: 'datefield', readOnly: true, format: 'd/m/Y H:i:s', fieldLabel: "Date"}
	]
	
	,dockedItems: [{
		xtype: 'toolbar'
		,items: [{
			text: "Enregistrer" // i18n
			,iconCls: 'ico save'
			,itemId: 'save'
		}, {
			text: "Supprimer" // i18n
			,iconCls: 'ico delete'
			,itemId: 'delete'
		}]
	}]

});