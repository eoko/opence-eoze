Oce.deps.wait('eo.form.GridField', function() {

var ACTION = eo.form.GridField.Action = Ext.extend(Ext.util.Observable, {

	gridField: undefined

	,constructor: function(config) {

		eo.form.GridField.Action.superclass.constructor.call(this, config);

		this.handler = this.run;

		Ext.apply(this, config);

		this.wins = [];
		this.tbItems = [];

		if (config.gridField) this.init(config.gridField);

//		if (this.handler) {
//			this.run = this.handler.createDelegate(this.scope || this.gridField);
//		}
	}

	,init: function(gridField) {
		if (this.gridField) {
			if (gridField !== this.gridField) {
				throw new Error('Action already initialized');
			} else {
				return;
			}
		}

		this.gridField = gridField;

		this.gridField.on('destroy', this.destroy, this);

		this.doInit(this);
	}

	,doInit: function() {}

	,destroy: function() {
		if (this.wins && this.wins.length) {
			Ext.each(this.wins, function(win) {
				win.destroy();
			});
		}
	}

	,getSelectedRecord: function() {
		var sm = this.gridField.getSelectionModel();
		if (!sm) return undefined;
		return sm.getSelected();
	}

	,run: function() {}

	,getRootWindow: function() {
		return this.gridField.findParentBy(function(e) {
			return e instanceof Ext.Window;
		});
	}

	,setEnabled: function(flag) {
		if (flag) this.enable();
		else this.disable();
	}

	,enable: function() {
		Ext.each(this.tbItems, function(items) {
			if (Ext.isFunction(items.enable)) items.enable();
		});
	}

	,disable: function() {
		Ext.each(this.tbItems, function(items) {
			if (Ext.isFunction(items.disable)) items.disable();
		});
	}

	,createToolbarItem: function() {

		var item = Ext.create(Ext.apply({
			text: '- Action -'
			,xtype: "button"
			,handler: this.handler
			,scope: this.scope || this
			//,iconCls: "ico ico_add"
		}, this.toolbarItemConfig));
		
		this.tbItems.push(item);
		return item;
	}
});

ACTION.Remove = Ext.extend(ACTION, {

	toolbarItemConfig: {
		iconCls: 'ico ico_delete'
		,text: "Supprimer" // i18n
		,disabled: true
	}

	,doInit: function() {
		ACTION.Remove.superclass.doInit.apply(this, arguments);

		var me = this,
			gf = this.gridField;

		gf.createGrid = gf.createGrid.createSequence(function() {
			var grid = gf.grid;
			var sm = grid.getSelectionModel();
			if (sm) {
				sm.on('selectionchange', function() {
					me.setEnabled(sm.getSelected() !== undefined);
				});
			}
		});
	}

	,run: function() {
		var gf = this.gridField,
			grid = gf.grid,
			sm = grid.getSelectionModel();

		if (!sm) return;
		var sels = sm.getSelections();

		if (!sels || !sels.length) return;
		var store = grid.store;

		Ext.each(sels, function(row) {
			store.remove(row);
		});
	}

});

Ext.ns('eo.form.GridField.ModelAction');

eo.form.GridField.ModelAction.Add = Ext.extend(ACTION, {

	toolbarItemConfig: {
		iconCls: 'ico ico_add'
		,text: "Ajouter" // i18n
	}

	,createWin: function() {

		var win;
		var WIN_CLASS = Oce.FormWindow;
		var form = this.model.createForm();
		var gf = this.gridField;

		this.addWin = win = new WIN_CLASS({
			formPanel: form
			,minimizable: false
			,closeAction: 'hide'
			,modalTo: this.getRootWindow()
			,title: this.winTitle || 'Nouvel enregistrement' // i18n
			,buttons: [{
				text: 'Ok'
				,handler: function() {
					if (form.form.isValid()) {
						var record = gf.addFormRecord(form);
						record.markDirty();
						win.hide();
					}
				}
			}, {
				text: 'Annuler'
				,handler: function() {
					win.hide();
				}
			}]
		});

		this.wins.push(win);

		return win;
	}

	,run: function() {
		if (this.addWindow) {
			var win = this.addWin || this.createWin();
			win.show();
		}
	}
});

eo.form.GridField.ModelAction.Edit = Ext.extend(ACTION, {

	toolbarItemConfig: {
		iconCls: 'ico ico_pencil'
		,text: "Éditer" // i18n
	}

	,createWin: function() {

		var win;
		var WIN_CLASS = Oce.FormWindow;
		var formPanel = this.model.createForm();
		var gf = this.gridField;

		this.editWin = win = new WIN_CLASS({
			formPanel: formPanel
			,title: this.winTitle || 'Modifier l\'enregistrement' // i18n
			,minimizable: false
			,closeAction: 'hide'
			,modalTo: this.getRootWindow()
			,buttons: [{
				text: 'Ok'
				,handler: function() {
//					var record = gf.addFormRecord(form);
//					record.markDirty();
					formPanel.form.updateRecord(win.record);
					win.hide();
				}
			}, {
				text: 'Annuler'
				,handler: function() {
					win.hide();
				}
			}]

			,setRecord: function(record) {
				this.record = record;
				formPanel.form.loadRecord(record);
			}
		});

		this.wins.push(win);

		return win;
	}

	,editAt: function(index) {
		var record = this.gridField.grid.store.getAt(index);
		if (!record) return;
		this.run(record);
	}

	,run: function(record) {

		if (!record) {
			record = this.getSelectedRecord();
			if (!record) return;
		}

		var win = this.editWin || this.createWin();
		win.setRecord(record);
		win.show();
	}
});

ACTION.Save = Ext.extend(ACTION, {

	toolbarItemConfig: {
		iconCls: 'ico save'
		,text: "Enregistrer" // i18n
		,disabled: true
	}

	,autoSave: false
	
	,createToolbarItem: function() {
		if (!this.autoSave || this.toolbarButton) {
			return ACTION.Save.superclass.createToolbarItem.apply(this, arguments);
		} else {
			return null;
		}
	}

	,doInit: function() {
		ACTION.Save.superclass.doInit.apply(this, arguments);
		this.gridField.on('dirtychanged', function(dirty) {
			this.setEnabled(dirty);
		}, this);
	}

	,run: function() {

		var gf = this.gridField;

		var records = gf.store.getModifiedRecords();
		if (!records || !records.length) return;

		var edit = [], add = [], remove = [];

		Ext.each(records, function(r) {
			if (r.phantom) {
				add.push(r.data)
			} else {
				edit.push(r.data);
			}
		});

//		Ext.each()

		if (!edit.length && !add.length && !remove.length) {
			this.handleServerResponse(true);
			return;
		}

		var steps = {};

		if (edit.length) {
			steps.edit = edit;
		}
		if (add.length) {
			steps.add = add;
		}
		if (remove.length) {
			steps.remove = remove;
		}

		var action = {
			controller: gf.controller
			,action: "edit_subset"
			,subset: gf.subset
			,json_steps: Ext.encode(steps)
		};

		gf.mask();

		Oce.Ajax.request({
			params: action
			,onComplete: this.handleServerResponse
			,scope: this
		});
	}

	,handleServerResponse: function() {
		this.gridField.unmask();
	}
});

ACTION.Cancel = Ext.extend(ACTION, {

	toolbarItemConfig: {
		iconCls: 'ico undo'
		,text: "Annuler" // i18n
		,disabled: true
	}

	,doInit: function() {
		ACTION.Cancel.superclass.doInit.apply(this, arguments);
		this.gridField.on('dirtychanged', function(dirty) {
			this.setEnabled(dirty);
		}, this);
	}

	,run: function() {
		var store = this.gridField.store,
			records = store.getModifiedRecords();

		Ext.each(records, function(r) {
			if (r.phantom) {
				store.remove(r);
			} else {
				r.reject();
			}
		});

		this.gridField.clearDirty();
	}

})

}); // deps closure