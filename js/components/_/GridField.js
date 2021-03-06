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
		this.addEvents("changed", "storecreated");
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
	 * @cfg {Object} addComboConfig A configuration object that can be passed to set
	 * some options on the add ComboBox, if one is created.
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
	 *
	 * <li>{Object} storeFieldConfig [undefined] A object to be passed as the
	 * config object for the DataStore corresponding record Field.</li>
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
		
		this.gridConfig = Ext.applyIf(this.gridConfig || {}, {
			title: this.title
			,border: this.border
		});

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
				this.configurator = Ext.widget(this.configurator);
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
				
				if (config.editor.name) {
					// Having a name set for a grid editor can be the cause of
					// great annoyance. When the editor is used at least once,
					// it will be rendered (if it has not been passed already
					// rendered). Then, when the form is submitted, the editor's
					// element will be taken into account, and its value will be
					// serialized in the form values. That is most probably not
					// what is intended, since the editor is supposed to be used
					// to edit the value of the grid's cell. For example, if the
					// editor has the name "id" set, then it will most probably
					// overwritte the record's own id value... So, you should
					// double check that something wrong hasn't occured that
					// leads use here! (Thanks for attention)
					var logger = console && (console.warn || console.log);
					if (logger) {
						logger.call(console, 'Warning!!! A grid editor has a name set: '
								+ config.editor.name);
//						debugger
					}
				}
				
				if (config.editor == 'checkbox') {
					colConfig = Ext.apply({
						dataIndex: config.dataIndex || di
						,xtype: 'checkcolumn'
					}, config);
					delete colConfig.editor;
					delete colConfig.editable;
				} else if (config.editor.xtype) {
					colConfig = Ext.apply({
						dataIndex: config.dataIndex || di
					}, config, this.columnDefaults);
					colConfig.editor = config.editor instanceof Ext.Component ? 
							config.editor : Ext.widget(config.editor);
					if (!colConfig.renderer && colConfig.editor.createRenderer) {
						colConfig.renderer = colConfig.editor.createRenderer('-');
					}
				} else {
					// TODO: commented out to let pass an error with SM wizard
					// but that will need a real fix...
//					throw new Error('GridField Invalid Config');
				}
				if (colConfig) extraDataConfig = {
					dataIndex: colConfig.dataIndex || di
					,name: colConfig.name || colConfig.dataIndex
					,defaultValue: colConfig.defaultValue || null
				}; else colConfig = {};
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
			if (colConfig.storeFieldConfig) {
				dataIndexes.push(Ext.apply({
					name: di
				}, colConfig.storeFieldConfig));
				delete colConfig.storeFieldConfig;
			} else if (di) {
				dataIndexes.push(di);
			}

			// internal option means the column must not be displayed to the
			// user, yet it must exists in the store
			if (!config.internal) this.gridColumns.push(colConfig);

		}, this);

		var storeFields = [].concat(dataIndexes);
		if (this.storeExtraFields) storeFields = storeFields.concat(this.storeExtraFields);
		
		if (!this.createReader && this.storeConfig && this.storeConfig.xtype === 'groupingstore') {
			this.createReader = this.createGroupingStoreJsonReader;
		}
		
		if (this.recordType) {
			if (!Ext.isFunction(this.recordType)) {
				storeFields = Ext.extend(
						Ext.data.Record.create(storeFields), this.recordType);
			} else {
				throw new Error("Illegal State");
			}
		}

//		var store = this.store = new Ext.data.JsonStore(Ext.apply({
		var store = this.store = Ext.widget(Ext.apply({
			url: 'api'
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
			,fields: storeFields
			,autoload: true
			,sortInfo: this.sortInfo
			,reader: !this.createReader ? undefined : this.createReader({
				// must account for storeFields possibly being a recordType constructor
				fields: Ext.isFunction(storeFields) ? storeFields : dataIndexes
				,root: 'data'
				,totalProperty: 'count'
				,idProperty: this.pkName
			})
		}, this.storeConfig, {

			xtype: "jsonstore"
		}));
	
		// override loadData
		(function() {
			var uber = store.loadData;
			store.loadData = function() {
				uber.apply(this, arguments);
				me.fireEvent('loaded', me);
				me.firstLoadDone = true;
				me.dirty = false; // get my virginity back, for the modified evt
				me.initialValue = me.syncValue(true);
			};
		})();
			
		this.afterStoreCreated();
		this.fireEvent("storecreated", this, store);

		if (this.subset) store.baseParams.subset = this.subset;

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

		store.on('add', this.onSyncValue, this);
		store.on('remove', this.onSyncValue, this);
		store.on('update', this.onSyncValue, this);
		
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

				var addComboConfig = Ext.apply({
					controller: this.initialConfig.addController || this.initialConfig.controller
					,editable: true
					,baseParams: this.initialConfig.baseParams || {}
					,clearable: false
					,width: 200
				}, this.addComboConfig);

				if (this.autoComplete) {
					addComboConfig.baseParams.autoComplete = this.autoComplete;
				}

				if (this.comboPageSize) {
					addComboConfig.pageSize = this.comboPageSize;
				}

//				addComboConfig.firstLoadLatchCount = this.firstLoadLatchCount;

				var addCombo = this.addCombo = new Oce.form.ForeignComboBox(addComboConfig);
				this.addStore = this.addCombo.store;
				this.addStore.on('load', this.sortOutIds, this);

				// Lock the combo if there is no data.
				// We only do that on local stores, because otherwise, it causes
				// problem with Contacts/Enfants field... The solution however
				// would probably be that any component needing the combo to
				// be locked should ask it explicitly... <= TODO
				if (this.addStore.local) {
					var setAddStoreEnabled = function() {
						var enabled = this.getCount();
						addCombo.setEnabled(enabled);
						if (me.addButton) me.addButton.setEnabled(enabled);
					}
					this.addStore.on({
						add: setAddStoreEnabled,
						remove: setAddStoreEnabled,
						datachanged: setAddStoreEnabled,
						load: setAddStoreEnabled
					});
				}
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
					var sm = me.grid.getSelectionModel();
					if (sm) sm.selectRow(editor.rowIndex-1, false);
				}
			});
			this.rowEditor.on('afteredit', function(editor) {
				if (me.newRecord) {
					me.newRecord = null;
					if (editor.repeat) this.onAddEditor();
				}
				var sm = me.grid.getSelectionModel();
				if (sm) sm.selectRow(editor.rowIndex, false);
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
	
	// protected
	,afterStoreCreated: function() {
		// I am a hook
	}
	
	// private
	,createGroupingStoreJsonReader: function(config) {
		return new Ext.data.JsonReader(config);
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
		if (this.addCombo) {
			this.addCombo.reset();
		}
	}

	,setBaseParam: function(name, value, reload) {
		this.store.setBaseParam(name, value);
		if (this.addStore) this.addStore.setBaseParam(name, value);
		if (reload) {
			this.load();
		}
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
					me.initialValue = me.syncValue(true);
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
			var record = this.createRecord(json);
			this.store.add(record);
			return record
		}
	}
	
	,addRecord: function(data) {
		var record = this.createRecord(data);
		this.store.add(record);
		record.markDirty();
		return record;
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
	,eachSubmitableRecord: function(cb, scope) {
		this.store.each(cb, scope);
	}
	
	// private
	,onSyncValue: function() {
		this.syncValue();
	}

	// private
	,syncValue: function(supressEvent) {
		var ids = [];
		var extraData = [];
		var i = this.orderStartIndex;
//		this.store.each(function(reccord) {
		this.eachSubmitableRecord(function(reccord) {
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
		
		// save value
		var v = !this.extraData || this.extraData.length == 0 ? ids : extraData,
			el = this.el,
			dom = el && el.dom;

		this.structuredValue = v;
		v = Ext.encode(v);
		
		if (dom) {
			dom.value = v;
		}
		
		if (!supressEvent && (this.firstLoadDone || this.phantom)) {
			if (v !== this.initialValue) {
				if (!this.dirty) {
					this.dirty = true;
					this.fireModifiedEvent();
					this.fireEvent('dirtychanged', true);
				}
			} else {
				if (this.dirty) {
					this.dirty = false;
					this.fireEvent('dirtychanged', false);
				}
			}
		}
		
		return v;
	}
	
	,getValue: function() {
		return this.structuredValue;
	}
	
	,fireModifiedEvent: function() {
		this.fireEvent('modified');
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
			if (this.addStore) {
				// if the add combo is not paginated, then we can lightheartedly
				// add the record back in it!
				if (!this.comboPageSize) {
					this.addStore.add(rec);

				// it is risky to add the record back to the combo's store, because it
				// could not match the current query... but since the user has just
				// removed it, we can hope for the best that he won't need to add it
				// right away. In the worst case, it will show up on the next search
				} else if (this.addStore.lastOptions && this.addStore.lastOptions.query
						&& this.addStore.lastOptions.params) {

					if (!this.addStore.lastOptions.params.query
							|| this.matchQuery(rec.json, this.addStore.lastOptions.params.query)) {

						this.addStore.add(rec);
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
		new RegExp('^' + Ext.escapeRe(query), 'i').test(record.data.nom);
	}

	,onRemove: function() {
		if (this.checkboxSel) {
			this.checkboxSel.each(function(reccord) {
				this.removeById(reccord.data[this.pkName]);
			}, this);
		}
	}
//
//	,setValue: function(v) {
////		Oce.form.GridField.superclass.setValue.apply(this, arguments);
////		if (this.el) this.syncValue(this.store);
//	}

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
		
		var el = me.grid.bwrap;
		if (el) {
			el.mask('Ajout', 'x-mask-loading');
		}

		Oce.mx.application.getModuleInstance(this.editModule,
			function(module) {
				module.addRecord(function(newId, data) {
					//me.add(data);
					me.store.reload({
						callback: function() {
							var el = me.grid.bwrap;
							if (el) {
								el.unmask();
							}
						}
					});
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

		var me = this,
			el = me.grid.bwrap;
		
		if (el) {
			el.mask('Suppression', 'x-mask-loading');
		}

		Oce.mx.application.getModuleInstance(this.editModule,
			function(module) {
				module.deleteRecord(
					ids
					,function() {
						me.store.reload({
							callback: function() {
								var el = me.grid.bwrap;
								if (el) {
									el.unmask();
								}
							}
						});
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
	
	/**
	 * Overrides {@link Ext.form.Field#initValue}.
	 * 
	 * Overridden code:
	 * 
	 *     function (){
     *         if(this.value !== undefined){
     *             this.setValue(this.value);
     *         }else if(!Ext.isEmpty(this.el.dom.value) && this.el.dom.value != this.emptyText){
     *             this.setValue(this.el.dom.value);
     *         }
     *         this.originalValue = this.getValue();
     *     }
	 * 
	 * Uses {@link #syncValue}, because `this.setValue(this.el.dom.value)` in the
	 * orginal code would be called with the json-encoded value, which would not
	 * work with {@link eo.form.GridField#setValue}, resulting in the dom element
	 * value's to be empty.
	 * 
	 * @protected
	 */
	,initValue: function() {
		
		var phantom = this.phantom; // save initial value
		this.phantom = false; // prevent the modified event from firing for new records
		
		// set el.dom.value
		this.syncValue(true);
		
		// restore initial phantom value
		this.phantom = phantom;

		// this is set in overridden initValue... so I comply
		this.originalValue = this.el.dom.value;
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
						if (tbItem) {
							if (Ext.isArray(tbItem)) tbarItems = tbarItems.concat(tbItem);
							else tbarItems.push(tbItem);
						}
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
					tbarItems.push(this.addButton = new Ext.Button({
						text: this.toolbar.addText
						,handler: this.onAdd
						,scope: this
						,iconCls: "ico ico_add"
					}));
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
		
		// This is needed for GridField to behave as expected in TabPanel, when
		// it is set as a tab's only component. More generaly, this is probably
		// needed to have the hide() method works correctly.
		//
		// Ext.Component:
		//  
		//	onHide : function(){
		//		this.getVisibilityEl().addClass('x-hide-' + this.hideMode);
		//	}
		//
		//	getVisibilityEl : function(){
		//		return this.hideParent ? this.container : this.getActionEl();
		//	}
		//	
		//	getActionEl : function(){
		//		return this[this.actionMode];
		//	}
		// 
		this.actionMode = "wrap";

		// This is necessary to make the component compatible with
		// the FieldLabeler plugin
		this.el.setWidth = Ext.Function.createSequence(this.el.setWidth, function(w) {
			if (me.grid) me.grid.setWidth(w);
		})
		this.el.setHeight = Ext.Function.createSequence(this.el.setHeight, function(h) {
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
		
		var sm;
		if (this.checkboxSel) {
			sm = this.checkboxSel;
		} else if (!this.disableSelection && (!this.gridConfig || !this.gridConfig.disableSelection)) {
			sm = new Ext.grid.RowSelectionModel();
		}

		var me = this;

		var gridConfig = Ext.apply({
//			columns: this.checkboxSel ? [this.checkboxSel].concat(this.gridColumns) : this.gridColumns
			cm: new Ext.grid.ColumnModel(this.checkboxSel ? [this.checkboxSel].concat(this.gridColumns) : this.gridColumns)
	        ,clicksToEdit: 1
			,selModel: sm
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
		
		if (this.cls) {
			gridConfig.cls = gridConfig.cls ? gridConfig.cls + " " : "";
			gridConfig.cls += this.cls;
		}

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
			gridConfig.listeners['rowdblclick'] = function(g, rowIndex) {
				me.editRow(rowIndex);
			};
		}

		this.initDragDrop(gridConfig);

		this.grid = new Ext.grid.EditorGridPanel(gridConfig);
		this.grid.on('afteredit', this.onSyncValue.createDelegate(this));
		
		this.grid.gridField = this;

		this.initDropTarget(this.grid);
	}
	
	,editRow: function(rowIndex) {
		
		var me = this,
			id = this.store.getAt(rowIndex).id;
			
		Oce.mx.application.getModuleInstance(
			this.editModule
			,function(module) {
				module.editRecord(id, null, function(win) {
					win.on('aftersave', function() {
						var el = me.grid.bwrap;
						if (el) {
							el.mask('Synchronisation', 'x-mask-loading');
						}
						me.store.reload({
							callback: function() {
								var el = me.grid.bwrap;
								if (el) {
									el.unmask();
								}
							}
						});
					});
				});
			}
		);
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
			});
		}
	}

	,initDropTarget: function(grid) {
		if (!this.orderable) return;
//		this.grid.on('afterrender', function() {
			var ddrow = new Ext.dd.DropTarget(grid.getView().mainBody, {
				ddGroup : this.gridDDGroup
				,notifyDrop : function(dd, e, data){
					var sm = grid.getSelectionModel();
					if (!sm) {
						if (console && console.warn) {
							console.warn("Cannot use drag drop with no selection model!");
						}
						return;
					}
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

Ext.reg('gridfield', 'Oce.form.GridField');

Oce.deps.reg('eo.form.GridField');
