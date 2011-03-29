Oce.deps.wait(['eo.form.GridField', 'eo.form.GridField.Action'], function() {

var GRID_ACTION = eo.form.GridField.Action;

var CQLIX_PLUGIN = eo.form.GridField.CqlixPlugin = eo.Object.create({

	constructor: function(config) {
		Ext.apply(this, config);
		this.created = true;
		
		if (!this.model) throw new Error();
		if (Ext.isFunction(this.model)) this.model = this.model.call(this.modelScope || this.scope || this);
		
		// This option (form) is used on a semi-experimental basis by 
		// SMOption.Produits, to push its special Rules field...
		if (this.form) {
			this.addTitle = this.form.addTitle || this.addTitle || this.form.title;
			this.editTitle = this.form.editTitle || this.editTitle || this.form.title;
			var x = this.formExtra = Ext.apply({}, this.form);
			delete x.addTitle;
			delete x.editTitle;
		}
	}
	
	,configure: function(gridField) {

		Ext.apply(gridField, {
			phantom: this.phantom
			,fullBuffer: this.fullBuffer
			,controller: gridField.controller || this.controller
		});

		var editAction = new eo.form.GridField.ModelAction.Edit({
			model: this.model
			,gridField: gridField
			,winTitle: this.editTitle
			,formExtra: this.formExtra // XP
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
		
		var addExtraColumns = this.addExtraColumns;

		// ColumnModel
		var cm = gridField.fields = this.model.createGridFieldColumnModel({
			override: this.override
			,fields: this.fields
			,editable: this.editable
			,pkName: this.model.primaryKeyName
			,addExtraColumns: function(cols) {
				if (addExtraColumns) addExtraColumns(cols);
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
				});
			}
		});

		if (this.toolbar !== false) gridField.toolbar = Ext.apply(gridField.toolbar || {}, {
			actions: [
				new eo.form.GridField.ModelAction.Add(Ext.apply({
					model: this.model
					,addWindow: true
					,winTitle: this.addTitle
					,formExtra: this.formExtra // XP
					,buildForm: this.buildAddForm || this.buildForm
				}, this.addAction))
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
	}
}); // CqlixPlugin

Ext.reg("gridfield.cqlix", CQLIX_PLUGIN);

}); // deps closure


// Injection of GridField specific methods in cqlix.Model
Oce.deps.wait('eo.cqlix.Model', function() {

	Ext.override(eo.cqlix.Model, {
		createGridFieldColumnModel: function(config) {
			var tmp = this.fieldCreateGridColumnMethodName;
			this.fieldCreateGridColumnMethodName = "createGridFieldColumn";
			var r = this.createColumnModel(config);
			this.fieldCreateGridColumnMethodName = tmp;
			return r;
		}
	}); // eo.cqlix.Model overrides

	Ext.override(eo.cqlix.ModelField, {
		createGridFieldColumn: function(config) {
			if (this.internal === true && this.isPrimaryKey()) {
				return this.doCreateGridColumn(Ext.apply({
					internal: true
					,submit: true
				}, config));
			} else {
				return this.createGridColumn(config);
			}
		}
	}); // eo.cqlix.ModelField overrides

}); // deps closure