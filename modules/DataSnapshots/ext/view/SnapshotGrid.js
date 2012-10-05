/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 oct. 2012
 */
Ext4.define('Eoze.modules.DataSnapshots.view.SnapshotGrid', {
	extend: 'Ext4.grid.Panel'
	,itemId: 'snapshotGrid'
	,columns: [
		{text: "ID", dataIndex: 'id', hidden: true} // i18n
		,{text: "Nom", dataIndex: 'id'} // i18n
//		,{text: "Date", dataIndex: 'date', xtype: 'datecolumn', format: 'd/m/Y H:i:s', width: 120} // i18n
		,{text: "Date", dataIndex: 'date', xtype: 'datecolumn', format: '@Y-m-d H:i:s', width: 120} // i18n
		,{text: "Version", dataIndex: 'version'} // i18n
		,{text: "Révision", dataIndex: 'revision'} // i18n
		,{text: "Description", dataIndex: 'description', flex: 1} // i18n
	]
});