/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 oct. 2012
 */
Ext4.define('Eoze.DataSnapshots.controller.Module', {
	
	extend: 'Deft.mvc.ViewController'

	,requires: [
		'Eoze.i18n.Ext.util.Format',
		'Eoze.i18n.plugin.Form',
		'Eoze.i18n.plugin.Grid',
		
		'Eoze.DataSnapshots.model.DataSnapshot'
	]
	
	,uses: [
		'Eoze.DataSnapshots.view.SnapshotGrid',
		'Eoze.DataSnapshots.view.EditPanel',
		'Eoze.DataSnapshots.view.EditWindow'
	]
	
	,control: {
		
		newSnapshot: {
			click: 'newSnapshot'
		}
		
		,snapshotGrid: {
			itemdblclick: function(table, record, el) {
				this.editRecord(record, el);
			}
		}
	}
	
	,init: function() {
		this.editWindowsById = {};
		this.callParent(arguments);
	}
	
	,newSnapshot: function(animateTarget) {
		Ext4.create({
			xclass: 'Eoze.DataSnapshots.view.EditWindow'
			,animateTarget: animateTarget
		});
	}
	
	,editRecord: function(record, animateTarget) {
		var id = record.getId(),
			map = this.editWindowsById,
			win;
		if (!record.phantom) {
			win = map[id];
		}
		if (win) {
			win.show();
		} else {
			win = Ext4.create({
				xclass: 'Eoze.DataSnapshots.view.EditWindow'
				,animateTarget: animateTarget
				,listeners: {
					close: function() {
						delete map[id];
					}
				}
			});
			// store win reference
			map[id] = win;
			// load record
			win.down('form').loadRecord(record);
		}
	}
});
