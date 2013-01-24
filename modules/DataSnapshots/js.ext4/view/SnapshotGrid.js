/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 oct. 2012
 */
Ext4.define('Eoze.modules.DataSnapshots.view.SnapshotGrid', {
	extend: 'Ext4.grid.Panel'
	
	,plugins: ['i18n.Grid']
	
	,itemId: 'snapshotGrid'
	
	,columns: [
		{dataIndex: 'name'}
		,{dataIndex: 'date', xtype: 'datecolumn', format: 'Y-m-d H:i:s', width: 120}
		,{dataIndex: 'version'}
		,{dataIndex: 'revision'}
		,{dataIndex: 'description', flex: 1}
	]
});