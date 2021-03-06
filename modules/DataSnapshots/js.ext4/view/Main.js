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
Ext4.define('Eoze.DataSnapshots.view.Main', {
	
	extend: 'Ext4.Panel'
	,controller: 'Eoze.DataSnapshots.controller.Module'
	
	,requires: [
		'Eoze.DataSnapshots.model.DataSnapshot'
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
			model: 'Eoze.DataSnapshots.model.DataSnapshot'
			,data: [
				{id: 1, name: 'hop la li', date:'2012-01-01', version:'4.0.1'}
				,{id: 2, name: 'boum lala', date:'2012-01-01', version:'4.0.1'}
				,{id: 2, name: 'boum lala', date:'2012-01-01', version:'4.0.1'}
				,{id: 4, name: 'tachi', date:'2012-01-01', version:'4.0.1'}
			]
		});

		this.items = Ext.create('Eoze.DataSnapshots.view.SnapshotGrid', {
			store: store
			,border: false
		})
		
		this.callParent();
	}
});
