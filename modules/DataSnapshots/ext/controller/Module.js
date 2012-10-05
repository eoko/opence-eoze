/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 oct. 2012
 */
Ext4.define('Eoze.modules.DataSnapshots.controller.Module', {
	
	extend: 'Deft.mvc.ViewController'

	,requires: [
		'Eoze.i18n.Ext.util.Format',
		'Eoze.i18n.plugin.Form',
		
		'Eoze.modules.DataSnapshots.model.DataSnapshot'
	]
	
	,uses: [
		'Eoze.modules.DataSnapshots.view.EditPanel'
	]
	
	,control: {
		
		newSnapshot: {
			click: function() {
				debugger
			}
		}
		
		,snapshotGrid: {
			itemdblclick: function(table, record, el) {
				this.editRecord(record, el);
			}
		}
	}
	
	,newSnapshot: function() {
		debugger
	}
	
	,editRecord: function(record, animateTarget) {
		var win = Ext4.create({
			xclass: 'Eoze.modules.DataSnapshots.view.EditWindow'
			,animateTarget: animateTarget
		});
		win.down('form').loadRecord(record);
	}
});