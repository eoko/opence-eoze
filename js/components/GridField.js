/* 
 * @author Éric Ortéga
 * @copyright (C) 2010, Éric Ortéga
 */

Ext.ns('Oce.form', 'Oce.grid', 'eo.grid');

// TODO: default value
Oce.grid.CheckColumn = Ext.extend(Ext.util.Observable, {

	constructor: function(config) {
		Ext.apply(this, config);
		Oce.grid.CheckColumn.superclass.constructor.apply(this, arguments);
		this.renderer = this.renderer.createDelegate(this);
//		this.onEvents('changed');
		this.addEvents('changed');
	}

	,init : function(grid){
		this.grid = grid;
		this.grid.on('render', function(){
			var view = this.grid.getView();
			view.mainBody.on('mousedown', this.onMouseDown, this);
		}, this);
	}

	,convertValue: function(v) {
		// null and undefined considered as 0 (the checkbox has only two visible
		// states... taking undetermined values as so would produce ui bugs)
		return v != false && v != 0 && v != null && v != undefined;
	}

	,invertValue: function(v) {
		return !this.convertValue(v);
	}

	,onMouseDown : function(e, t){
		if(t.className && t.className.indexOf('x-grid3-cc-'+this.id) != -1){
			e.stopEvent();
			var index = this.grid.getView().findRowIndex(t);
			var record = this.grid.store.getAt(index);
			record.set(this.dataIndex, this.invertValue(record.data[this.dataIndex]));
			this.fireEvent('changed');
		}
	}

	,renderer : function(v, p, record){
		p.css += ' x-grid3-check-col-td';
//		return '<div class="x-grid3-check-col'+(v?'-on':'')+' x-grid3-cc-'+this.id+'">&#160;</div>';
		return '<div class="x-grid3-check-col'
			+ (this.convertValue(v)?'-on':'')
			+ ' x-grid3-cc-'+this.id+'">&#160;</div>';
	}
});

eo.form.GridField = Oce.form.GridField = Ext.extend(Ext.form.Field, {

	defaultAutoCreate : {tag: 'input', type: 'text', autocomplete: 'off'}
	,pkName: 'id'
	,pkRowId: 'id'
	,orderField: 'order'
	,orderStartIndex: 0
	,jsonNamePrefix: 'json_'

	,edit: true

	,firstLoadDone: false

	,toolbar: {}

	// If set to true, the field will issue a request to load its subset
	// when first rendered
	,autoload: false

	/**
	 * GridField is an implementation of Ext.form.Field that uses a GridPanel
	 * to interact with the user. The content of the grid's store is serialized
	 * into an html input, so that the standard form submission is preserved.
	 * The way the data are aggregated depends on the configuration of the
	 * GridField.
	 *
	 * @cfg {Array[Object]} fields An array of configuration objects used to
	 * configure the fields to be represented/modified in the GridField.
	 *
	 * Each item of the fields can contains configuration accepted by Ext's
	 * grid Column, along with a few others:<ul>
	 *
	 * <li>{Boolean} submit [undefined] Can be used either to force a column that
	 * would not normally be submitted by default (i.e. if it is neither the primary
	 * key, nor editable) to be submitted, or to prevent a column that would be
	 * submitted by default to not be. Be careful if you decide to not submit the
	 * PK, since that would must probably result in funky things on the side of the
	 * default eoze's GridModule controllers.</li>
	 *
	 * <li>{Boolean} internal [undefined] A field flagged as <b>internal</b> will
	 * be added to the DataStore backing the Grid, but it won't be added to the
	 * Grid's columns, or ColumnModel. That means that the value of this Record's
	 * field can be used and/or submitted, but the user cannot see them in the grid,
	 * even if the show/hide command are enabled for grid header.</li>
	 * </ul>
	 */
	,constructor: function(config) {

		Oce.form.GridField.superclass.constructor.call(this, config);
		
		this.addEvents({
			loaded: true
		});

		if (Ext.isObject(this.toolbar)) {
			Ext.applyIf(this.toolbar, {
				add: false
				,addText: 'Ajouter :'
				,remove: false
				,removeText: 'Enlever'
				,removeSeparator: true
				,del: false
				,delText: 'Supprimer'
				,delSeparator: true
				,delConfirm: true
				,create: false
				,createSeparator: true
				,createText: 'Nouveau'
			});
		}
	}

	,initComponent: function() {

		var me = this;

		Oce.form.GridField.superclass.initComponent.apply(this, arguments);

		this.addedIds = [];

		var dataIndexes = [];
		var extraData = [];

		this.gridColumns = [];

		var gridPlugins = [];
		if (this.gridPlugins) gridPlugins = gridPlugins.concat(gridPlugins, this.gridPlugins);
		this.gridPlugins = gridPlugins;
		if (this.gridConfig && this.gridConfig.plugins) {
			this.gridPlugins = this.gridPlugins.concat(this.gridPlugins, this.gridConfig.plugins);
		}

		if (this.configurator) {
			if (!this.configurator.created) {
				this.configurator = Ext.create(this.configurator);
			}
			this.configurator.configure(this);
		}

		Ext.each(eo.hashToArray(this.fields, 'dataIndex'), function(config) {
			
			var di = config.dataIndex;

			config.editor = config.editor || config.editable;

			var extraDataConfig;

			var colConfig;
			if (Ext.isString(config)) {
				colConfig = Ext.apply({
					header: config
					,dataIndex: di
				}, this.columnDefaults);

			// Editable columns
			} else if (config.editor) {
				if (config.editor == 'checkbox') {
					colConfig = new Oce.grid.CheckColumn(
						Ext.apply({
							dataIndex: config.dataIndex || di
						}, config)
					);
					this.gridPlugins.push(colConfig);
					delete colConfig.editor;
				} else if (config.editor.xtype) {
					colConfig = Ext.apply({
						dataIndex: config.dataIndex || di
					}, config, this.columnDefaults);
					colConfig.editor = Ext.create(config.editor);
					if (!colConfig.renderer && colConfig.editor.createRenderer) {
						colConfig.renderer = colConfig.editor.createRenderer('-');
					}
					colConfig = new Ext.grid.Column(colConfig);
				} else {
					throw new Error('GridField Invalid Config');
				}
				extraDataConfig = {
					dataIndex: colConfig.dataIndex
					,name: colConfig.name || colConfig.dataIndex
					,defaultValue: colConfig.defaultValue || null
				};
			} else {
				colConfig = Ext.apply({
					dataIndex: config.name || di
				}, config, this.columnDefaults);
				
				if (colConfig.submit) {
					extraDataConfig = {
						dataIndex: colConfig.dataIndex
						,name: colConfig.name || colConfig.dataIndex
						,defaultValue: colConfig.defaultValue || null
					};
				}
			}

			// submit option (makes the column be submitted, ie. its dataIndex
			// is added to extraData)
			if (!extraDataConfig && colConfig.submit) {
				extraDataConfig = {
					dataIndex: colConfig.dataIndex
					,name: colConfig.name || colConfig.dataIndex
					,defaultValue: colConfig.defaultValue || null
				}
			}

			// add to extraData if needed
			if (extraDataConfig) extraData.push(extraDataConfig);

			// add to store fields
			// (we don't want to add undefined dataIndex though, from action
			// columns, for example)
			if (di) dataIndexes.push(di);

			// internal option means the column must not be displayed to the
			// user, yet it must exists in the store
			if (!config.internal) this.gridColumns.push(colConfig);

		}, this);

		var storeFields = [].concat(dataIndexes);
		if (this.storeExtraFields) storeFields = storeFields.concat(this.storeExtraFields);

//		var store = this.store = new Ext.data.JsonStore(Ext.apply({
		var store = this.store = Ext.create(Ext.apply({
			url: 'index.php'
			,totalProperty: 'count'
			,idProperty: this.pkName
			,baseParams: Ext.apply({
				controller: this.controller
				,action: this.action || 'load_subset'
				,id: this.rowId
//				,subset: this.subset
			}, this.baseParams || {})
			,pruneModifiedRecords: true
			,root: 'data'
//REM			,fields: dataIndexes
			,fields: storeFields
			,autoload: true
			,sortInfo: this.sortInfo
			,reader: !this.createReader ? undefined : this.createReader({
				fields: dataIndexes
				,root: 'data'
				,totalProperty: 'count'
				,idProperty: this.pkName
			})
		}, this.storeConfig, {

			xtype: "jsonstore"
		}));

		if (this.subset) this.store.baseParams.subset = this.subset;

		// Autoset name from subset
		if (!this.name && this.subset) this.setName(this.subset);

		if (this.fullBuffer) {
			var deletedRecord = this.deletedRecords = [];
			store.on({
				remove: function(s, record) {
					//record.reject(true);
					if (!record.phantom) deletedRecord.push(record);
				}
			})
		}

		store.on('add', this.syncValue, this);
		store.on('remove', this.syncValue, this);
		store.on('update', this.syncValue, this);
		
		if (this.rowId !== undefined) {
			store.baseParams[this.pkRowId] = this.rowId;
			store.load();
		}

		if (this.toolbar) {
			if (this.autoComplete) { // || this.toolbar.add) {

				// Init added item list on first load
				this.store.on('load', function() {
					Ext.each(this.store.data.items, function(item) {
						var isNew = true;
						for (var i=0,l=this.addedIds.length; i<l; i++) {
							if (item[this.pkName] == this.addedIds[i]) {
								isNew = false;
								break;
							}
						}
						if (isNew) this.addedIds.push(item[this.pkName]);
					}, this)
					this.syncValue();
				}, this);

				var addComboConfig = {
					controller: this.initialConfig.addController || this.initialConfig.controller
					,editable: true
					,baseParams: this.initialConfig.baseParams || {}
					,clearable: false
					,width: 200
				};

				if (this.autoComplete) {
					addComboConfig.baseParams.autoComplete = this.autoComplete;
				}

				if (this.comboPageSize) {
					addComboConfig.pageSize = this.comboPageSize;
				}

//				addComboConfig.firstLoadLatchCount = this.firstLoadLatchCount;

				this.addCombo = new Oce.form.ForeignComboBox(addComboConfig);
				this.addStore = this.addCombo.store;
				this.addStore.on('load', this.sortOutIds, this);
				this.onAddStoreInit(this.addStore);
			
			}
		}

		if (this.rowEditor) {
			this.gridPlugins.push(
				this.rowEditor = new Ext.ux.grid.RowEditor(Ext.apply({
//							clicksToEdit: false
					}, Ext.isObject(this.rowEditor) ? this.rowEditor : {}
				))
			);

//				this.onAdd = function() {
//					this.onAddEditor();
//					this.rowEditor.newRow = true;
//				}.createDelegate(this);
			this.onAdd = this.onAddEditor;

			this.rowEditor.isDirty = function() {
				return Ext.ux.grid.RowEditor.prototype.isDirty.call(this)
					|| me.newRecord;
			};

			this.rowEditor.on('canceledit', function(editor) {
				if (me.newRecord) {
					store.remove(me.newRecord);
					me.newRecord = null;
					// Select the row that was being edited or the the previous row
					// (the just edited row may have been removed in the canceledit event)
					me.grid.getSelectionModel().selectRow(editor.rowIndex-1, false);
				}
			});
			this.rowEditor.on('afteredit', function(editor) {
				if (me.newRecord) {
					me.newRecord = null;
					if (editor.repeat) this.onAddEditor();
				}
				me.grid.getSelectionModel().selectRow(editor.rowIndex, false);
			}.createDelegate(this));
		}

		if (this.selectable !== false) {
			this.checkboxSel = new Ext.grid.CheckboxSelectionModel({
				singleSelect: false
			});
		}

		if (this.extraData) {
			if (!Ext.isArray(this.extraData)) this.extraData = [this.extraData];
			var xd = this.extraData, len = xd.length;
			for (i=0; i<len; i++) {
				if (Ext.isString(xd[i])) xd[i] = {
					name: xd[i], dataIndex: xd[i]
				}
			}
		}
		if (extraData.length) {
			if (!this.extraData) {
				this.extraData = [];
			} else {
				var autoXD = {};
				Ext.each(extraData, function(xd) {
					autoXD[xd.name] = xd;
				});
				var myXDs = this.extraData, myXD;
				for (i=0,len=myXDs.length; i<len; i++) {
					myXD = myXDs[i];
					if (autoXD[myXD.name]) myXDs[i] = Ext.apply(autoXD[myXD.name], myXD);
					delete autoXD[myXD.name];
				}
				extraData = eo.hashToArray(autoXD);
			}
			this.extraData = this.extraData.concat(extraData);
		}

		// decipher orderFieldName
		if (this.orderable && this.orderField) {
			this.orderFieldName = Ext.isString(this.orderField) ?
				this.orderField : this.orderField.name;
			if (!this.orderFieldName) throw new Error(
				"Invalid order field (name is missing): " + this.orderField
			);
		}
	}

	,value: ''

	,setRowId: function(id, reload) {
		this.rowId = id;
		this.store.baseParams[this.pkRowId] = id;
		if (this.addStore) this.addStore.baseParams['rowId'] = id;
		if (reload) {
			this.load();
		}
	}

	,onAddStoreInit: function(store) {}

	,getBaseParams: function() {
		if (!this.baseParams) {
			return this.baseParams = {};
		} else {
			return this.baseParams;
		}
	}

	,clear: function() {
		this.addedIds = [];
		this.store.removeAll();
		if (this.addCombo) this.addCombo.reset();
	}

	,setBaseParam: function(name, value, reload) {
		this.store.setBaseParam(name, value);
		if (this.addStore) this.addStore.setBaseParam(name, value);
		if (reload) this.load();
		this.clear();
	}
	
//	,masked: false

	,mask: function() {
		var el = this.grid && this.grid.el;
		if (el) {
			el.mask.apply(el, arguments);
		} else {
			this.masked = true;
		}
	}

	,unmask: function() {
		var el = this.grid && this.grid.el;
		if (el) el.unmask();
		this.masked = false;
	}

	,load: function() {
		if (Oce.form.LoadLatch.getFrom(this).canLoad()) {

			//console.log("Loading GridField: " + this.fieldLabel);

			this.mask();

			var latch = 1
				,me = this
				,cb = function() {
					me.unmask();
//					if (--latch === 0) {
//						me.unmask();
//					}
				}
				;

			if (this.addStore) {
				latch++;
				this.addStore.load({
					callback: cb
				});
			}

			this.store.load({
				callback: function() {
					cb();
					me.fireEvent('loaded', me);
					me.firstLoadDone = true;
					me.dirty = false; // get my virginity back, for the modified evt
				}
			});
		}
	}

	,isDirty: function() {
		return this.dirty === true;
	}

	,clearDirty: function() {
		if (this.dirty) {
			this.dirty = false;
			this.fireEvent('dirtychanged', false);
		}
	}

	,addedIds: []

	,sortOutIds: function() {
		Ext.each(this.addedIds, function(id) {
			var rec = this.addStore.getById(id);
			if (rec) this.addStore.remove(rec);
		}, this);
	}

	,onAdd: function() {
		var val;
		if ((val = this.addCombo.getValue()) !== '') {
			var addRecord = this.addStore.getById(val);

			this.add(addRecord.json);

			this.addStore.remove(addRecord);
			this.addCombo.reset();
		}
	}

	,onAddEditor: function() {
		if (!this.rowEditor.canStartEditing()) {
			return false;
		}
		var record = this.newRecord = this.createRecord();
//		var index = this.store.data.length;
//		this.rowEditor.stopEditing();
		this.store.add(record);
		this.grid.getView().refresh();
//		this.grid.getSelectionModel().selectRow(0);
//		this.rowEditor.startEditing(index);
		return false !== this.rowEditor.startEditing(record);
	}

	,addAndEdit: function(json) {
		if (!this.rowEditor.canStartEditing()) {
			return;
		}
		var record = this.newRecord = this.createRecord(json);
//		var index = this.store.data.length;
//		this.rowEditor.stopEditing();
		this.store.add(record);
//		this.grid.getView().refresh();
//		this.grid.getSelectionModel().selectRow(0);
//		this.rowEditor.startEditing(index);
		this.rowEditor.startEditing(record);
	}

	,createRecord: function(json) {
		json = Ext.apply({}, json, this.defaultRecord);

//		var record = new this.store.recordType(
//			json,
//			this.pkName && json[this.pkName] !== undefined ? json[this.pkName] : undefined
//		);
//
//		return record;

		var init = {};
		Ext.iterate(json, function(k,v) {init[k]=null});
//		var rec = new this.store.recordType(json);
		var rec = new this.store.recordType(init);
		rec.commit();
		Ext.iterate(json, function(k,v) {rec.set(k,v)});
		rec.json = json;
		// If pkName === false, that means that the user want an id to be
		// auto generated for the row by ext, which is important as the row
		// id must uniquely identify the row for the grid selection model to
		// work correctly
		if (this.pkName && json[this.pkName]) rec.id = json[this.pkName];
		return rec;
	}

	,add: function(json) {
		if (json instanceof Ext.FormPanel || json instanceof Ext.form.BasicForm) {
			return this.addFormRecord(json);
		} else {
			this.addedIds.push(json[this.pkName]);
			this.store.add(this.createRecord(json));
		}
	}

	,addFormRecord: function(form) {
		if (form instanceof Ext.FormPanel) form = form.form;
		var record = this.createRecord(
			Oce.form.getFormData(form)
		);
		record.markDirty();
		this.store.add(record);
		return record;
	}

	// private
	,syncValue: function() {
		if (!this.el) return;
		var ids = [];
		var extraData = [];
		var i = this.orderStartIndex;
		this.store.each(function(reccord) {
			var id = reccord.data[this.pkName],
				xData = {};
			
			if (id) ids.push(id);
			if (this.pkName) xData[this.pkName] = id;

			Ext.each(this.extraData, function(xd) {
				xData[xd.name] = 
					reccord.data[xd.dataIndex] === undefined || reccord.data[xd.dataIndex] === null ?
					xd.defaultValue : reccord.data[xd.dataIndex];
			});

			if (this.orderable && this.orderFieldName) {
				xData[this.orderFieldName] = i++;
			}

			extraData.push(xData);
		}, this)

		if (!this.el.dom) return;
		this.el.dom.value = !this.extraData || this.extraData.length == 0 ?
			Ext.encode(ids)
			: Ext.encode(extraData);

		if ((this.firstLoadDone || this.phantom) && !this.dirty) {
			this.dirty = true;
			this.fireEvent('modified');
			this.fireEvent('dirtychanged', true);
		}
	}

	,removeById: function(id) {
		// unexclude id
		for (var i=0,l=this.addedIds.length; i<l; i++) {
			if (this.addedIds[i] == id) {
				this.addedIds.splice(i,1);
				break;
			}
		}
		// remove from list
		var rec = this.store.getById(id);
		if (rec) {
			this.store.remove(rec);
			// it is risky to add the record back to the combo's store, because it
			// could not match the current query... but since the user has just
			// removed it, we can hope for the best that he won't need to add it
			// right away. In the word case, it will show up on the next search
			if (this.addStore) {
				if (this.addStore.lastOptions && this.addStore.lastOptions.query
						&& this.addStore.lastOptions.params) {

					if (!this.addStore.lastOptions.params.query
							|| this.matchQuery(rec.json, this.addStore.lastOptions.params.query)) {
						this.add(rec.json);
					}
				} else {
					this.addStore.reload();
				}
			}
		}
	}

	/**
	 * This method can be customized according to the way autocomplete query are
	 * handled...
	 */
	,matchQuery: function(record, query) {
		new RegExp('^' + query, 'i').test(record.data.nom);
	}

	,onRemove: function() {
		if (this.checkboxSel) {
			this.checkboxSel.each(function(reccord) {
				this.removeById(reccord.data[this.pkName]);
			}, this)
		}
	}

	,setValue: function(v) {
//		Oce.form.GridField.superclass.setValue.apply(this, arguments);
//		if (this.el) this.syncValue(this.store);
	}

	,onCreate: function() {
		var me = this;

		var initValues;

		if (this.toolbar.createInit) {
			var form = me.findParentByType('oce.form').form || me.findParentByType('form');
			if (!form) throw new Error('No parent form?');
			initValues = {};
			Ext.iterate(this.toolbar.createInit, function(foreign,local) {
				initValues[foreign] = Ext.isFunction(local) ?
					local(me,form)
					: form.findField(local).getValue();
			});
		}

		Oce.mx.application.getModuleInstance(this.editModule,
			function(module) {
				module.addRecord(function(newId, data) {
					me.add(data);
				}, true, initValues);
			}
		);
	}

	,onDelete: function() {
		
		var ids = [];

		if (this.checkboxSel) {
			this.checkboxSel.each(function(reccord) {
				ids.push(reccord.json[this.pkName]);
			}, this)
		} else {
			return;
		}

		var me = this
			;

		Oce.mx.application.getModuleInstance(this.editModule,
			function(module) {
				module.deleteRecord(
					ids
					,function() {
						me.load()
					}
					,me.toolbar.delConfirm
					,{
						waitTarget: me.grid
					}
				);
			}
		);
	}

	// TODO test: is this hack correct? specifically, does it break the
	// form serialization procedure?
	// This method is overriden to return this.name before this.el.dom.name,
	// because this.el.dom.name may be modified at rendering, to
	// 'json_' + this.name.
	// Maybe it would be more sensible to wrap the form input dom element
	// in a div, the div being this.el...?
	,getName: function() {
		return this.name || eo.form.GridField.superclass.getName.apply(this, arguments);
	}

	,setName: function(name) {
		this.name = name;
		if (!this.el) return;
		if (this.displayOnly || !name) {
			this.el.set({name: null});
		} else {
			name = this.jsonNamePrefix ? this.jsonNamePrefix + this.name : this.name;
//			this.el.set({name: 'json_' + this.name});
			this.el.set({name: name});
		}
	}

	,onRender: function(ct, position) {
		Oce.form.GridField.superclass.onRender.apply(this, arguments);

		var me = this;
		
//		this.el.dom.style.border = '0 none';
		this.el.dom.setAttribute('tabIndex', -1);
		this.el.addClass('x-hidden');

		this.setName(this.name);
//REM		if (this.displayOnly) {
//			this.el.set({name: null});
//		} else {
//			this.el.set({name: 'json_' + this.name});
//		}

		var phantom = this.phantom; // prevent the modified event from firing for new records
		this.phantom = false;
		this.syncValue();
		this.phantom = phantom;

		this.wrap = this.el.wrap({
//            cls:'x-html-editor-wrap', cn:{cls:'x-html-editor-tb'}
            cls:'x-grid-field-wrap', cn:{cls:'x-grid-field-grid'}
		});

//		this.gridContainer = new Ext.Panel({
//			renderTo: this.wrap.dom.firstChild
//			,layout: 'fit'
//			,bodyStyle: 'background:transparent; border: 0;'
//		});

//			debugger
		if (this.toolbar) {

			var tbarItems = [];

			var actions = this.toolbar.actions;
			if (actions) {
				if (!Ext.isArray(actions)) actions = [actions];
				Ext.each(actions, function(action) {
					if (Ext.isString(action)) {
						tbarItems.push(action);
					} else {
						action.init(this);
						var tbItem = action.createToolbarItem();
						if (tbItem) tbarItems.push(tbItem);
					}
				}, this)
			}

			if (this.toolbar.create) {

				if (!this.editModule) {
					throw new Error('Missing config option: editModule');
				}

				tbarItems.push({
					text: this.toolbar.createText
					,handler: this.onCreate
					,scope: this
				})

				if (this.toolbar.createSeparator) {
					tbarItems.push('-');
				}
			}

			if (this.toolbar.add) {
//				if (this.toolbar.add instanceof eo.form.GridField.Action) {
//					var action = this.toolbar.add;
//					action.init(this);
//					var tbItem = action.createToolbarItem();
//					if (tbItem) {
//						tbarItems.push(tbItem);
//					}
//				} else {
					tbarItems.push({
						text: this.toolbar.addText
						,handler: this.onAdd
						,scope: this
						,iconCls: "ico ico_add"
					});
//				}
			}
			if (this.addStore) {
				tbarItems.push(this.addCombo);
			}
			
			if (this.toolbar.remove) {
				if (this.toolbar.removeSeparator && tbarItems[tbarItems.length-1] !== '-') {
					tbarItems.push('-');
				}
				tbarItems.push({
					text: this.toolbar.removeText
					,handler: this.onRemove
					,scope: this
					,iconCls: "ico ico_delete"
				});
			}

			if (this.toolbar.del) {
				if (this.toolbar.delSeparator && tbarItems[tbarItems.length-1] !== '-') {
					tbarItems.push('-');
				}
				tbarItems.push({
					text: this.toolbar.delText, handler: this.onDelete, scope: this
				})
			}
			
			if (this.tbarItems) {
				if (!Ext.isArray(this.tbarItems)) throw new Error('Illegal config option: tbarItems');
				tbarItems.concat(this.tbarItems);
			}

			if (tbarItems.length > 0) {
				this.tbar = new Ext.Toolbar({
					items: tbarItems
				})
			}
		}

//		var checkColumn = new Ext.grid.CheckColumn({
//		   header: 'À charge',
//		   dataIndex: 'indoor',
//		   width: 55
//		})
//		this.gridColumns.push(checkColumn);

		this.createGrid();

//		this.gridContainer.add(this.grid);

		if(!this.width){
            var sz = this.el.getSize();
            this.setSize(sz.width, this.height || sz.height);
        }
        this.resizeEl = this.positionEl = this.wrap;

		// This is necessary to make the component compatible with
		// the FieldLabeler plugin
		this.el.setWidth = this.el.setWidth.createSequence(function(w) {
			if (me.grid) me.grid.setWidth(w);
		})
		this.el.setHeight = this.el.setHeight.createSequence(function(h) {
			if (me.grid) me.grid.setHeight(h);
		})

		// The height param would prevent the membre from flexing the component
		if (this.flex) delete this.height;

		if (this.autoload) {
			this.load();
		}

		if (this.masked) {
			this.mask(this.masked);
		}
	}

	,createGrid: function() {

		var me = this

		,gridConfig = Ext.apply({
//			columns: this.checkboxSel ? [this.checkboxSel].concat(this.gridColumns) : this.gridColumns
			cm: new Ext.grid.ColumnModel(this.checkboxSel ? [this.checkboxSel].concat(this.gridColumns) : this.gridColumns)
	        ,clicksToEdit: 1
			,selModel: this.checkboxSel ? this.checkboxSel : new Ext.grid.RowSelectionModel()
			,store: this.store
			,plugins: this.gridPlugins
			,enableColumnHide: false
//			,enableColumnSort: this.sortable !== undefined ? this.sortable : false
			,renderTo: this.wrap.dom.firstChild
			,view: this.gridView ? this.gridView : undefined
			,viewConfig: this.gridViewConfig ? Ext.apply({
				autofill: true
			}, this.gridViewConfig) : undefined
//			,border: false
		}, this.gridConfig);

		// Enforce to take into account plugins that may have been added to
		// this.gridPlugins during iniatilization (else, it could be overwritted
		// by user-added plugins from this.gridConfig.plugins)
		gridConfig.plugins = this.gridPlugins;

		if (this.tbar) gridConfig.tbar = this.tbar;

		if (this.pageSize) {
			gridConfig.bbar = new Ext.PagingToolbar({
				pageSize: this.pageSize,
				store: this.store,
				displayInfo: true,
				displayMsg: 'Enregistrements {0} - {1} sur {2}', // i18n
				emptyMsg: "Aucune données existantes" // i18n

				// Override to notify full reload of the serverside store
				,doLoad : function(start, extraParams){
					var o = {}, pn = this.getParams();
					o[pn.start] = start;
					o[pn.limit] = this.pageSize;
					Ext.apply(o, extraParams);
					if(this.fireEvent('beforechange', this, o) !== false){
						this.store.load({params:o});
					}
				}
				,doRefresh: function() {
					this.doLoad(this.cursor, {reload:1});
				}
			});

			this.store.baseParams.limit = this.pageSize;
		}

		if (this.editModule && this.edit) {
			if (!gridConfig.listeners) gridConfig.listeners = {};
			gridConfig.listeners['rowdblclick'] = function(grid, rowIndex) {
				// params
				var params = [grid.store.getAt(rowIndex).id];
				if (me.editParams) params = params.concat(me.editParams);
				// execute
				Oce.mx.application.getModuleInstance(
					me.editModule
					,function(module) {
						module.editRowById.apply(module, params, function(win) {
							win.on('aftersave', function(){
								me.store.reload(Ext.apply(me.store.lastOptions.params, {
									reload: true
								}));
							});						
						});
					}
				);
			}
		}

		this.initDragDrop(gridConfig);

		this.grid = new Ext.grid.EditorGridPanel(gridConfig);
		this.grid.on('afteredit', this.syncValue.createDelegate(this));

		this.initDropTarget(this.grid);
	}
	
	,initDragDrop: function(gridConfig) {
		if (!this.orderable) return;
		this.gridDDGroup = Ext.id();
		Ext.apply(gridConfig, {
			enableDragDrop: true
			,ddGroup: this.gridDDGroup
			,ddText: 'Place this row'
		});
		if (this.ddTextField) {
			var me = this;
			gridConfig.selModel.on('beforerowselect', function(sm,i,ke,row) {
				me.grid.ddText = row.data[me.ddTextField];
			})
		}
	}

	,initDropTarget: function(grid) {
		if (!this.orderable) return;
//		this.grid.on('afterrender', function() {
			var ddrow = new Ext.dd.DropTarget(grid.getView().mainBody, {
				ddGroup : this.gridDDGroup
				,notifyDrop : function(dd, e, data){
					var sm = grid.getSelectionModel();
					var rows = sm.getSelections();
					var cindex = dd.getDragData(e).rowIndex;
					if (sm.hasSelection()) {
						for (i = 0; i < rows.length; i++) {
							this.store.remove(this.store.getById(rows[i].id));
							this.store.insert(cindex,rows[i]);
						}
						sm.selectRecords(rows);
					}
				}.createDelegate(this)
			});
//		}, this)
	}

	,onResize: function(w, h) {
		Oce.form.GridField.superclass.onResize.apply(this, arguments);
		if (this.el && this.grid) {
			if (Ext.isNumber(w)) {
				this.grid.setWidth(w);
			}
			if (Ext.isNumber(h)) {
				this.grid.setHeight(h);
			}
		}
	}

	,createAction: function(config) {
		var action = new eo.form.GridField.Action(config);
		action.init(this);
		return action;
	}
});

Ext.reg('gridfield', Oce.form.GridField);

Oce.deps.reg('eo.form.GridField');