/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 oct. 2012
 */
Ext4.define('Eoze.DataSnapshots.view.EditWindow', {
	
	extend: 'Ext4.window.Window'
	
	,uses: [
		'Eoze.DataSnapshots.view.EditPanel'
	]
	
	,itemId: 'editWindow'
	
	,title: "Data Snapshot" // i18n
	,layout: 'fit'
	,autoShow: true
	
	,border: false
	
	,animShow: true
	,animHide: false

	,items: [{
		xclass: 'Eoze.DataSnapshots.view.EditPanel'
	}]
});
