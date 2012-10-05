/**
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 oct. 2012
 * 
 * @internal Sadly, it is required to overnest the Grid panel in another panel
 * to circumvent a bug that breaks the toolbar bottom border if the Grid panel
 * has border = false.
 * 
 */
Ext4.define('Eoze.modules.DataSnapshots.view.Main', {
	
	extend: 'Ext4.Panel'
	,mixins: ['Deft.mixin.Controllable']
	,controller: 'Eoze.modules.DataSnapshots.controller.Module'
	
	,requires: [
		'Eoze.modules.DataSnapshots.model.DataSnapshot'
	]
	
	,uses: [
		'Ext4.data.Store'
	]

	,itemId: 'dataSnapshots'
	
	,border: false

	,dockedItems: [{
		xtype: 'toolbar'
		,dock: 'top'
		,itemId: 'snapshotGridToolbar'
		,items: [{
			text: "Nouveau"
			,iconCls: 'ico DataSnapshots add'
			,itemId: 'newSnapshot'
		}]
	}]

	,initComponent: function() {
		
		var store = new Ext4.data.Store({
			model: 'Eoze.modules.DataSnapshots.model.DataSnapshot'
			,data: [
				{id: 1, date:'2012-01-01', version:'4.0.1'}
				,{id: 2, date:'2012-01-01', version:'4.0.1'}
			]
		});

		this.items = Ext.create('Eoze.modules.DataSnapshots.view.SnapshotGrid', {
			store: store
			,border: false
		})
		
		this.callParent();
	}
});
