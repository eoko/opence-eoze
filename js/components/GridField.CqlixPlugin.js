Oce.deps.wait('eo.form.GridField', function() {

var GRID_ACTION = eo.form.GridField.Action;

var CQLIX_PLUGIN = eo.form.GridField.CqlixPlugin = eo.Object.create({

	constructor: function(config) {
		Ext.apply(this, config);
		this.created = true;
		
		if (!this.model) throw new Error();
		if (Ext.isFunction(this.model)) this.model = this.model.call(this.modelScope || this.scope || this);
	}
	
	,configure: function(gridField) {

		Ext.apply(gridField, {
			phantom: this.phantom
			,fullBuffer: this.fullBuffer
			,controller: gridField.controller || this.controller
		});

		var fieldDataIndices = [], fieldConfigs = {}, hasFieldConfig = false;
		if (this.fields) {
			Ext.each(this.fields, function(field) {
				if (Ext.isString(field)) {
					fieldDataIndices.push(field);
				} else if (Ext.isObject(field)) {
					hasFieldConfig = true;
					var di = field.dataIndex;
					fieldDataIndices.push(di);
					fieldConfigs[di] = field;
				}
			});
		}

		var editAction = new eo.form.GridField.ModelAction.Edit({
			model: this.model
			,gridField: gridField
			,winTitle: this.editTitle
		});

		// Orderable
		if (this.model.orderable) {
			if (this.model.orderField) {
				gridField.orderable = true;
				gridField.orderField = this.model.orderField;
			} else {
				throw new Error("Model orderField must be set to create orderable grid");
			}
		}

		// Auto expand main field
		if (this.model.mainField) {
			gridField.gridConfig = gridField.gridConfig || {};
			Ext.applyIf(gridField.gridConfig, {
				autoExpandColumn: this.model.mainField.name
			});
		}

		var cm = gridField.fields = this.model.createColumnModel({
			override: this.override
			,fields: this.fields
			,editable: this.editable
			,addExtraColumns: function(cols) {
				cols.push({
					xtype: "actioncolumn"
//					,header: "Supprimer"
					,editable: false
					,width: 2*16+3*5
					,resizable: false
					,items: [{
						handler: function(grid, rowIndex, colIndex, item, e) {
							editAction.editAt(rowIndex);
						}
						,getClass: function() {return "ico col ico_edit"}
					}, {
						handler: function(grid, rowIndex, colIndex, item, e) {
							grid.store.removeAt(rowIndex);
							e.stopEvent();
						}
						,getClass: function() {return "ico col ico_delete"}
					}]
				})
			}
		});

		if (this.toolbar !== false) gridField.toolbar = Ext.apply(gridField.toolbar || {}, {
			actions: [
				new eo.form.GridField.ModelAction.Add({
					model: this.model
					,addWindow: true
					,winTitle: this.addTitle
				})
				,new GRID_ACTION.Remove({
				})
//				,'-'
//				,new GRID_ACTION.Save({
//					model: this.model
//				})
//				,new GRID_ACTION.Cancel({
//				})
			]
		});

//		gridField.storeConfig = Ext.apply({
//			proxy: new Ext.data.HttpProxy({
//				url: 'index.php'
//			})
//			,writer: new Ext.data.JsonWriter({
//				encode: false
//				,writeAllFields: true
//				,encodeDelete: true
//			})
//			,baseParams: {
//				controller: this.controller + ".crud"
//				,model: this.model.name
//			}
//			,autoLoad: true
//			,autoSave: false
//		}, gridField.storeConfig);

//		gridField.onAdd = function() {
//			debugger
//		}
		
//		if (hasFieldConfig) {
//			Ext.each(cm, function(c) {
//				var cfg = fieldConfigs[c.dataIndex];
//				if (cfg) Ext.apply(c, cfg);
//			});
//		}
	}

//REM	,buildDataIndexLookup: function(fields) {
//		var r = {};
//		Ext.each(fields, function(field) {
//			var di = field.dataIndex;
//			if (!di) throw new Error();
//			r[di] = field;
//		});
//		return r;
//	}
});

Ext.reg("gridfield.cqlix", CQLIX_PLUGIN);

}); // deps closure