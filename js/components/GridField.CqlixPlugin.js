Oce.deps.wait('eo.form.GridField', function() {

var GRID_ACTION = eo.form.GridField.Action;

eo.form.GridField.CqlixPlugin = eo.Object.create({

	constructor: function(config) {
		Ext.apply(this, config);
		if (!this.model) throw new Error();
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

		var cm = gridField.fields = this.model.createColumnModel({
			override: this.override
			,fields: this.fields
			,editable: this.editable
			,addExtraColumns: function(cols) {
				cols.push({
					xtype: "actioncolumn"
//					,header: "Supprimer"
					,editable: false
					,width: 40
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

		gridField.toolbar = Ext.apply(gridField.toolbar || {}, {
			actions: [
				new eo.form.GridField.ModelAction.Add({
					model: this.model
					,addWindow: true
					,winTitle: this.addTitle
// XXX DEMO
					,demoHackRemiseFixed: this.demoHackRemiseFixed
					,demoHackRemisePercent: this.demoHackRemisePercent

					,createForm: function() {
						if (this.demoHackRemiseFixed || this.demoHackRemisePercent) {
							var grid, items = [{
									xtype: "textfield"
									,name: "name"
									,fieldLabel: "Nom"
								}];

							if (this.demoHackRemiseFixed) {
								items.push(grid = Ext.create({
									xtype: "gridfield"
									,anchor: "100%"
									,hideLabel: true
									,height: 200
									,fields: {
										name: {
											header: "Saison"
											,editor: {
												xtype: "oce.simplecombo"
												,data: {
													0: "Saison 1"
													,1: "Saison 2"
													,2: "Saison 3"
													,3: "Saison 4"
												}
											}
										}
										,price: {
											header: "Prix"
											,editor: {xtype: "numberfield"}
										}
										,price_remise: {
											header: "Prix avec remise"
											,editor: {xtype: "numberfield"}
										}
									}
									,defaultRecord: {
										name: ""
										,price: ""
										,price_remise: ""
									}
									,gridConfig: {
										tbar: {
											items: {
												text: "Add"
												,iconCls: 'ico ico_add'
												,handler: function() {
													grid.add(grid.defaultRecord);
												}
											}
										}
									}
								}))
							} else if (this.demoHackRemisePercent) {
								items.push({
									xtype: "numberfield"
									,fieldLabel: "Prix de base"
									,name: "price_unit"
								}, grid = Ext.create({
									xtype: "gridfield"
									,anchor: "100%"
									,hideLabel: true
									,height: 200
									,fields: {
										name: {
											header: "Saison"
											,editor: {
												xtype: "oce.simplecombo"
												,data: {
													0: "Saison 1"
													,1: "Saison 2"
													,2: "Saison 3"
													,3: "Saison 4"
												}
											}
										}
										,price: {
											header: "Pourcentage"
											,editor: {xtype: "numberfield"}
										}
									}
									,defaultRecord: {
										name: ""
										,price: ""
									}
									,gridConfig: {
										tbar: {
											items: {
												text: "Add"
												,iconCls: 'ico ico_add'
												,handler: function() {
													grid.add(grid.defaultRecord);
												}
											}
										}
									}
								}))
							}

							return new Oce.FormPanel({
								items: items
								,width: 350
							});
						} else {
							return eo.form.GridField.ModelAction.Add.prototype.createForm.call(this);
						}
					}
// < XXX DEMO
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

}); // deps closure