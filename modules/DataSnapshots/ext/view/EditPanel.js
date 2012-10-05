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
	
	,bodyCls: 'eo-form-body'
	,defaults: {
		width: 300
	}

	// field labels are set by i18n.Form plugin
	,items: [
		{name: 'id', xtype: 'displayfield'}
		,{name: 'name', xtype: 'textfield'}
		,{name: 'description', xtype: 'textarea'}
		,{name: 'date', xtype: 'datefield', readOnly: true, format: 'd/m/Y H:i:s'}
	]
	
	// button texts are set by i18n.Form plugin
	,dockedItems: [{
		xtype: 'toolbar'
		,items: [{
			iconCls: 'ico save'
			,itemId: 'save'
		}, {
			iconCls: 'ico delete'
			,itemId: 'delete'
		}]
	}]

});