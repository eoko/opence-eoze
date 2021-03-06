(function() {
	
var NS = Ext.ns('Oce.Modules.GridModule');

/**
 *
 * Alias
 * -----
 * 
 * {@link #GridModule} is aliased as `Oce.Modules.GridModule.GridModule`. This alias
 * is still supported for legacy reasons, but its usage is deprecated.
 * 
 * 
 * Overridding
 * -----------
 * 
 * {@link Oce.GridModule#beforeCreateWindow}: must be called before a window
 * is created, with the config object as argument. This is an opportunity for
 * customizing components to modify the configuration of any window (to set icon,
 * for example).
 *
 * @todo IMPORTANT the configuration objects for windows are shared between all
 * GridModule instances. This is very problematic, since that prevent
 * functionnality providers (plugins) to easily and efficiently customize a
 * window before its creation.
 *
 * 
 * Actions
 * -------
 *
 * ### Remove and Add Actions
 * 
 * If a GridModule has its action 'remove' disabled, then it doesn't display the 
 * button in the ribbon or the edit window. The actions are configured in the 
 * `module.actions` config option (yaml) of the module (see Opence's languages
 * module, for example).
 * 
 * 
 * ### Custom Actions
 * 
 * To add a _custom action_ accessible from the module's ribbon, two steps are needed.
 * 
 * 1.  First, configure the ribbon button and implement the action behaviour. This is
 *     best done by calling the {@link #addRibbonAction} method from the 
 *     {@link #initActions} overridden method.
 *     
 * 2.  Second, make the action available through the module's config. This is done in the
 *     `extra.toolbar` config key.
 *
 *
 * Edit module
 * -----------
 *
 * It is possible to use another module for editing. In this case, configuration which is
 * specific to record editing is not required for this module.
 *
 * In order to declare another module as taking responsability for editing jobs, use the
 * {@link #editModule} config option, or override the {@link #getEditModule} mtethod.
 *
 * *Note*: the `editModule` support if all but complete for now (notably completely lacking
 * to take record creation into consideration).
 * 
 */
Oce.GridModule = Ext.extend(Ext.util.Observable, {

	// TODO remove that, that is absolutely dangerous!!!
	spp: Ext.Panel.prototype
	
	/**
	 * @cfg {String} autoExpandColumn
	 * 
	 * {@link Ext.grid.Column#id} id of the column that will be set as 
	 * the grid's {@link Ext.grid.GridPanel#autoExpandColumn  autoExpandColumn}.
	 */
	,autoExpandColumn: undefined
	
	/**
	 * @type {Boolean}
	 * @readonly
	 * 
	 * True if this component fires an 'open' event. Read-only.
	 */
	,openEvent: true
	
	/**
	 * @type {Boolean}
	 * @readonly
	 * 
	 * True if this component is opened. Read-only.
	 */
	,opened: false
	
	/**
	 * @type {Object} 
	 * @private
	 * 
	 * Origin id string of the reloads that have already been processed. When a reload 
	 * is directly fired from the success callback of a saving method, the origin id 
	 * string is kept for a short time (as a key set to `true`in the `processedReloads` 
	 * object), in order to prevent externally fired events from triggering the 
	 * processing again. For example, a GridModule having a declared dependency on 
	 * another table will have its reload method called twice: once from the success 
	 * callback, and a second time from the Kepler watched event triggered by the table 
	 * it depends on.
	 */
	,processedReloads: undefined
	
	/**
	 * @cfg {Object} extra
	 * 
	 * @cfg {Object} winConfig
	 * @cfg {Object} editWinConfig
	 * @cfg {Object} addWinConfig
	 */

	/**
	 * Name of the module to be used for editing, if different from this module.
	 *
	 * @cfg {String} [editModule=undefined]
	 */

	,constructor: function(config) {

		// Initial hidden state
		Ext.each(this.columns, function(col) {
			col.initialHidden = !!col.hidden;
		});

		this.addAsyncConstructTask(this.onApplyPreferences, this);

		this.my = Ext.apply({
			selectAllText: "Tout sélectionner"
			,showAllColsText: "Toutes"
		}, this.my);
		
		// Init toolbar
		this.toolbarConfig = Ext.clone(this.extra.toolbar, true);

		Ext.apply(this, this.my);
		
		Ext.apply(this, config);

		this.editWindowAdapter = this.createEditWindowAdapter();
		
		this.sc = this.extra.shortcuts;

		this.reloadLatch = 0;
		this.activeAddWindows = {};
		this.editWindows = {};
		this.openActionHandlers = {};
		this.storeBaseParams = {};
		// reload vars
		this.processedReloads = {};
		this.createReloadTask();

		this.pageSize = this.extra.pageSize || 30;

		Oce.GridModule.superclass.constructor.apply(this, arguments);

		this.addEvents(
			'open', 
			'close',
			/**
			 * @event aftercreatewindow
			 * 
			 * Fires after a window has been created, and before the form 
			 * data are loaded (in the case of an edit window).
			 * 
			 * @param {Oce.GridModule} this
			 * 
			 * @param {Ext.Window} win The window that has been created.
			 * 
			 * @param {String} action The action provided by the window. 
			 * That can be either `'add'` or `'edit'`.
			 * 
			 * @param {String/Integer} recordId If the action was `'edit'`,
			 * then the id of the record being edited will passed as the
			 * third argument.
			 */
			'aftercreatewindow',
			/**
			 * @event beforegridstorefirstload
			 * 
			 * Fires before the main grid store is first loaded.
			 * 
			 * @param {Oce.GridModule} this
			 * @param {Ext.data.Store} store The main grid's store.
			 */
			'beforegridstorefirstload',
			/**
			 * @event ribboncreated
			 *
			 * Fires after the ribbon component (i.e. toolbar) is created.
			 *
			 * @param {Oce.GridModule} this
			 */
			'ribboncreated'
		);
		
		this.model.initRelations(this.modelRelations);
        
        // Column groups merge
        // 
        // lastItems are merged at the end of columnGroups.items
        // 
        var x = this.extra = this.extra || {},
            cg = x && x.columnGroups,
            cgi = cg && cg.items,
            lcgi = cg && cg.lastItems;
        if (lcgi) {
            cg = x.columnGroups = x.columnGroups || {};
            cgi = cg.items = (cgi || {});
            Ext.iterate(lcgi, function(name, items) {
                cgi[name] = items;
            });
        }
		
		// Init plugins
		// Must be done before initActions, to give plugins the opportunity to
		// add their own actions.
		this.initPlugins();

		this.initActions();
		this.afterInitActions();

		this.initMultisortPlugin();
		this.initFilterPlugin();

		this.initEditModule();

		Ext.iterate(this.extra, function(name, config) {
			var pg = Oce.GridModule.plugins[
				name.substr(0,1).toUpperCase() + name.substr(1)
			];
			if (pg) {
				this[name + "Plugin"] = new pg(this, config);
			}
		}, this);
	}
	
    // private
	,afterAsyncConstruct: function() {
		this.initConfiguration();
	}
	
	/**
	 * @protected
	 */
	,addAsyncConstructTask: function(fn, scope) {
		var stack = this.asyncConstructTasks = this.asyncConstructTasks || [];
		stack.push({
			fn: fn
			,scope: scope
		});
	}
	
	/**
	 * This method will be called when an instance is created, to allow for
	 * asynchronous operations.
	 */
	,doAsyncConstruct: function(callback, scope) {
		
		var me = this,
			stack = this.asyncConstructTasks;
		
		if (!stack) {
            this.afterAsyncConstruct();
			if (callback) {
				callback.call(scope); // this callback is from module manager
			}
		}
		
		else {
			var latch = stack.length;
			Ext.each(stack, function(task) {
				task.fn.call(task.scope || this, function() {
					if (--latch === 0) {
	                    me.afterAsyncConstruct();
						if (callback) {
							callback.call(scope); // this callback is from module manager
						}
					}
				});
			}, this);
		}
	}

	/**
	 * @return Eoze.GridModule.form.EditWindowAdapter
	 */
	,createEditWindowAdapter: function() {
		return Ext4.create('Eoze.GridModule.form.EditWindowAdapter', {
			module: this
		});
	}
	
	/**
	 * This method can be used to allow for multiple prefs contexts for the same module.
	 * @private
	 */
	,getPrefsKey: function(path) {
		var root = 'modules.' + this.name;
		if (path) {
			return root + '.' + path;
		} else {
			return root;
		}
	}
	
	// private
	,onApplyPreferences: function(cb, scope) {
		eo.app(function(app) {
			app.getPreferences(this.getPrefsKey(), function() {
				this.doApplyPreferences.apply(this, arguments);
				cb.call(scope)
			}, this);
		}, this);
	}
	
	// private
	,savePrefs: function(path, value) {
		this.prefsManager.set(this.getPrefsKey(path), value)
	}
	
	// private
	,doApplyPreferences: function(manager, prefs) {
		
		this.prefsManager = manager;
		
		// build map
		var map = {};
		Ext.each(this.columns, function(col) {
			map[col.name] = col;
		});
		
		// config
		var cols = prefs.columns;
		if (cols) {
			// apply prefs
			Ext.iterate(cols, function(name, config) {
				Ext.apply(map[name], config);
			});
		}
		
		// position
		this.columnPositions = prefs.columnPositions;
	}
	
	,getExtraModels: function(names, cb) {
		var xm = this.extraModels;
		var m = true;
		Ext.each(names, function(name) {
			// TODO actually launch the models loading process, calling cb when done
			if (!xm[name]) {
				throw new Error('Not implemented yet!');
				m = false;
				return false;
			}
		});
		// The callback must be returned only in case the method does not return
		// the requested models right away.
		if (m) {
			return xm;
		} else {
			return false;
		}
	}

	,hasDependance: function(name) {
		if (Ext.isString(name)) {
			var test = this[name];
			if (!test) {
				return false;
			} else if (Ext.isFunction(test)) {
				return test.call(this);
			} else {
				throw new Error(name + " is not a function");
			}
		} else if (Ext.isFunction(name)) {
			return name(this);
		} else {
			throw new Error('Unsupported dependance test');
		}
	}

	,getHelpTopic: function(index) {
		var help = this.extra.help;
		if (!(help && help.topics)) return false;
		var topics = help.topics;
		if (Ext.isString(topics)) {
			// this is a default all purpose topic
			return topics;
		} else if (!Ext.isObject(topics)) {
			throw new Error('IllegalArgument');
		}
		if (!index) index = "default";
		if (topics[index] === false) return false;
		else if (topics[index]) {
			return topics[index].replace(/^\$/, '#');
		}
		else if (topics['default']) return topics['default'];
		else return false;
	}

	,hasHelp: function(index) {
		return this.getHelpTopic() !== false && this.extra.help.factory;
	}

	,getHelpFactory: function() {
		if (!this.helpFactory) {
			var factory = Ext.namespace(this.extra.help.factory);
			if (!factory.view) throw new Error('Invalid Help Factory');
			else return this.helpFactory = factory;
		} else {
			return this.helpFactory;
		}
	}

	,viewHelp: function(topic) {
		if (/^#/.test(topic)) {
			var def = this.getHelpTopic();
			if (!/#/.test(def)) topic = def + topic;
		}
		this.getHelpFactory().view(topic);
	}

	,initPlugins: function() {
		var plugins = this.extra.plugins,
			pp = this.plugins = [];
		if (plugins) {
			Ext4.Object.each(plugins, function(index, config) {
				var ptype = Ext.isString(config) ? config : config.ptype,
					c = Oce.GridModule.ptypes[ptype],
					p = new c(config);
				pp.push(p);
			}, this);
			pp.forEach(function(p) {
				p.init(this);
			}, this);
		}
	}

	// TODO REM
	,initExtra: function() {
		throw new Error('DEPRECATED (use initPlugins)');
	}

//	,activeAddWindows: {}
//	,editWindows: {}
	
	,getFields: function() {throw 'Abstract'}

	,getTitle: function() {
		if (this.my.title) return this.my.title;
		else return this.name;
	}

	,destroy: function() {
		this.tab.hide();
		this.tab.destroy();
		delete this.toolbar;
		delete this.tab;
	}

	,initDefaults: function() {

		var defaults = {
			sortable: true,
//			width: 200,
			type: 'textfield',
			maxLength: 45,
			allowBlank: false
		};

		if (this.defaults === undefined) {
			return this.defaults = defaults;
		} else {
			return Ext.applyIf(this.defaults, defaults);
		}
	}

	,getGridColumnDefaults: function() {
		if (!this.gridColumnsDefaults) {
			return this.gridColumnsDefaults = this.initDefaults();
		} else {
			return this.gridColumnsDefaults;
		}
	}

	,beforeInitStore: function(storeConfig) {}

	,afterInitStore: function(store) {}

	,initStore: function() {

		this.store = this.store || {};

		if (!(this.store instanceof Ext.data.Store)) {

			var controller = this.controller;

			Ext.applyIf(this.store, {
				baseParams: Ext.apply({
					controller: controller
					,action:'load'
				}, this.storeBaseParams),
				url: 'api',
				root: 'data',
				idProperty: this.primaryKeyName,
				totalProperty: 'count',
				fields: this.storeColumns,
				remoteSort: true
			});
			
			delete this.storeBaseParams;
			
			this.beforeInitStore(this.store);

//			this.store = new Oce.MultipleSortJsonStore(this.store);
			this.store = this.createStore(this.store);

//			if (Ext.isArray(this.defaultSortColumn)) {
//				this.store.sort(this.defaultSortColumn);
//			} else {
				this.store.setDefaultSort(
					this.defaultSortColumn
					,this.defaultSortDirection
				);
//			}

			this.store.on('beforeload', function(store, options) {
				options.useRanges = true;
			});

			this.afterInitStore(this.store);
//			this.doFirstLoad();
		}
	}

	,createStore: function(config) {
		var s = new Oce.MultipleSortJsonStore(this.store);
		this.afterCreateGridStore(s);
		return s;
	}
	
	/**
	 * Hook method called after the main grid's {@link Ext.data.Store store} is created.
	 * (This method has some implementation in {@link Oce.GridModule}, so all children 
	 * modules should their parent method.)
	 * @param {Ext.data.Store} store
	 * @protected
	 */
	,afterCreateGridStore: function(store) {
		store.on('beforeload', function(store, opts) {
			var cm = this.grid.getColumnModel(),
				vci = [];
			
			for (var i=0,l=cm.getColumnCount(); i<l; i++) {
				 if (!cm.isHidden(i)) {
					 vci.push(i);
				 }
			}
			
			var dataIndexes = [];

			Ext.each(vci, function(i) {
				var di = cm.getDataIndex(i);
				if (di) {
					dataIndexes.push(di);
				}
			});
			
			// If no column is displayed, we don't want to reload.
			// Since internal columns are not displayed, but always here, we don't count them).
			if (!dataIndexes.length) {
				return false;
			}
			
			// internal columns
			Ext.each(this.columns, function(col) {
				if (col.internal) {
					dataIndexes.push(col.name);
				}
			});
			
			// put into params
			var p = opts.params || {};
			p.json_columns = Ext.encode(dataIndexes);
		}, this);
		
		// Update selection on store changes
		// onSelectionChange must not be called directly, because it expects
		// the selection model as argument
		var callSelectionChange = function() {
			var g = this.grid,
				sm = g && g.getSelectionModel();
			return this.onSelectionChange(sm);
		}
		store.on({
			scope: this
			,load: callSelectionChange
			,update: callSelectionChange
		});
	}

	/**
	 * Initiates action for editing (or viewing the specified record).
	 *
	 * This method also accepts params as documented for {@link #doEditRecord}.
	 *
	 * @param {Object/Eoze.GridModule.EditRecordOperation/Ext.data.Record/String} param
	 * @param {String/Ext.data.Record} param.record
	 * @param {Integer/String} [param.startTab]
	 * @param {Function} [param.callback] Callback triggered after loading.
	 * @param {Object} [param.scope]
	 * @param {Ext.Element} [param.sourceEl]
	 *
	 * @return {Eoze.GridModule.EditRecordOperation} operation
	 *
	 * @todo #gridmodule This should be moved to a controller.
	 */
	,editRecord: function(record, startTab, callback, scope, sourceEl) {
		var operation = Eoze.GridModule.EditRecordOperation.parseOperation(arguments);
		this.doEditRecord(operation);
		return operation;
	}

	/**
	 * Actual implementation of {@link #editRecord}. This method should be preferred for overriding,
	 * since all arguments preparation has been done beforehand.
	 *
	 * Only the record id is mandatory, all other arguments are optional and can be left blank. Even if
	 * `record` is supplied, `recordId` must be passed as the first argument.
	 *
	 * @param {Eoze.GridModule.EditRecordOperation} params
	 *
	 * @protected
	 *
	 * @todo #gridmodule This should be moved to a controller.
	 */
	,doEditRecord: function(operation) {

		var adapter = this.editWindowAdapter;

		this.getEditWindow(operation)

			.then({
				success: function() {
					adapter.showWindow(operation);
					adapter.loadWindow(operation);
				}
			})

			.otherwise(function() {
				debugger
			});
//			.otherwise(adapter.handleError, adapter); // TODO
	}

	,editRow: function(row) {

		var el,
			index = this.grid.store.indexOf(row),
			id = row.data[this.primaryKeyName];

		if (index >= 0) {
			var rc = this.grid.view.resolveCell(index,0);
			if (rc) {
				el = Ext.get(rc.cell);
			}
		}

		this.editRecord({
			record: row
			,sourceEl: el
		});
	}

	,editRecordLine: function(grid, rowIndex) {
		if (this.my.recordEditable !== false) {
			this.editRow(grid.store.getAt(rowIndex))
		}
	}

	/**
	 * @param {Object} config
	 *
	 * @template
	 * @protected
	 */
	,beforeCreateGrid: function(config) {}
	
	// private
	,onSelectionChange: function(selectionModel) {
		if (selectionModel.getCount() > 0) {
			var records = this.getSelectedRecords();
			Ext.each(this.selectionDependantItems, function(item) {
				if (item.onRecordSelection) {
					item.onRecordSelection.call(item.scope || item, item, records);
				} else {
					item.enable();
				}
			});
		} else {
			Ext.each(this.selectionDependantItems, function(item) {
				item.disable();
			});
		}
	}

	,afterCreateGrid: function(grid) {
		
		// Install selection listener
		grid.getSelectionModel().on({
			scope: this
			,selectionchange: this.onSelectionChange
		});
		
		// prefs
		grid.getColumnModel().on({
			scope: this
			,columnmoved: this.onUpdateColumnsPrefs
			,bulkhiddenchange: this.onUpdateColumnsPrefs
			,hiddenchange: this.onUpdateColumnsPrefs
		});
		
		// 28/02/12 19:13 Commented out if (e.fromOtherSession) { ... }
		//                Changed reload to reloadFrom(origin) in processExternallyModifiedRecords
		
		// Handle external changes
		grid.mon(eo.Kepler, this.tableName + ':modified', function(e, ids) {
//			if (e.fromOtherSession) {
				this.onRecordsExternallyModified(ids);
//			}
		}, this);
		grid.mon(eo.Kepler, this.tableName + ':removed', function(e, ids) {
//			if (e.fromOtherSession) {
				this.onRecordsExternallyDeleted(ids);
//			}
		}, this);
		grid.mon(eo.Kepler, this.tableName + ':created', function(e, id) {
//			if (e.fromOtherSession) {
				this.onRecordsExternallyCreated(id);
//			}
		}, this);
		
		if (this.externalGridDependencies) {
			this.addGridExternalDependencies(this.externalGridDependencies);
		}
	}
	
	/**
	 * Handler for updating column prefs.
	 * @private
	 */
	,onUpdateColumnsPrefs: function(cm) {
		var config = {},
			positions = [];
		Ext.each(cm.config, function(col) {
			var n = col.name;
			if (n) {
				positions.push(n);
				config[n] = {
					hidden: col.hidden
				};
			}
		});
		this.savePrefs('columns', config);
		this.savePrefs('columnPositions', {
			columns: positions
			,version: eo.getApplication().getOpenceVersion()
		});
	}
	
	/**
	 * @type {Array}
	 * @protected
	 * 
	 * Array of kepler event names to watch, because the grid needs to be
	 * reloaded when they happen. These events will be automatically added
	 * with {@link addGridExternalDependencies} each time a main grid is
	 * created.
	 * 
	 * Note that cares should be taken when overridding this property, since
	 * parent modules may use it. {@link Oce.GridModule} itself won't use
	 * it, so it is always safe *for the first direct child modules only*
	 * to override this property directly.
	 */
	,externalGridDependencies: undefined
	
	/**
	 * Adds a {@link eo.Kepler Kepler} event as a dependency of the main grid. 
	 * The grid will be reloaded each time one of this event happens. The session 
	 * where the event originated will not be taken into account (that is, the
	 * event will be processed even if it originates in the current session). The
	 * ids of the modified record will not be used to filter the events either.
	 *
	 * @protected
	 */
	,addGridExternalDependencies: function(keplerEvents) {
		if (!Ext.isArray(keplerEvents)) {
			this.addGridExternalDependencies([keplerEvents]);
		}
		
		else {
			var grid = this.grid;
			Ext.each(keplerEvents, function(event) {
				grid.mon(eo.Kepler, event, function(e, ids, origin) {
					this.onRecordsExternallyModified(ids, origin);
				}, this);
			}, this);
		}
	}
	
	/**
	 * @private
	 */
	,processExternallyModifiedRecords: function(ids, action, forceReload, origin) {
		
		// If the event has already been processed from somewhere else
		// (most notably, the onSuccess callback that directly triggered the reload)
		if (this.processedReloads[origin] === true) {
			return;
		}
		
		var s = this.grid.store;
		
		if (ids) {
			var i = ids.length,
				reload = !!forceReload,
				id, win;
				
			while (i--) {
				id = ids[i];
				win = this.editWindows[id];
				if (s.getById(ids[i])) {
					reload = true;
				}
				if (win) {
					if (this['onEditWindowExternally' + action]) {
						this['onEditWindowExternally' + action](win);
					}
				}
			}
			
			if (reload) {
				this.reloadFrom(origin);
			}
		}
		
		else {
			this.reloadFrom(origin);
		}
	}
	
	,onRecordsExternallyCreated: function(ids) {
		this.processExternallyModifiedRecords(false, 'Created', true);
	}
	
	,onRecordsExternallyDeleted: function(ids) {
		this.processExternallyModifiedRecords(ids, 'Deleted', true);
	}
	
	,onRecordsExternallyModified: function(ids, origin) {
		this.processExternallyModifiedRecords(ids, 'Modified', false, origin);
	}

	/**
	 * Behaviour to produce when a window is externally modified. In this
	 * context, externally means out of the actual window, not necessarily
	 * from another user or session.
	 * 
	 * If the window's form contains unsaved modifications, then a message
	 * will inform the user and prompt them if they want to reload the 
	 * window, else the window will be silently reloaded.
	 * 
	 * If no refresh method is available in the passed `win` object, then
	 * the window will be closed instead.
	 * 
	 * @param {eo.Window} window The window that is concerned.
	 * @param {Boolean} [external=true] `true` to specify in the information
	 * message that the modification came from another user/session.
	 *
	 * @protected
	 */
	,onEditWindowExternallyModified: function(win, external) {

		var actionMsg, okHandler;

		if (win.refresh) {

			// If the form is not dirty, reload without confirmation
			if (!win.formPanel.isModified()) {
				win.refresh(true);
				return;
			}

			else {
				actionMsg = "Les données vont être rechargées.";
				okHandler = function() {
					this.close();
					win.refresh(true);
				};
			}
		} else {
			actionMsg = "La fenêtre va être fermée.";
			okHandler = function() {
				this.close();
				win.close();
			}
		}

		NS.AlertWindow.show({

			modalTo: win
			,modalGroup: 'refresh'

			,title: 'Modification' + (external ? ' extérieure' : '')
			,message: "L'enregistrement a été modifié"
				+ (external ? ' par un autre utilisateur. ' : '. ')
				+ actionMsg

			,okHandler: okHandler
			,cancelHandler: function() {
				// Restore the normal warning on close behavior
				win.forceClosing = false;
				this.close();
			}
		});
	}

	,onEditWindowExternallyDeleted: function(win) {
		NS.AlertWindow.show({

			modalTo: win
			// Same group as modified, since a deleting overrides any modifications
			,modalGroup: 'refresh'

			,title: 'Modification extérieure'
			,message: "L'enregistrement a été supprimé par un autre utilisateur."
					+ " La fenêtre va être fermée."

			,okHandler: function() {
				this.close();
				win.close();
			}

			,cancelHandler: function() {
				// Restore the normal warning on close behavior
				win.forceClosing = false;
				this.close();
			}
		});
	}
	
	,initGrid: function() {

		var checkboxSel = this.gridColumns[0];
		
		var pagingToolbar = {
			xtype: 'paging'
			,pageSize: this.pageSize
			,store: this.store
			,params: {action:'load'}
			,displayInfo: true
			,displayMsg: 'Enregistrements {0} - {1} sur {2}'
			,emptyMsg: "Aucune données existantes"
		};

		var config = Ext.apply({
			store: this.store
			,id: 'oce-gridmodule-grid-' + this.name
			,columns: this.gridColumns
			,sm:checkboxSel
			,autoExpandColumn: this.autoExpandColumn
			,loadMask: true
			,columnLines:true
			,border : false
			,header : false
			,autoScroll : true
			,stripeRows : true
			,enableColumnHide: false

			,bbar: pagingToolbar
			,pagingToolbar: pagingToolbar

			,viewConfig: Ext.apply({
				// grid view config can be added here
			}, this.extra.gridView)

			,listeners: {
				rowdblclick: this.editRecordLine.createDelegate(this)
			}
		}, this.extra.grid);

		this.beforeCreateGrid(config);

		this.grid = new Ext.grid.GridPanel(config);
		this.grid.ownerGridModule = this;

		this.fireEvent('aftercreategrid', this, this.grid);
		this.afterCreateGrid(this.grid);

		return this.grid;
	}

	,saveEdit: function(win, onSuccess) {
		var me = this;
		this.beforeSaveEdit(win, function(stop, successCb, params) {
			if (false !== stop) {
				var action = !params ? 'mod' : {
					name: 'mod'
					,params: params
				};
				me.save.call(me, win, action, successCb || onSuccess);
			}
		});
	}

	,beforeSaveEdit: function(win, callback) {
		// nothing can be added here, children classes are not required to
		// call GridModule's super method
		callback(true);
	}

	,saveNew: function(win, callback, loadModelData) {
		this.beforeAddFormSave(win.form);
		var fn;
		if (Ext.isFunction(callback)) {
			var me = this;
			fn = function(newId, data) {
				me.afterAdded(newId, data);
				callback(newId, data);
			}
		} else {
			fn = this.afterAdded.createDelegate(this);
		}

		this.save.call(this, win, 'add', fn, loadModelData);
	}

	,afterAdded: function(newId) {
		if (this.extra.editAfterAdd) {
			this.editRecord({
				record: newId
				,startTab: this.extra.editAfterAddTab
			});
		}
	}
	
	,onFormSaveSuccess: function(form, data, options) {
		
		var win = options.win,
			onSuccess = options.onSuccess,
			loadModelData = options.loadModelData;

		// TODO refactor this out of here ---
		
		win.formPanel.modified = false;
		// The aftersave event must be fired *before* the win is closed
		// or it will be considered modified and will trigger a confirmation
		// dialog
		win.fireEvent('aftersave', win, data, options);
		
		// ---

		// Using reloadFrom in order to prevent multiple reloading originating 
		// from the same window savings (that might come through Kepler event
		// external dependencies).
		this.reloadFrom(this.makeKeplerOriginString(win));

		if (onSuccess) {
			var r;
			if (!loadModelData) {
				r = onSuccess(data.newId);
			} else {
				r = onSuccess(data.newId, data.data)
			}
			if (r !== false) {
				win.close();
			}
		} else {
			win.close();
		}

		var msg = Oce.pickFirst(data, ['message','messages','msg']);
		if (msg !== undefined) {
			Oce.Ajax.defaultSuccessMsgHandler(msg);
		}
	}
	
	,onFormSaveFailure: function(form, data, options) {

		var win = options.win;
		
		// TODO form submit failure
		// System erreur
		if (data) {
			this.processFormError(win, form, data, options);
		}

		// If no data is provided, that means that was
		// an infrastructural error
		else {
			// TODO handle error
			debugger
		}
	}
	
	/**
	 * This method is called when a system error occurs during saving of a
	 * record with the {#save method}. A system error means that the server
	 * has responded correctly but indicated in the response object that the
	 * request could not be processed successfuly.
	 * 
	 * @param {Ext.Window} win
	 * @param {eo.data.JsonForm} form
	 * @param {Object} data The data decoded from the server response.
	 * @param {Object} options The options passed to the form {eo.data.JsonForm#submit}
	 * method.
	 *
	 * @protected
	 */
	,processFormError: function(win, form, data, options) {

		if (options) {
			if (options.errorMessageDisplayed || options.ajaxOptions && options.ajaxOptions.errorMessageDisplayed) {
				return;
			} else {
				if (options.ajaxOptions) {
					options.ajaxOptions.errorMessageDisplayed = true;
				}
				options.errorMessageDisplayed = true;
			}
		}

		if (data.status && data.status >= 400 && data.status < 500) {
			data = Ext.decode(data.responseText);
		}

		var msg = data.errorMessage,
			errors = data.errors;

		if (errors) {
			debugger
		}
		
		if (msg) {
			NS.AlertWindow.show({

				modalTo: win
				,modalGroup: 'save'

				,title: 'Erreur'
				,message: msg

				,okHandler: function() {
					this.close();
				}
			});
		} else {
			// TODO error handling
			
			var message = "<p>L'enregistrement a échoué.</p>";

			message += "<p>Vous pouvez rapporter les informations suivantes"
				+ " au support technique pour aider à corriger cette erreur&nbsp;:</p>";

			message += '<ul style="margin-top: 1em; margin-left: 1em;">';
			if (data.requestId) {
				message += String.format('<li>Requête #{0}</li>', data.requestId);
			}
			if (data.timestamp !== null) {
				message += String.format('<li>Erreur #{0}</li>', data.timestamp);
			}
			message += '</ul>';

				
			NS.AlertWindow.show({
				modalTo: win
				,title: 'Erreur'
				,message: message
				,height: 160
				,okHandler: function() {
					this.close();
				}
			});
		}
	}
	
	/**
	 * @private
	 */
	,makeKeplerOriginString: function(win) {
		return Oce.mx.application.instanceId + '/' + win.id;
	}

	/**
	 * @param {Ext.Window} win The form window.
	 * 
	 * @param {String/Object} action The `action` of the request or, an object
	 * can be passed to provide extra params for the request, in the 
	 * form:
	 *     {
	 *	       action: actionName
	 *	       ,params: extraParams
	 *	   }
	 *	   
	 * @param {Function} onSuccess A function to be called when the save
	 * action is successfuly completed.
	 * 
	 * @param {Boolean} loadModelData=false `true` to request the server
	 * to include the new data of the saved record in the response.
	 * 
	 * @protected
	 */
	,save: function(win, action, onSuccess, loadModelData) {
		
		var form = win.getForm();
		var error = this.isFormValid(form, win);

		if (error === false) {
			Ext.MessageBox.alert("Erreur", 'Certains champs ne sont pas correctement remplis.'); // i18n
		} else if (Ext.isString(error)) {
			Ext.MessageBox.alert("Erreur", error); // i18n
		} else {
			var extraParams;
			
			if (Ext.isObject(action)) {
				extraParams = action.params;
				action = action.name;
			}
			
			var params = Ext.apply({
				controller: this.controller
				,action: action
				,keplerOrigin: this.makeKeplerOriginString(win)
			}, extraParams);

			if (loadModelData) {
				params.dataInResponse = true;
			}
			
			var opts = {
				url: 'api'
				
				// post process data
				,win: win
				,onSuccess: onSuccess
				,loadModelData: loadModelData
				
				// request param
				,jsonData: params
				
				,waitTitle: 'Interrogation du serveur' // i18n
				,waitMsg: 'Enregistrement en cours' // i18n

				,scope: this
				,success: this.onFormSaveSuccess
				,failure: this.onFormSaveFailure
//				,failure: function(form, action) {
//					Oce.Ajax.handleFormError(
//						form,
//						action,
//						me.isForceErrorMessage(win.formPanel, action)
//					);
//				}
			};

			this.fireEvent('beforeformsubmit', form, opts);
			this.beforeFormSubmit(form, opts);
			
			form.on({
				single: true
				,scope: this
				
				,beforesave: function() {
					if (win.mask) {
						win.mask('Enregistrement', 'x-mask-loading');
					} else {
						var el = win.el,
							maskEl = el && el.query('.x-window-mc');
						if (maskEl) {
							Ext.get(maskEl).mask('Enregistrement', 'x-mask-loading');
						}
					}
				}
				
				,aftersave: function() {
					if (win.unmask) {
						win.unmask();
					} else {
						var el = win.el,
							maskEl = el && el.query('.x-window-mc');
						if (maskEl) {
							Ext.get(maskEl).unmask();
						}
					}
				}
			});

			form.submit(opts);
//			this.submitForm(form, opts);
		}
	}

	// protected
	,isFormValid: function(form, win) {
		return form.isValid()
	}
	
//	,submitForm: function(form, opts) {
//		if (this.submitMethod === 'json') {
//			var values = form.getFieldValues();
//			opts.params[form.jsonFormParam || 'json_form'] = Ext.encode(values);
//			var success = opts.success,
//				failure = opts.failure;
//			delete opts.success;
//			delete opts.failure;
//			opts.callback = function(options, succeeded, response) {
//				var action = {
//					result: Ext.decode(response.responseText)
//				};
//				if (!succeeded) {
//					failure(form, options);
//				} else {
//					if (action.result.success) {
//						success(form, action);
//					} else {
//						failure(form, action);
//					}
//				}
//				
//			};
//			Ext.Ajax.request(opts);
//		} else {
//			form.submit(opts);
//		}
//	}
	
	,toggleFieldValue: function(recordId, name) {
		var g = this.grid,
			ge = g && g.el;
		if (ge) {
			ge.mask("Enregistrement", "x-mask-loading");
		}
		Oce.Ajax.request({
			params: {
				controller: this.controller
				,action: "toggleFieldValue"
				,id: recordId
				,name: name
			}
			,onSuccess: function(data) {
				var s = g.store,
					rec = s.getById(data[s.idProperty]),
					v = data[name];
				if (rec) {
					rec.data[name] = eo.bool(data[name]);
					rec.commit();
				}
			}
			,onComplete: function() {
				if (ge) ge.unmask();
			}
		});
	}

	// TODO finish (or fix?) doc block
	/**
	 * Decides if an error message dialog must be displayed for the given action
	 * and formPanel. The resolution goes as follow:
	 * - if a config option is set, either module.forceErrorMessage,
	 * or module.forceErrorMessage[action], then this setting is respected
	 * - else, if formPanel seems to contains multiple tabs, then yes
	 * - else no
	 */
	,isForceErrorMessage: function(formPanel, action) {
		if (this.my.forceErrorMessage !== undefined) {
			if (this.my.forceErrorMessage === true) {
				return true;
			} else if (action !== undefined) {
				var fema = this.my.forceErrorMessage[action];
				if (fema !== undefined) return fema !== false;
			}
		}
		formPanel.items.each(function(item) {
			if (item instanceof Ext.TabPanel) return true;
			return undefined;
		});
		return false;
	}

	,beforeFormSubmit: function(form, options) {}

	,addRecord: function(callback, loadModelData, initValues) {
		var win = this.createAddWindow(callback);
		
		//
		this.activeAddWindows[win.getId()] = win;

		win.on('destroy', function() {
			delete this.activeAddWindows[win.getId()]
		}.createDelegate(this));

		//
		if (loadModelData) win.loadModelData = true;

		if (initValues) {
			// Some types of field will set their value after they're rendered.....
			win.on('afterrender', function() {
				Ext.iterate(initValues, function(fieldName, val) {
					var field = win.form.findField(fieldName);
					if (field) {
						field.setValue(val);
					}
				});
			}, this, {single: true});
		}

		var tb = this.getToolbar(false),
			addButton = tb && tb.getItemForAction("add");
		win.show(addButton ? addButton.btnEl : undefined);
//		// init widgets waiting for init value
//		window.form.reset();
//		window.form.clearInvalid();
	}

	,beforeAddFormSave: function(form) {}

	,getSelectedRowsData: function() {
		var data = [];
		this.checkboxSel.each(function(reccord) {
			data.push(reccord.data);
		}, this)
		return data;
	}
	
	/**
	 * Gets the currently selected records in the main grid.
	 * @return {Ext.data.Record[]}
	 */
	,getSelectedRecords: function() {
		var records = [];
		this.checkboxSel.each(function(reccord) {
			records.push(reccord);
		}, this)
		return records;
	}

	,getSelectedRowsId: function() {
		var ids = [];
		this.checkboxSel.each(function(reccord) {
			ids.push(reccord.data[this.primaryKeyName]);
		}, this)
		return ids;
	}

	,deleteSelectedRecords: function() {
		var ids = this.getSelectedRowsId();
		if (ids.length > 0) {
			this.deleteRecord(ids);
		}
	}

	,deleteRecord: function(ids, callback, confirm, ajaxOpts) {

		if (!Ext.isArray(ids)) ids = [ids];

		var me = this;

		var fn = function() {
			var grid = me.grid;
			if (grid && grid.el) {
				grid.el.mask("Suppression en cours", "x-mask-loading");
			}
			Oce.Ajax.request(Ext.apply({
				params: {
					json_ids: encodeURIComponent(Ext.util.JSON.encode(ids))
					,controller: me.controller
					,action: 'delete'
				}
				,onSuccess: function() {
					if (grid && grid.el) {
						grid.el.unmask();
					}
					if (callback) {
						callback.call(me);
					}
					me.reload();
					me.afterDelete(ids);
				}
				,onFailure: function() {
					if (grid && grid.el) grid.el.unmask();
				}
			}, ajaxOpts))
		};

		if (confirm !== false) {
			Ext.MessageBox.confirm('Veuillez confirmer',
				'Vous êtes sur le point de supprimer ' + ids.length +
					' enregistrement' + (ids.length > 1 ? 's' : '') +
					'. Êtes-vous sûr de vouloir continuer ?', // i18n
				function(btn){
					if (btn == 'yes') {
						fn();
					}
				}
			);
		} else {
			fn();
		}
	}
	
	,afterDelete: function() {}

	,deleteRecords: function() {
		this.deleteRecord.apply(this, arguments);
	}
	
	,addWindowToolbarAddExtra: function(btnGroups, getWindowFn, handlers) {

	}

	,editWindowToolbarAddExtra: function(btnGroups, getWindowFn, handlers) {

		var deleteHandler = function() {
			Ext.MessageBox.confirm('Veuillez confirmer',
				'Êtes-vous sûr de vouloir supprimer cet enregistrement ?',
				function(btn){
					if (btn == 'yes') {
						var win = getWindowFn();
						win.forceClosing = true;
						win.close();
						this.deleteRecord(win.idValue, null, false);
					}
				}.createDelegate(this)
			);
		}.createDelegate(this)

		// Toolbar
		var tbarItems = [];
		
		if (this.hasAction('remove')) {
			tbarItems.push({
				text: 'Supprimer'
				,iconCls: 'ico_bin'
				,handler: deleteHandler
			});
		}

		if (this.my.edit !== undefined && this.my.edit.tbar  !== undefined) {
			tbarItems.concat(this.my.edit.tbar);
		}

		btnGroups.edit = {
			 xtype: 'buttongroup'
			,anchor: 'left'
			,items: tbarItems
		}
	}

	/**
	 * opts:
	 * - monitorFormModification (default FALSE): if set to TRUE, the cancel
	 * button will initially have the text "Fermer" and the save button will
	 * be disabled. On the first modification of the window's form, the save
	 * button will be enabled, and the cancel button will have its text set to
	 * "Annuler".
	 */
	// see createEditWindowToolbar before modifying this
	,createFormWindowToolbar: function(getWindowFn, handlers, addExtraFn, opts) {

		var saveButton, cancelButton;
		
		if (opts && opts.saveWithoutCloseButton) {
			saveButton = new Ext.SplitButton({
				 text: "Enregistrer"
				 ,iconCls: "ico_disk"
				 ,isSaveButton: true
				 ,handler: function() {
					 handlers.save();
				 }
				 ,menu: [{
					text: "Enregistrer sans fermer"
					,handler: function() {
						handlers.save(eo.falseFn);
					}
				 }]
			 });
		} else {
			saveButton = new Ext.Button({
				 text: "Enregistrer"
				 ,iconCls: "ico_disk"
				 ,isSaveButton: true
				 ,handler: function() {
					 handlers.save();
				 }
			 });
		}

		var btnGroups = {
			base: {
				 xtype: "buttongroup"
				,anchor: "right"
				,items: [
					 saveButton
					 ,cancelButton = new Ext.Button({
						 text: "Annuler"
						 ,iconCls: "ico_cross"
						 ,isCloseButton: true
						 ,handler: handlers.forceClose
						 ,hidden: true
					 })
				]
			}
		};

		if (opts && opts.monitorFormModification) {

			saveButton.disable();
			cancelButton.setText("Fermer");

			handlers.onFormPanelCreated(function(formPanel) {
				formPanel.on({
					modified: function() {
						saveButton.enable();
						cancelButton.setText("Annuler");
					}
					,modificationcleared: function() {
						saveButton.disable();
						cancelButton.setText("Fermer");
					}
				})
			});
		}

		if (addExtraFn) {
			addExtraFn.call(this, btnGroups, getWindowFn, handlers);
		}

		// === Help Group ===
		btnGroups.right = "->";
		var items = [];

		if (this.hasHelp()) {
			items.push({
//				text: "help1",
				iconCls: "fugico_question-frame"
				,tooltip: "Aide" // i18n
				,handler: this.viewHelp.createDelegate(this, [this.getHelpTopic("add")]) // TODO help
			});
			var contextHelpButton = new Ext.Button({
//				text: "help2",
				iconCls: "fugico_question-balloon"
				,enableToggle: true
				,hidden: true
				,tooltip: "Aide contextuelle" // i18n
			});
			items.push(contextHelpButton);
		}

		var enableContextHelp;
		if (items.length) {
			btnGroups.help = {
				xtype: "buttongroup"
				,items: items
			};
			enableContextHelp = function(override) {
				Ext.apply(contextHelpButton, override);
				contextHelpButton.show();
			}
		} else {
			enableContextHelp = function(){}
		}
		
		var r = [];
		Ext.iterate(btnGroups, function(n, config) {
			r.push(config);
		})
		
		var bar = new Ext.Toolbar({
			xtype: "toolbar"
			,items: r
			,enableContextHelp: enableContextHelp
		});
		
		bar.saveButton = saveButton;
		bar.cancelButton = cancelButton;

		return bar;
	}

	,createFormWindowHandlers: function(opts) {

		if (!opts.saveFn) {
			throw new Error('Missing required option: safeFn');
		}

		var saveFn = opts.saveFn;

		var me = this
			,handlers

			,reloadStore = this.reload.createDelegate(this)

			// because the window will be undefined during its creation...
			// (must be wrapped in another fn)
			,closeWindow = function() {handlers.win.close()}.createDelegate(this)

			;

		var formPanelCreationListeners = [];

		return handlers = {
			getWindowFn: function() {return handlers.win;}
			,reload: reloadStore
			,close: closeWindow
			,forceClose: function() {
				var win = handlers.getWindowFn();
				if (win.forceClose) win.forceClose();
				else win.close();
			}
			,save: function(onSuccess) {
				saveFn.call(me, handlers.win, onSuccess);
			}
			,onFormPanelCreated: function(fn) {
				formPanelCreationListeners.push(fn);
			}
			,notifyFormPanelCreation: function(formPanel) {
				formPanel.save = handlers.save;
				Ext.each(formPanelCreationListeners, function(l) {
					l(formPanel);
				});
				handlers.onFormPanelCreated = function(fn) {fn(formPanel);};
			}
		};
	}

	,createFormWindow: function(formConfig, winConfig, saveFn, toolbarAddExtraFn, tbarOpts) {

		var handlers = this.createFormWindowHandlers({
			saveFn: saveFn
		});

		var tbar = this.createFormWindowToolbar(handlers.getWindowFn, handlers, toolbarAddExtraFn, tbarOpts);
		if (tbar instanceof Ext.Component == false) tbar = Ext.widget(tbar);

		var kit = new Oce.GridModule.ContentKit({

			pkName: this.primaryKeyName
			,controller: this.controller

			,content: Ext.apply({
// 04/12/11 07:07
// Removed the next line because it triggerred double submit, with eo.Window's own submitHandler
//				submitHandler: handlers.save,
				controller: this.controller
				,autoScroll: true
				,xtype: 'oce.form'
			}, formConfig)

			,winConfig: Ext.apply({
				 title: "Enregistrement" // i18n
				,layout: this.my.editWinLayout
				,tbar: tbar
				,actionToolbar: tbar
				,tools: [this.createEditWindowGearTool(tbar, winConfig)]
				,submitButton: tbar.saveButton
				,unlockSaveButton: function() {
					if (tbar.saveButton) {
						tbar.saveButton.enable();
					}
				}
			}, winConfig)
			
		});

		kit.on('formpanelcreate', function(formPanel) {
			formPanel.on('beforeload', function(fp, form, options) {
				this.beforeFormLoad(form, options);
			}, this);
			formPanel.on('afterload', function(form, data, formPanel) {
				if (handlers.win.refreshTitle) {
					handlers.win.refreshTitle(data.data);
				}
				this.afterFormLoad.apply(this, arguments);
			}, this);
			handlers.notifyFormPanelCreation(formPanel);
		}, this)

		return handlers.win = kit.getWin();
	}
	
	,createEditWindowGearTool: function(tbar, winCfg) {
		var gearMenu = new Ext.menu.Menu({
			id: winCfg.id ? winCfg.id + "-gear-menu" : Ext.id('gear-menu')
			,items: [{
				text: "Dévérouiller le bouton Enregistrer"
				,cls: "unlock-save-button"
				,handler: function() {
					if (tbar.saveButton) {
						tbar.saveButton.enable();
					}
				}
			}]
		});
		return {
			id: "gear"
			,handler: tbar ? function(event, el, panel) {
				gearMenu.show(el);
			} : undefined
		};
	}

	/**
	 * Hook method that is called when a form load has been triggered,
	 * before it actually occurs.
	 * 
	 * @param {Ext.form.BasicForm} form The form that is to be loaded.
	 * @param {Object} options The options object that will be passed
	 * to the {@link Ext.form.BasicForm#load load} method of the form.
	 */
	,beforeFormLoad: function(form, options) {}
	
	,afterFormLoad: function(form, data, formPanel) {}
	,onConfigureAddFormPanel: function(formConfig) {}

	/**
	 * Prepares this module to be used with an {@link #editModule external editing module}.
	 *
	 * This method is called during the initialization phase of the module, and removes functionalities
	 * that are specific to record editing. This allow modules that do not handle editing to provide
	 * partial configuration.
	 *
	 * @private
	 */
	,initEditModule: function() {
		if (this.editModule || this.extra.editModule) {
			Ext.apply(this, {
				buildFormsConfig: Ext.emptyFn
			});
		}
	}

	/**
	 * Get the instance of the module to be used for editing. If editing is not done by another module,
	 * then this method will return `false`. In the contrary, the method will return a promise that
	 * will resolve with the edit module's own {@link #getEditWindow} promise.
	 *
	 * This option can be set in configuration with {@link #editModule}.
	 *
	 * @params {Eoze.GridModule.EditRecordOperation} operation
	 * @return {Deft.Promise|false}
	 * @protected
	 */
	,getEditModule: function(operation) {
		var editModule = this.editModule || this.extra.editModule;

		// Using another edit module
		if (editModule) {
			if (Ext.isString(editModule)) {
				var deferred = Ext4.create('Deft.Deferred');
				Oce.getModule(editModule, function(module) {
					module.getEditWindow(operation).then({
						scope: deferred
						,update: deferred.update
						,success: deferred.resolve
						,failure: deferred.reject
					});
				});
				return deferred.getPromise();
			} else {
				return editModule.getEditWindow(operation);
			}
		}

		// Not using another edit module
		else {
			return false;
		}
	}

	/**
	 * @version 2011-12-08 21:03 Added opts
	 * @version 2013-03-19 14:26 Removed opts
	 * @version 2013-03-19 16:20 Removed callback
	 *
	 * @params {Eoze.GridModule.EditRecordOperation} operation
	 * @return {Deft.Promise} Promise of a {@link Ext.Window}.
	 *
	 * @protected
	 */
	,getEditWindow: function(operation) {

		var editModulePromise = this.getEditModule(operation);

		if (editModulePromise) {
			return editModulePromise;
		}

		var deferred = Ext4.create('Deft.Deferred'),
			recordId = operation.getRecordId(),
			editWindows = this.editWindows;

		// Test for already existing window
		var existingWindow = recordId && editWindows[recordId];

		// Already opened window
		if (existingWindow) {
			operation.setWindow(existingWindow, true);
			deferred.resolve(existingWindow);
		}

		// New window
		else {
			this.createEditWindow(operation).then({
				scope: this
				,success: function(win) {

					// Operation
					operation.setWindow(win);

					// Instance lookup
					editWindows[recordId] = win;

					win.on({
						close: function() {
							editWindows[recordId].destroy();
							delete editWindows[recordId];

							// Notify operation
							operation.notifyClosed();
						}
					});

					// Events
					this.fireEvent('aftercreatewindow', this, win, 'edit', recordId, operation);
					this.afterCreateWindow(win, 'edit', recordId, operation);
					this.afterCreateEditWindow(win, recordId, operation);

					// Promise
					deferred.resolve(win);
				}
			});
		}

		return deferred.promise;
	}

	/**
	 * Hook method called before a window is created.
	 * 
	 * @param {Object} config The configuration of the window
	 * to be created.
	 * 
	 * @param {String} action The action provided by the window. 
	 * That can be either `'add'` or `'edit'`.
	 * 
	 * @param {String/Integer} recordId If the action was `'edit'`,
	 * then the id of the record being edited will passed as the
	 * third argument.
	 * 
	 * @protected
	 */
	,beforeCreateWindow: function(config, action, recordId) {}
	
	/**
	 * Hook method called before an edit window is created.
	 * 
	 * @param {Object} config The configuration of the window
	 * to be created.
	 * 
	 * @param {String/Integer} recordId The id (primary key value)
	 * of the record being edited.
	 * 
	 * @protected
	 * 
	 */
	,beforeCreateEditWindow: function(config, recordId) {}
	
	/**
	 * Hook method called before an add window is created.
	 * 
	 * @param {Object} config The configuration of the window
	 * to be created.
	 * 
	 * @protected
	 * 
	 */
	,beforeCreateAddWindow: function(config) {}
	
	/**
	 * Hook method called after a window has been created, and
	 * before the form data are loaded (in the case of an edit
	 * window).
	 * 
	 * @param {Ext.Window} win The window that has been created.
	 * 
	 * @param {String} action The action provided by the window. 
	 * That can be either `'add'` or `'edit'`.
	 * 
	 * @param {String/Integer} recordId If the action was `'edit'`,
	 * then the id of the record being edited will passed as the
	 * third argument.
	 *
	 * @param {Eoze.GridModule.EditRecordOperation} operation
	 * 
	 * @protected
	 */
	,afterCreateWindow: function(win, action, recordId, operation) {
		var me = this;
		win.getKeplerOriginString = function() {
			return me.makeKeplerOriginString(win);
		};			
	}
	
	/**
	 * Hook method called after an add window has been created.
	 * 
	 * @param {Ext.Window} win The window that has been created.
	 * 
	 * @protected
	 */
	,afterCreateAddWindow: function(win) {}

	/**
	 * Hook method called after a window has been created, and
	 * before the form data are loaded.
	 * 
	 * @param {Ext.Window} win The window that has been created.
	 * 
	 * @param {String/Integer} recordId If the action was `'edit'`,
	 * then the id of the record being edited will passed as the
	 * third argument.
	 *
	 * @param {Eoze.GridModule.EditRecordOperation} operation
	 * 
	 * @protected
	 */
	,afterCreateEditWindow: function(win, recordId, operation) {

		this.parseContextHelpItems(win);

		// Change event
		var event = String.format('{0}#{1}:modified', this.modelName, recordId);
		
		win.mon(eo.Kepler, event, function(e, origin) {
			var instanceId = Oce.mx.application.instanceId;
			if (origin !== instanceId + '/' + win.id) {
				var matches = /^([^/]+)\//.exec(Oce.mx.application.instanceId),
					external = matches && matches[0] === instanceId;
				origin.substr(0, Oce.mx.application.instanceId.length)
				this.onEditWindowExternallyModified(win, external)
			}
		}, this);

		// Route
		var route = Eoze.AjaxRouter.Router.getRoute(this.name + '.open')
				|| Eoze.AjaxRouter.Router.getRoute(this.name + '.edit');
		if (route) {
			win.href = route.assemble({
				id: recordId
			});

			// Tab panel
			var tabPanel = win.formPanel.items.get(0);
			if (tabPanel instanceof Ext.TabPanel) {
				tabPanel.on('tabchange', function(tabPanel, item) {
					win.href = route.assemble({
						id: recordId
						,tab: item.slug || item.tabName
					});
					Eoze.AjaxRouter.Router.setActivePage(win);
				});
			}
		}
	}

	,parseContextHelpItems: function(win) {
		if (!this.hasHelp()) return;
		var tbar = win.actionToolbar;
		if (!tbar || !tbar.enableContextHelp) return;

		var helpItems = [];
		var walkChildren = function(ct) {
			ct.items.each(function(item) {
				var topic = item.helpTopic;
				if (topic) {
					helpItems.push({
						cmp: item
						,topic: topic
					});
				}
				if (item instanceof Ext.Container && item.items) {
					walkChildren(item);
				}
			});
		}

		walkChildren(win.formPanel);

		/*
		 * Wraps context help toggle button handler to avoid collisions with
		 * properties and methods of Ext.Button (we just want to override its
		 * toggleHandler method), and also saving the multiple creation of the
		 * prototypical logic.
		 */
		var ContextHelpHandler = function(formPanel, helpItems, module) {
			this.zones = null;
			this.rootPanel = formPanel;
			this.helpItems = helpItems;
			this.module = module;
		};
		
		/*
		 * Creates a wrapper for a new ContextHelpHandler, to be applied to the
		 * Ext.Button toggle button to override its toggleHandler method.
		 */
		ContextHelpHandler.createWrapper = function(formPanel, helpItems, module) {
			var h = new ContextHelpHandler(formPanel, helpItems, module);
			return {
				toggleHandler: h.toggleHandler.createDelegate(h)
			}
		};
		ContextHelpHandler.prototype = {

			showTopic: function(topic) {
				this.clearZones();
				this.module.viewHelp(topic);
			}
			,clearZones: function() {
				if (this.zones) Ext.each(this.zones, function(zone) {
					zone.remove();
				});
				this.zones = null;
				this.button.toggle(false);
			}
			,mask: function(cmp, cls) {
				var createMask = function(el) {
					if (!(/^body/i.test(el.dom.tagName) && el.getStyle('position') == 'static')) {
						el.addClass("x-masked-relative");
					}
					var mask = el.createChild({cls:"context-help-mask"});
					if (mask) {
						// from ext source
						// ie will not expand full height automatically
						if (Ext.isIE && !(Ext.isIE7 && Ext.isStrict) && el.getStyle('height') == 'auto') {
							mask.setSize(undefined, el.getHeight());
						}
						mask.on('unload', function() {
							el.removeClass(["x-masked", "x-masked-relative"]);
						});
					}
					return mask;
				};

				var el = cmp instanceof Ext.Element ? cmp : cmp.el;
				var mask = createMask(el);
				if (!mask) {
					var rx = RegExp.prototype.compile(el.id + '$');
					var parent = el.parent;
					while (parent && rx.test(parent.id)) {
						if ((mask = createMask(parent))) {
							break;
						} else {
							el = parent;
							parent = parent.parent;
						}
					}
				}

				if (!mask) return;

				this.zones.push(mask);
				el.addClass('x-masked');
			}
			,toggleHandler: function(button, state) {

				this.button = button;

				if (!state) {
					this.clearZones();
					return;
				} else if (this.zones) {
					// extra safety...
					return;
				}

				var fp = this.rootPanel;

				var currentPanel;
				fp.items.each(function(item) {
					if (item instanceof Ext.Container) {
						if (item instanceof Ext.TabPanel) {
							currentPanel = item.getActiveTab();
						} else {
							var layout = item.getLayout();
							if (layout instanceof Ext.CardLayout) {
								currentPanel = item.activeItem;
							}
						}
					}
				});
				if (!currentPanel) currentPanel = fp;
				var fpBody = currentPanel.body;

				//var mask = fpBody.createChild({cls: "context-help-zone"});
				var zones = this.zones = [];
				this.mask(currentPanel);

				var me = this;
				Ext.each(this.helpItems, function(helpItem) {
					var cmp = helpItem.cmp;
					if (!cmp.isVisible()) return;
					var zone = fpBody.createChild({
						tag:'a',
						cls:'context-help-zone'
					});
					var el = cmp.el;
					zone.anchorTo(el, 'tl-tl');
					zone.setSize(el.getWidth(), el.getHeight());
					zone.on('click', me.showTopic.createDelegate(me, [helpItem.topic]));
					zones.push(zone);
				});
			} // toggleHandler
		}; // ContextHelpHandler.prototype

		if (helpItems.length > 0) {
			tbar.enableContextHelp(ContextHelpHandler.createWrapper(
				win.formPanel, helpItems, this
			));
		}
	}
	
	/**
	 * Gets the title of the module in its singular form.
	 * @return {String}
	 */
	,getSingularTitle: function() {
		var title = this.title;
		if (!this.hasOwnProperty('singularTitle')) {
			if (title.substr(-1) === 's') {
				this.singularTitle = title.substr(0, title.length - 1);
			} else {
				this.singularTitle = title;
			}
		}
		return this.singularTitle;
	}
	
	/**
	 * Builds the title for the edit window from the given data.
	 *
	 * @param {Object} data The data from the record, or as loaded into
	 * the form.
	 *
	 * @protected
	 */
	,buildEditWindowTitle: function(data) {
		var format = this.editWindowTitleFormat || this.extra.editWindowTitleFormat,
			re = /%([^%]+)%/,
			matches,
			value;
		if (format) {
			while ((matches = re.exec(format))) {
				value = data[matches[1]];
				if (!Ext.isDefined(value)) {
					return null;
				}
				format = format.replace(matches[0], value);
			}
			return format;
		}
	}

	/**
	 * Actually creates the edit window. This method can be overriden to
	 * modify the creation of the window, but it should be called only internaly
	 * by the GridModule. Any other use should use the getEditWindow, which
	 * takes keeps track of already opened edit windows, to avoid opening twice
	 * the same record for edit
	 *
	 * @version 2013-03-19 15:33 Replaced arguments with single operation param.
	 *
	 * @param {Eoze.GridModule.EditRecordOperation} [operation]
	 * @return {Deft.Promise}
	 */
	,createEditWindow: function(operation) {

		var recordId = operation.getRecordId(),
			me = this;
			
		var winConfig = Ext.apply({}, this.applyExtraWinConfig('edit', {
			title: this.editWindowTitle || (this.getSingularTitle() + " : Modifier") // i18n
			,id: this.editWinId(recordId)
			,layout: this.my.editWinLayout
			,refreshable: true
			,refreshTitle: function(data) {
				var title = me.buildEditWindowTitle(data);
				if (title) {
					this.setTitle(title);
				}
			}
		}));

		// Try to get record title data from the store
		var s = this.store,
			record = s && s.getById(recordId);
		if (record) {
			winConfig.title = this.buildEditWindowTitle(record.data) || winConfig.title;
		}
		
		this.beforeCreateWindow(winConfig, 'edit', recordId);
		this.beforeCreateEditWindow(winConfig, recordId);

		var win = this.createFormWindow(
			this.getEditFormConfig(),
			winConfig,
			this.saveEdit,
			this.editWindowToolbarAddExtra.createDelegate(this),
			{
				monitorFormModification: true
				,saveWithoutCloseButton: true
			}
		);

		return Deft.Promise.when(win);
	}
	
	,applyExtraWinConfig: function(action, config) {
		var cache = this.extraWinConfigCache = this.extraWinConfigCache || {};
		if (!cache[action]) {
			var extra = this.extra;
			if (!this.extra) {
				cache[action] = {};
				return config;
			}
			var winConfig = extra.winConfig;
			if (!winConfig) {
				cache[action] = {};
				return config;
			}
			var actionConfig = extra[action + 'WinConfig'];

			cache[action] = Ext.apply(Ext.apply({}, winConfig), actionConfig);
		}
		return Ext.apply(config, cache[action]);
	}

	,getEditFormConfig: function() {
		return this.my.editFormConfig;
	}

	/**
	 * Creates the toolbar to be used by the edit window. This method is not
	 * used internaly, it is intended as a helper for overriding methods.
	 * @param handlers
	 * @param opts see createFormWindowToolbar for accepted options
	 */
	,createEditWindowToolbar: function(handlers, opts) {
		return this.createFormWindowToolbar(
			handlers.getWindowFn,
			handlers,
			this.editWindowToolbarAddExtra.createDelegate(this),
			Ext.apply({
				monitorFormModification: true
				,saveWithoutCloseButton: true
			}, opts)
		);
	}

	,nextFreeAddWinId: 1
	,nextAddWinId: function() {
		return String.format("eo-{0}-add-win-{1}", this.my.name, this.nextFreeAddWinId++);
	}

	,editWinId: function(recordId) {
		return String.format("eo-{0}-edit-win-{1}", this.my.name, recordId);
	}

	,createAddWindow: function(callback) {

		var formConfig = Ext.apply({}, this.getAddFormConfig());
		this.onConfigureAddFormPanel(formConfig);

		var winConfig = Ext.apply({}, this.applyExtraWinConfig('add', {
			 title: this.addWindowTitle
					 || this.extra.addWindowTitle
					 || (this.getSingularTitle() + " : Nouveau") // i18n
			,layout: this.my.addWinLayout
			,id: this.nextAddWinId()
		}));

		this.beforeCreateWindow(winConfig, "add");
		this.beforeCreateAddWindow(winConfig);

		var win = this.createFormWindow(
			formConfig
			,winConfig
			,function(win) {
				this.saveNew.call(this, win, callback, win.loadModelData);
			}, this.addWindowToolbarAddExtra.createDelegate(this)
		);
			
		this.fireEvent('aftercreatewindow', this, win, 'add');
		this.afterCreateWindow(win, 'add');
		this.afterCreateAddWindow(win);

		return win;
	}

	/**
	 * protected method
	 *
	 * Gets the configuration of the Oce.FormPanel for the ADD action.
	 */
	,getAddFormConfig: function() {
		return this.my.addFormConfig;
	}

	,getVisibleColumnsNames: function() {

		var visibleCols = this.grid.getColumnModel().getColumnsBy(function(c){return !c.hidden;});

		// Remove the 'checker' first columns
		visibleCols.shift();

		var names = new Array();

		for (var i=0; i<visibleCols.length; i++) {
			names.push(visibleCols[i].name);
		}

		return names;
	}

	,ajaxFileRequest: function(params, opts) {
		
		var me = this,
			win;

		opts = opts || {};

		var doRequest = function() {
			
			var requestParams = Ext.applyIf(
				Ext.apply({
					controller: me.controller
				}, params)
			);

			// Retrieve values from options window
			if (win) {
                var values = win.form.getFieldValues();
				if (opts.jsonOptions) {
					requestParams.json_options = encodeURIComponent(Ext.util.JSON.encode(values));
				} else {
					Ext.iterate(values, function(k,v) {
						requestParams[k] = v;
					});
				}
			}

			var onSuccess = function(data, response) {
				waitBox.hide();
				//location.href = data.url;
				if (win) win.close();
				if (true || Ext.isIE6 || Ext.isIE7) {
					Ext.Msg.alert(
						'Fichier',
						String.format(
							"Le fichier est disponible à l'adresse suivante : "
							+ '<a href="{0}" target="_blank">{0}</a>.', 
							response.url
						)
					);
				} else {
					var dlFrame = Ext.DomHelper.append(document.body, {
						tag: 'iframe',
						frameBorder: 0,
						width: 0,
						height: 0,
						css: 'display:none;visibility:hidden;height:1px;',
						src: response.url
					});
					setTimeout(function() {
						Ext.fly(dlFrame).remove();
					}, 100);
//				} else {
//					window.open(response.url);
				}
			};

			if (opts.onSuccess) {
				onSuccess = Ext.Function.createSequence(onSuccess, opts.onSuccess, opts.scope);
				delete opts.onSuccess;
			}
			
			var waitBox = Ext.Msg.wait('Création du fichier');

			Oce.Ajax.request(Ext.applyIf({
				params: requestParams
//				,waitTitle: 'Veuillez patienter' // i18n
//				,waitMsg: 'Fichier en cours de création...' // i18n
//				,waitTarget: win ? win : undefined
				,onSuccess: onSuccess
			}), opts);
		}

		if (opts) {
			if (opts.window) {
				win = opts.window;
				win.okHandler = doRequest;
			} else if (opts.form) {

				var form = !Ext.isArray(opts.form) ? opts.form : {
					xtype: 'oce.form'
					,items: opts.form
				};

				win = new Oce.FormWindow(Ext.apply({
					title: opts.winTitle || 'Options'
					,width: 320
					,formPanel: form
					,submitButton: 0
					,cancelButton: 1
					,buttons: [
						{text: 'Ok', handler: doRequest} // i18n
						,{text: 'Annuler', handler: function() {win.close()}} // i18n
					]
				}, opts.winConfig));
			}
		}

		if (win) {
			win.show();
		} else {
			doRequest();
		}
	}

	,exportData: function(format) {

		switch (format) {
			case 'xls': 
			case 'pdf':
			case 'csv':
			case 'ods':
				break;
			default:Ext.MessageBox.alert('Format Invalide', "Désolé, ce format de "
				+ "fichier n'est pas pris en charge. Il s'agit vraisemblablement "
				+ "d'une erreur du programme, veuillez contactez l'assistance technique.");
		}

		var names = this.getVisibleColumnsNames();

		var fields = {}

		Ext.each(this.columns, function(col) {
			for (var i=0,l=names.length; i<l; i++) {
				if (col.name === names[i]) {
					fields[col.name] = col.header;
					names.splice(i,1);
				}
			}
		}, this)

		var exportParams = Ext.apply({},
			this.grid.store.baseParams
		);
		Ext.apply(exportParams, this.getLastGridLoadParams());
		Ext.apply(exportParams, {
			controller:this.controller
			,action:'export'
			,format:format
		});

		if (exportParams.json) {
			exportParams.json.fields = fields;
		} else {
			exportParams.json = {'fields': fields};
		}
		exportParams.json = encodeURIComponent(Ext.util.JSON.encode(exportParams.json));

		this.ajaxFileRequest(exportParams);
	}
	
	/**
	 * @return {Object}
	 * @protected
	 */
	,getLastGridLoadParams: function() {
		var s = this.grid.store,
			o = Ext.apply({}, s.lastOptions.params);
		Ext.apply(o, s.baseParams);
		delete o.controller;
		delete o.action;
		return o;
	}

	,getToolbar: function(createIf) {
		if (!this.toolbar) {
			
			if (!createIf) {
				return undefined;
			}
			
			var leftItems = [],
				rightItems = [];
			
			Ext.iterate(this.toolbarConfig, function(label, menuItems) {
				
				// Disabled group
				if (menuItems === false) {
					return;
				}
				
				var name = label,
					align = 'left',
					stick = false;
				
				// Support for new syntax
				if (menuItems.items) {
					if (menuItems.label) {
						name = label;
						label = menuItems.label;
					}
					stick = !!menuItems.stick;
					align = menuItems.align || align;
					menuItems = menuItems.items;
				}
				
				label = label.replace(/\%title\%/, this.getTitle());
				
				var groupItems = [];
				Ext.each(menuItems, function(item) {
					if (item === '-') {
						groupItems.push(new Ext.Toolbar.Separator());
					} else {
						if (Ext.isString(item)) {
							item = this.actions[item];
							if (!item) {
								// the item has been disabled in the config
								return;
							}
						} else if (Ext.isObject(item)) {
							var itemItem = item.item;
							if (!itemItem) {
								throw new Error('Invalid toolbar item config');
							}
							if (Ext.isString(itemItem)) {
								itemItem = this.actions[itemItem];
							}
							if (false == itemItem instanceof Ext.Component) {
								item = Ext.apply({}, item, itemItem);
							}
						}
						// DEBUG INFO: dying here often means that the module
						// actions have not been initialized correctly
						if (item.depends && !this.hasDependance(item.depends)) {
							return;
						}
						groupItems.push(item);
					}
				}, this);
				
				if (groupItems.length) {
					var alignGroup,
						groupConfig = {
							xtype: 'buttongroup',
							title: label,
							align: 'bottom',
							items: groupItems
						};

					(align === 'right' ? rightItems : leftItems).push(groupConfig);
				}
			}, this);
			
			var items = [];
			if (leftItems.length) {
				items = items.concat(leftItems);
			}
			if (rightItems.length) {
				items = items.concat(['->'], rightItems);
			}
			
			this.beforeCreateToolbar(items);

			this.toolbar = new Ext.Toolbar({
				items: items
				,forbidHiddenLayout: true
			});

			this.toolbar.getItemForAction = function(actionId) {
				var items = this.findBy(function(item){
					if (item.actionId === actionId) return true;
					return undefined;
				});
				if (items.length) return items[0];
				return undefined;
			};

			this.fireEvent('ribboncreated', this, this.toolbar);
		}

		return this.toolbar;
	}
	
	,beforeCreateToolbar: function(items) {}
	
	,buildGridColumnsConfig: function() {
		
		var defaults = this.getGridColumnDefaults(),
			columns = this.columns,
			renderers = this.renderers;
		
		var columnConfigMap = {},
			initialColumns = [];
		// copy initial columns to initialColumns
		Ext.each(this.gridColumns, function(col) {
			initialColumns.push(col);
		});

		var col;
		for (var i=0,l=columns.length; i<l; i++) {

			col = columns[i];

			// Primary
			if (col.primary) {
				this.primaryKeyName = col.name;
			} else {
				col.primary = false;
			}

			Ext.applyIf(col, {
				dataIndex: col.name,
				sortable: defaults.sortable,
				width: defaults.width
			});

			if (Ext.isString(col.renderer)) {
				col.renderer = renderers[col.renderer];
			}
			if (((col.formField && col.formField.xtype === "htmleditor")
					|| (col.type && col.type === "htmleditor"))
					&& !col.renderHtml) {
				var renderer = function(v) {
					if (!v) return v;
					v = v.replace(/<[^>]*\bstyle\b[^>]*>[\s\S]*?<\/style>/gm, '');
					var tmp = document.createElement("DIV");
					tmp.innerHTML = v;
					v = tmp.textContent||tmp.innerText||"";
					if (v.length > 150) return v.substr(0,150) + '...';
					else return v;
				}
				if (col.renderer) {
					var prevColRenderer = col.renderer;
					col.renderer = function(v) {
						return prevColRenderer(renderer(v));
					}
				} else {
					col.renderer = renderer;
				}
			}

			// --- Grid ---

			if (col.grid !== false && col.internal !== true) {
			
				// Auto expand
				if (col.autoExpand) {
					if (!col.id) {
						col.id = col.dataIndex;
					}
					this.autoExpandColumn = col.id;
				}

				this.gridColumns.push(col);
				columnConfigMap[col.name] = col;
			}

			// --- Grid Store
			this.storeColumns.push(Ext.apply({
				name: col.dataIndex || col.name
			}, col.store));
		}

		// --- User Preference: positions of the columns

		var columnPositions = this.columnPositions;
		if (columnPositions && columnPositions.version === eo.getApplication().getOpenceVersion()) {
			var positions = columnPositions && columnPositions.columns;
			if (positions) {
				var ordered = initialColumns;
				Ext.each(positions, function(name) {
					var col = columnConfigMap[name];
					if (col) {
						ordered.push(columnConfigMap[name]);
					} else {
						eo.warn("Missing configuration for column: " + name);
					}
				});
				this.gridColumns = ordered;
			}
		}
	}

//	,buildStoreColumnsConfig: function() {
//		Ext.each(this.gridColumns, function(col) {
//			if (col.dataIndex) { // exclude plugin columns
//				this.storeColumns.push({name: col.dataIndex});
//			}
//		}, this);
//	}

	/**
	 * Must call afterBuildFormsConfig()
	 */
	,buildFormsConfig: function() {

		var defaults = this.getGridColumnDefaults();
		
		var addConfig = new NS.FormConfig(this, 'add', defaults),
			editConfig = new NS.FormConfig(this, 'edit', defaults);

		if (this.forms) {
			// TODO This is not used (nor, probably, processed correctly).
			// This must be fixed or removed.
			Ext.iterate(this.forms, function(name, config) {
				var formFields = new NS.FormConfig(this, name);
				// Events
				this.onFormItemsConfig(formFields.fields);
				if (formFields.action) {
					switch (formFields.action) {
						case 'add':this.onAddFormItemsConfig(formFields.fields);break;
						case 'edit':this.onEditFormItemsConfig(formFields.fields);break;
					}
				}
			}, this);
		}

		this.afterBuildFormsConfig(addConfig.fields, editConfig.fields);

		// Edit & Add forms
		var addTabFormItems = null, editTabFormItems = null;

		if ('tabs' in this) {
			if ('add' in this.tabs) {
				addTabFormItems = this.makeTabPanel(this.tabs.add, addConfig);
				this.my.addWinLayout = 'fit';
			}
			if ('edit' in this.tabs) {
				editTabFormItems = this.makeTabPanel(this.tabs.edit, editConfig);
				this.my.editWinLayout = 'fit';
			}
		} else {
			// 27/12/11 10:53
			// Restored old values ('fit' was breaking autosizing of all other windows,
			// eg. agencies, contracts...)
			this.my.addWinLayout = addConfig.winLayout;
			this.my.editWinLayout = editConfig.winLayout;
			// 19/12/11 20:29
			// Changed default window layout to fit because SMInstanceProducView
			// add window was broken (content not fitting in the win).
//			this.my.addWinLayout = 'fit';
//			this.my.editWinLayout = 'fit';
		}

		//... Edit form
		if (editTabFormItems !== null) {
			if (editTabFormItems.xtype && editTabFormItems.xtype === 'oce.form') {
				this.my.editFormConfig = editTabFormItems;
			} else {
				this.my.editFormConfig = {
					 xtype: 'oce.form'
					,jsonFormParam: 'json_form'
					,padding: 0
					,items: editTabFormItems
				};
			}
		} else {
			this.my.editFormConfig = Ext.apply(editConfig.onConfigureForm({
				xtype: 'oce.form'
				,jsonFormParam: 'json_form'
				,defaults: {
					anchor: '100%'
				}
				,items: editConfig.fields
			}), this.extra.formConfig);
		}

		//... Add form
		if (addTabFormItems !== null) {
			if (addTabFormItems.xtype && addTabFormItems.xtype === 'oce.form') {
				this.my.addFormConfig = addTabFormItems;
			} else {
				this.my.addFormConfig = {
					 xtype: 'oce.form'
					,jsonFormParam: 'json_form'
					,bodyStyle: 'background:transparent'
					,padding: 0
					,items: addTabFormItems
				}
			}
		} else {
			this.my.addFormConfig = addConfig.onConfigureForm({
				xtype: 'oce.form'
				,defaults: {
					anchor: '100%'
				}
				,jsonFormParam: 'json_form'
				,items: addConfig.fields
			});
		}

		// Form size
		var w = this.extra.windowWidth, h = this.extra.windowHeight;
		if (w) {
			if (Ext.isObject(w)) {
				if (w.add) this.my.addFormConfig.width = w.add;
				if (w.edit) this.my.editFormConfig.width = w.edit;
			} else {
				this.my.addFormConfig.width = this.my.editFormConfig.width = w;
			}
		}
		if (h) {
			if (Ext.isObject(h)) {
				if (h.add) this.my.addFormConfig.height = h.add;
				if (h.edit) this.my.editFormConfig.height = h.edit;
			} else {
				this.my.addFormConfig.height = this.my.editFormConfig.height = h;
			}
		}
	}

	,afterBuildFormsConfig: function(addConfigFields, editConfigFields) {

		// --- Form configuration events
		if (addConfigFields) {
			this.onFormItemsConfig(addConfigFields);
			this.onAddFormItemsConfig(addConfigFields);
		}
		if (editConfigFields) {
			this.onFormItemsConfig(editConfigFields);
			this.onEditFormItemsConfig(editConfigFields);
		}
	}

	,initConfiguration: function() {
		// --- Grid ------------------------------------------------------------

		// Columns ---

		if (!Ext.isDefined(this.gridColumns)) {

			this.checkboxSel = new Ext.grid.CheckboxSelectionModel();
			this.gridColumns = [this.checkboxSel];
			this.storeColumns = [this.checkboxSel];

			if (!this.primaryKeyName) this.primaryKeyName = 'id';

			this.buildGridColumnsConfig();
			this.buildFormsConfig();

			// --- Store ---

			if (this.gridColumns.length > 1) {
				Ext.applyIf(this, {
					defaultSortColumn: this.extra.defaultSortColumn || this.gridColumns[1].dataIndex
					,defaultSortDirection: this.extra.defaultSortDirection || 'ASC'
				});
			}

			this.initStore();
		}
	}
	
	,create: function(config) {

		var me = this;
		
		this.initGrid();
		this.afterInitGrid();

		// --- Main Panel ----------------------------------------------------------

		var tabPanelConfig = Ext.apply({
            
            module: this
            ,getActiveModule: function() {
                return this.module;
            }
            
			,title: this.my.title
			,header: false
			,closable : true
			,layout: 'fit'
			,cls: this.my.name
	//		,
	//		bbar: true
			,items: [this.grid]
			
			,listeners: {
				scope: this
				,close: function() {
					if (tab !== null) {
						this.destroy();
					}
					if (this.opened) {
						this.opened = false;
						this.fireEvent("close", this);
					}
				}
				,activate: function() {
					tab.doLayout();
				}
			}
		}, config);

		this.beforeCreateTabPanel(tabPanelConfig);

		var tab = new Ext.Panel(tabPanelConfig);

		// give the tab interface a chance to finish layout before launching
		// this request, which processing will be slightly blocking
		(function() {me.doFirstLoad();}).defer(500);
		
		this.afterCreateTabPanel(tab);

		return tab;
	}

	/**
	 * @return {Eoze.AjaxRouter.Router.Route}
	 */
	,getRoute: function() {
		return Eoze.AjaxRouter.Router.getRoute(this.name + '.index')
			|| Eoze.AjaxRouter.Router.getRoute(this.name);
	}

	,beforeCreateTabPanel: function(config) {
		// toolbar plugin
		if (this.toolbarConfig !== false) {
			config.tbar = this.getToolbar(true);
		}
		// route
		var route = this.getRoute();
		if (route) {
			config.href = route.assemble();
		}
	}
	
	// private
	,afterCreateTabPanel: function(tabPanel) {
		
		// Clear previous list
		var l = this.selectionDependantItems = [];
		
		// Walk top toolbar items (recursively)
		var tbar = tabPanel.getTopToolbar();
		if (tbar) {
			
			var walk = function(item) {
				if (item.items) {
					item.items.each(walk);
				}
				if (item.dependsOnSelection) {
					item.disable();
					l.push(item);
				}
			};
			
			tbar.items.each(walk);
		}
	}

	,afterInitGrid: function() {}

	,onFormItemsConfig: function(items){}
	,onEditFormItemsConfig: function(items){
		Ext.each(items, function(field) {
			// A first load latch must be added because the grid field load
			// will be triggered twice on edit: one time by the setRowId on the
			// field, and another time by the refresh of the form panel (to load
			// its form).
			if (field instanceof Oce.form.GridField
					|| field.xtype === 'gridfield') {

				Oce.form.LoadLatch.addFirstTo(field);
			}
		});
	}
	,onAddFormItemsConfig: function(items){}

	,makeTabPanel: function(tabItems, formConfig) {

		var tabConfig = tabItems;
		tabItems = tabConfig.items;

		var tabPanelItems = [],
			firstFormTabItems = null,
			tabBuilder = new NS.TabBuilder(formConfig);

		var applyFormTabConfig = function(tab, tabConfig) {
			delete tabConfig.items;
			Ext.apply(tab, tabConfig);
		};

		var iterateTabItems = function (tabItems) {
			var rTabItems = [];

			Ext.iterate(tabItems, function(tabName, tabConfig) {

				var tab = null;

				if (Ext.isArray(tabConfig)) {
					// Form tab, shortcut form
					tab = tabBuilder.makeFormTab(tabName, tabConfig);
					if (firstFormTabItems === null) firstFormTabItems = tab.items;
				} else if (Ext.isObject(tabConfig)) {
					if (tabConfig.xtype) {
						tab = Ext.applyIf(tabConfig, {
							tabName: tabName.toLowerCase()
						});
					} else if (tabConfig.page !== undefined) {
						// This is an html page tab
						tab = Ext.applyIf(tabConfig, {
							xtype: 'oce.wintpl'
							,title: tabName
							,tabName: tabName.toLowerCase()
						});
					} else if (tabConfig.items !== undefined) {
						// This is a form tab
						tab = tabBuilder.makeFormTab(tabName, tabConfig.items);
						if (firstFormTabItems === null) firstFormTabItems = tab.items;
						applyFormTabConfig(tab, tabConfig);
					}
				}

				if (tab !== null) {
					// tabPanelItems.push(tab);
					rTabItems.push(tab);
				} else {
					throw new Error('Invalid tab configuration');
				}
			}, this);

			return rTabItems;
		}.createDelegate(this);

		if (tabConfig.groupTabs) {
//			Ext.iterate(tabItems, function(groupTabName, groupTabConfig) {
			Ext.each(tabItems, function(groupTabConfig) {
				var items = groupTabConfig.items,
					config;
				if (items) {
					items = iterateTabItems(items);
					config = Ext.apply({}, groupTabConfig);
					delete config.items;
				} else {
					items = iterateTabItems(groupTabConfig);
				}
				tabPanelItems.push(Ext.apply({
					expanded: true

//					,deferredRender:false
					,hideMode:'offsets'
//					,autoScroll: true
					,style: {
						'overflow-x': 'hidden',
						'overflow-y': 'auto'
					}

					,defaults:{
						 layout:'form'
						,autoScroll: true
						,defaultType:'textfield'
						,bodyStyle:'padding:10px;  background:transparent;'
					}

					,items: items
				}, config));
			});
		} else {
			tabPanelItems.push(iterateTabItems(tabItems));
		}

		// Add hidden fields to first tab
		if (firstFormTabItems === null) {
			Ext.each(formConfig.fields, function(field) {
				if (field.xtype == 'hidden' && !tabBuilder.wasItemAdded(field.name)) {
					throw new Error('Missing form tab...');
				}
			});
		} else {
			Ext.each(formConfig.fields, function(field) {
				if (field.xtype == 'hidden' && !tabBuilder.wasItemAdded(field.name)) {
					firstFormTabItems.push(field);
				}
			}, this);
		}

		if (tabConfig.groupTabs) {
			return [{
				 xtype: Ext.isString(tabConfig.groupTabs) ? tabConfig.groupTabs : 'grouptabpanel'

				,tabWidth: 130
				
				,activeGroup: tabConfig.activeGroup || 0

				,width: tabConfig.windowWidth
				,height: tabConfig.windowHeight

				// Is this necessary with GroupTab ??
				// this line is necessary for anchoring to work at
				// lower level containers and for full height of tabs
				,anchor:'100% 100%'

				,items: tabPanelItems
			}]
		} else {
			if (tabPanelItems[0].length == 1) {
				return {
					xtype: 'panel'
					,border: false

					,width: tabConfig.windowWidth
					,height: tabConfig.windowHeight

					,bodyStyle: 'background:transparent;'

					,layout: 'fit'
					,anchor:'100% 100%'

					,defaults:{
						 layout:'form'
						,autoScroll: true
						,defaultType:'textfield'
						,bodyStyle:'padding:10px;  background:transparent;'
					}
					
					,items: Ext.apply(tabPanelItems[0][0], {
						header: false
						,border: false
					})
				};
			}
			return [{
				 xtype:'tabpanel'
				,activeTab: 0 // fix it if the first tab doesn't display on opening
				,border:false
				,tabPosition: 'bottom'

				,width: tabConfig.windowWidth
				,height: tabConfig.windowHeight
	
				// this line is necessary for anchoring to work at
				// lower level containers and for full height of tabs
				,anchor:'100% 100%'

				// only fields from an active tab are submitted
				// if the following line is not present
				,deferredRender:false

				,bodyStyle:'background:transparent;'

				,defaults:{
					 layout:'form'
					,autoScroll: true
					,defaultType:'textfield'
					,bodyStyle:'padding:10px; background:transparent;'
					// as we use deferredRender:false we mustn't
					// render tabs into display:none containers
					,hideMode:'offsets'
				}

				,items: tabPanelItems
			}]
		}
	}

	,initFilterPlugin: function() {
		if (this.extra.filters && Ext.isObject(this.extra.filters)) {
			var filterItems = [];
			Ext.iterate(this.extra.filters, function(name, text) {
				if (Ext.isObject(text)) {
					filterItems.push(Ext.apply({}, text, {filterName: name}))
				} else if (name === 'spacer' || name === "-") {
					filterItems.push("-");
				} else {
					filterItems.push({
						filterName: name
						,text: text
					});
				}
			})
			// apply default filters
			var activeFilters = [],
				lastFilters = {},
				allActive = true;
			Ext.each(filterItems, function(f) {
				var n = f.filterName;
				lastFilters[n] = f.checked;
				if (f.checked) {
					activeFilters.push(n);
				} else {
					allActive = false;
				}
			});
			lastFilters.all = allActive;
			this.storeBaseParams.json_filters = encodeURIComponent(Ext.encode(activeFilters));
			// create menu (needs the last filters)
			var me = this;
			this.actions.filter = {
				xtype: 'oce.rbbutton'
				,text: 'Filtres'
				,iconCls: 'b_ico_filter'
				// The menu must be rebuilt for each new button
				,initComponent: function() {
					this.menu = me.createFilterMenu(filterItems, lastFilters);
					Ext.ComponentMgr.types[this.xtype].prototype.initComponent.apply(this, arguments);
				}
			};
		}
	}

	,createFilterMenu: function(filters, lastFilters) {

		var me = this
			,blockCheckHandler = false
			;

		var checkAllItem = new Ext.menu.CheckItem({
			 text:'Tous' // i18n
			,checked:!(this.checkIndexes instanceof Array)
			,hideOnClick:false
			,filterName: 'all'
			,handler:function(item) {
				var checked = !item.checked;
				blockCheckHandler = true;
				item.parentMenu.items.each(function(i) {
					if(item !== i && i.setChecked && !i.disabled && !i.isOption) {
						i.setChecked(checked);
					}
				});
				blockCheckHandler = false;
			}
		});

		lastFilters = Ext.apply({}, lastFilters);
		var searchHandler = function(menu) {

			if (!me.store) return;

			var changed = false;
			menu.items.each(function(item) {
				if (item.filterName) {
					if (item.checked !== lastFilters[item.filterName]) {
						changed = true;
						lastFilters[item.filterName] = item.checked;
					}
				}
			});

			if (changed) {
				var activeFilters = [];
				Ext.iterate(lastFilters, function(k,v) {
					if (v) activeFilters.push(k);
				});
				me.store.baseParams.json_filters = encodeURIComponent(Ext.encode(activeFilters));
				me.reload();
			}
		};

		var checkHandler = function(menuItem) {
			var menu = menuItem.parentMenu;
			if (!menu) return;
			if (blockCheckHandler) return;
			var all = true;
			menu.items.each(function(item) {
				if (item.filterName && item !== checkAllItem && !item.checked
					&& !item.isOption) all = false;
			});
			checkAllItem.setChecked(all);
		};

		var items = [
			checkAllItem
			,'-'
		];

		Ext.each(filters, function(f) {
			if (filters === "-") {
				items.push(filters);
			} else {
				items.push(Ext.applyIf(f, {
					checked: true
					,hideOnClick: false
					,checkHandler: checkHandler
				}))
			}
		});

		items.push('-');
		items.push({
			 text: 'Appliquer'
			,iconCls : 'refresh'
			
			,scope: this
			,handler: function(menuItem) {
				var menu = menuItem.parentMenu;
				if (menu) {
					searchHandler.call(this, menu);
					menu.hide();
				}
			}
		});

		var menu = new Ext.menu.Menu({
			items: items
			,plugins: new Ext.ux.menu.TooltipPlugin
			,listeners: {
				scope: this
				,beforehide: searchHandler
			}
		});
		
		checkHandler.call(this, {
			parentMenu: menu
		});
		if (this.store) {
			searchHandler.call(this, menu);
		} else {
			this.afterInitStore = Ext.Function.createSequence(this.afterInitStore, function() {
				searchHandler.call(me, menu);
			});
		}
		
		return menu;
	}

	,getDefaultSortColumn: function(grid) {
		var cm = grid.getColumnModel();
		if (cm.getColumnCount() > 1) {
			return cm.getColumnAt(1).dataIndex;
		}
		throw 'Grid with 0 columns are not supported';
		// TODO rx handle 0 columns grids...
	}

	,initMultisortPlugin: function() {
		if (this.extra.multisort) {
			this.afterInitStore = Ext.Function.createSequence(this.afterInitStore, this.doInitMultisortPlugin, this);
		}
	}

	/**
	 * @private
	 */
	,doInitMultisortPlugin: function(store) {
		this.beforeCreateGrid = Ext.Function.createSequence(this.beforeCreateGrid, function(config) {
			var me = this,
				defaultSort = this.defaultSortColumn;

			var tbar = Ext.create('Eoze.GridModule.multisort.Toolbar', {
				getDefaultSortParams: Ext.bind(function() {
					return [
						defaultSort || me.getDefaultSortColumn(me.grid),
						me.defaultSortDirection || 'ASC'
					];
				}, this)
			});

			if (Ext.isArray(defaultSort)) {
				tbar.clearSort = function() {
					this.hasMultiSort = false;

					Ext.each(this.findByType('button'), function(button) {
						if (button.reorderable) {
							this.remove(button);
						}
					}, this);

					Ext.each(defaultSort, function(sort) {
						var field,
							dir = 'ASC';
						if (Ext.isObject(sort)) {
							field = sort.field;
							dir = sort.dir || sort.direction || dir;
						} else {
							field = sort;
						}
						tbar.addSortField(field, dir, true);
//						tbar.configureStoreSort();
					});

					this.doSort();
				};
			}

			config.tbar = tbar;

			this.on({
				single: true
				,scope: this
				,aftercreategrid: function(me, grid) {
					tbar.bindGrid(grid);

					// Initial multiple sort
					if (Ext.isArray(defaultSort)) {
						Ext.each(defaultSort, function(sort) {
							var field,
								dir = 'ASC';
							if (Ext.isObject(sort)) {
								field = sort.field;
								dir = sort.dir || sort.direction || dir;
							} else {
								field = sort;
							}
							tbar.addSortField(field, dir, true);
							tbar.configureStoreSort();
						});
					}
				}
			})
		}, this);
	}

	,open: function(destination, config, action) {
		
		this.opening = true;
		
		if (!destination) {
			destination = Oce.mx.application.getMainDestination();
		}

		if (this.tab && this.tab.isDestroyed) {
			this.destroy();
		}

		if (!this.tab) {
			this.tab = this.create(config);
			destination.add(this.tab);
		}
		
		this.tab.show();

		// 06/09/12 20:31 Removed that, seems highly useless:
		// if (this.toolbar) {
		// 	this.toolbar.doLayout();
		// }
		
		this.beforeFinishOpen(function() {
			
			this.opening = false;
			this.opened = true;
			this.fireEvent("open", this);

			if (action) {
				Ext.iterate(action, function (k, v) {
					var fn = this.openActionHandlers[k];
					if (fn) fn.call(this, v);
				}, this);
			}
		});
	}
	
	,beforeFinishOpen: function(cb) {
		cb.call(this);
	}
	
	,moduleActions: {
		open: function(cb, scope, args) {
			this.on({
				single: true
				,open: cb
				,scope: scope
			});
			return this.open.apply(this, args);
		}
		,addRecord: function(cb, scope, args) {
			this.addRecord.apply(this, args);
			cb.call(scope || this, this);
		}
	}
	
	/**
	 * Registers a new module action.
	 * 
	 * The implementing function will be executed in the scope of this module.
	 * 
	 * @param {String} name Identifier of the module action.
	 * @param {Function} fn The function implementing the action.
	 */
	,addModuleAction: function(name, fn) {
		this.moduleActions = Ext.apply({}, this.moduleActions);
		this.moduleActions[name] = fn;
	}
	
	,executeAction: function(name, callback, scope, args) {
		if (Ext.isObject(name)) {
			callback = name.callback || name.fn;
			scope = name.scope || this;
			args = name.args;
			name = name.action || name.name;
		}
		return this.moduleActions[name].call(this, callback, scope, args);
	}
	
	,hasAction: function(name) {
		return !!this.actions[name];
	}
	
	,getAction: function(name) {
		return this.actions[name].createDelegate(this);
	}
	
	,firstLoad: false
	
	/**
	 * Hook method that is called before the main grid's store is first loaded.
	 * 
	 * @param {Ext.data.Store} store The main grid's store.
	 *
	 * @protected
	 */
	,beforeGridStoreFirstLoad: function(store) {}

	,doFirstLoad: function() {
		if (!this.firstLoad) {
			var store = this.store;
			
			this.fireEvent('beforegridstorefirstload', this, store);
			this.beforeGridStoreFirstLoad(store);
			
			store.load({
				params: {start: 0, limit: this.pageSize, action:'load'}
// --- EXPERIMENTS ---
//				,callback: function(records, opts, success) {
//					var params = opts.params,
//						start = params.realstart || 0,
//						limit = params.limit || 10,
//						lr = start + limit;
//					if (lr < store.getTotalCount()) {
//						store.load({
//							params: {start: 0, realstart: start+limit, limit: 500, action:"load"}
//							,add: true
//							,callback: arguments.callee
//						});
//					}
//				}
//				,callback: function() {
//					Oce.Ajax.request({
//						params: {controller: controller, action: "load", start: 0, limit: 10000}
//						,onSuccess: function(data, obj) {
////							debugger
//							store.proxy = new eo.data.CacheProxy((obj));
//						}
//					})
//				}
			});
			this.firstLoad = true;
		}
	}

	,addReloadLatch: function() {
		this.reloadLatch++;
	}

	,releaseReloadLatch: function() {
		this.reloadLatch--;
	}
	
	/**
	 * Triggers a reload and temporarily (for 10s) disable the
	 * {#processExternallyModifiedRecords} method for the given origin. This
	 * method is designed to prevent externally occuring events from triggering
	 * the same processing as the one directly called from the success callback
	 * of a window saving.
	 */
	,reloadFrom: function(origin, callback, scope) {
		var pr = this.processedReloads,
			prt = pr[origin];
		if (!prt) {
			prt = pr[origin] = new Ext.util.DelayedTask(function() {
				delete (pr[origin]);
			});
		}
		prt.delay(1000);
		this.reload(callback, scope);
	}

	/**
	 * @param {Function/Object} callback (Optional) A callback or a config 
	 * object to be passed to the store.reload() function.
	 * @param {Object} scope (Optional) Scope with which to call the callback 
	 * (defaults to the GridModule object). This argument is ignored if a config
	 * Object is given for callback, instead of a function.
	 */
	,reload: function(callback, scope) {
		this.queueReload(callback, scope);
	}
	
	/**
	 * Creates the reload delayed task (and init the reloadQueue array).
	 *
	 * @private
	 */
	,createReloadTask: function() {
		this.reloadQueue = [];
		return this.reloadTask = new Ext.util.DelayedTask(function() {
			var fn = this.doReload,
				rq = this.reloadQueue;
			this.reloadQueue = [];
			this.doReload(function() {
				Ext.each(rq, function(callback) {
					callback.fn.apply(callback.scope, arguments);
				});
			});
		}, this);
	}
	
	// private
	,queueReload: function(callback, scope, delay) {
		if (!Ext.isDefined(delay)) {
			delay = 10;
		}
		var rt = this.reloadTask,
			rq = this.reloadQueue;
		if (callback) {
			rq.push({fn: callback, scope: scope});
		}
		rt.delay(delay);
	}
	
	// private
	,doReload: function(callback, scope) {
		var o;
		if (callback) {
			if (Ext.isFunction(callback)) {
				o = {
					callback: callback
					,scope: scope || this
				};
			} else {
				o = callback;
			}
		}
		if (this.firstLoad) {
			this.store.reload(o);
		}
	}

	/**
	 * Reset displayed columns according to default initial config.
	 *
	 * @private
	 */
	,resetHiddenColumns: function() {
		var grid = this.grid,
			view = grid.view,
			cm = grid.getColumnModel(),
			colCount = cm.getColumnCount(),
			config;
		for (var i = 0; i < colCount; i++) {
			config = cm.config[i];
			config.hidden = config.initialHidden;
			delete cm.totalWidth;
		}

		view.refresh(true);
		cm.fireEvent('bulkhiddenchange', cm);

		this.reload();
	}
	
	// private
	,columnMenu_onBeforeShow: function(colMenu) {
		var cm = this.grid.getColumnModel(),
			colCount = cm.getColumnCount(),
			checkGroups = [];

		colMenu.removeAll();

		// Reset defaults item
		colMenu.add({
			text: "Reset" // i18n
			,tooltip: "Afficher les colonnes par défaut" // i18n
			,iconCls: 'ico reset'
			,scope: this
			,handler: this.resetHiddenColumns
		});
		
		// --- Select all ---
		var checkAllItem = new Ext.menu.CheckItem({
			 text:this.my.showAllColsText
			,checked:!(this.checkIndexes instanceof Array)
			,hideOnClick:false
		});
		colMenu.add(checkAllItem, '-');

		var checkAllGroup = Ext.create('eo.form.SelectableCheckGroup', checkAllItem);
		colMenu.checkGroup = checkAllGroup;
		checkGroups.push(checkAllGroup);
		
		// This method intends to give some air to the layout to avoid some
		// overly manifest freezing of the UI
		var me = this,
			applying = 0;
		var apply = function(fn) {
			var run = function() {
				var g = me.grid,
					view = g.view,
					cm = g.getColumnModel();
				applying++;
				fn();
				applying--;
				if (!applying) {
//					me.grid.el.unmask();
					view.refresh(true);
					cm.fireEvent('bulkhiddenchange', cm);
				}
			};
			if (!applying) {
//				me.grid.el.mask('&nbsp;', 'x-mask-loading');
				run.defer(100);
			} else {
				run();
			}
		};

		// --- Items ---
		var groups = {},
			groupMenus,
			groupSeparator;
		if (this.extra.columnGroups) {
			groupMenus = [];
			Ext.iterate(this.extra.columnGroups.items, function(title, items) {
				var menu = new Ext.menu.Menu();

				groupMenus.push(menu);

				var item = colMenu.add(Ext.create('Ext.menu.CheckItem', {
					hideOnClick: false
					,text: title
					,menu: menu
					,checkHandler: function(cb, check) {
						if (!cb.initiated) return;
						if (menu.items) {
							apply(function() {
								menu.items.each(function(item) {
									item.setChecked(check);
								});
							});
						}
						if (check) {
							this.removeClass('undetermined');
						}
					}
				}));

				menu.parentItem = item;

				checkAllGroup.add(item);
				var checkGroup = Ext.create('eo.form.SelectableCheckGroup', item);
				menu.checkGroup = checkGroup;
				checkGroups.push(checkGroup);

				Ext.each(items, function(item) {
					groups[item] = menu;
				});
			});

			groupSeparator = colMenu.add('-');
		}

		var hasFreeItems = false;

		for(var i = 0; i < colCount; i++){
			if(cm.config[i].hideable !== false){
				var config = cm.config[i],
					text = config.extra && config.extra.groupText || cm.getColumnHeader(i),
					dest = groups[config.dataIndex];

				if (!dest) {
					dest = colMenu;
					hasFreeItems = true;
				}

				var item = new Ext.menu.CheckItem({
					itemId: 'col-'+cm.getColumnId(i),
					text: text,
					checked: !cm.isHidden(i),
					hideOnClick:false,
					disabled: cm.config[i].hideable === false,
					tooltip: cm.config[i].tooltip,
					checkHandler: function(item, checked, cm) {
						// Apply change
						var index = cm.getIndexById(item.itemId.substr(4));
						if(index != -1){
							apply(function() {
								// We don't want to use the normal setHidden
								// method here, because it triggers an overly
								// long to process "hiddenchange" event.
								// We need however to clean the ColumnModel's
								// totalWidth to avoid the columns rendering
								// to become all fucked up (see
								// ColumnModel.setHidden source).
								cm.totalWidth = null;
								cm.config[index].hidden = !checked;
								// cm.setHidden(index, !checked);
								me.grid.store.removeAll();
//								me.grid.el.mask('Chargement', 'x-mask-loading');
								me.queueReload(function() {
//									me.grid.el.unmask();
								}, undefined, 1000);
							});
						}
					}.createDelegate(this, [cm], 2)
				});

				dest.add(item);

				dest.checkGroup.add(item);
			}
		}

		// Finish group menus -- determine checked statut from items
		Ext.each(checkGroups.reverse(), function(checkGroup) {
			checkGroup.init();
		});

		if (groupSeparator && !hasFreeItems) {
			groupSeparator.hide();
		}
	}
	
	/**
	 * Adds an action to the module ribbon toolbar.
	 *
	 * This method should be called from {@link #initActions}.
	 * 
	 * @param {String/Object} action
	 * 
	 * When called with two arguments, this is the unique identifier of the action 
	 * (used in the `extra.toolbar` config option of the module to identify the 
	 * action) as a string.
	 * 
	 * When called with only one arguments, this must be the config Object of the 
	 * ribbon button. In this case, the second argument will be ignored, and the 
	 * config Object **must** contain a key `action` with the unique identifier 
	 * string of the action.
	 * 
	 * @param {Object} buttonConfig 
	 * The ribbon button configuration, if the method is called with 2 arguments.
	 *
	 * @protected
	 */
	,addRibbonAction: function(action, buttonConfig) {
		if (Ext.isObject(action)) {
			buttonConfig = action;
			action = buttonConfig.action;
		}
		this.actions[action] = Ext.apply({
			xtype: 'oce.rbbutton'
		}, buttonConfig);
	}

	/**
	 * This method is called during the module initialization, to add extra
	 * actions to the module's ribbon toolbar, or to implement special menu
	 * actions.
	 * 
	 * @protected
	 */
	,initActions: function() {

		var helpHandler = this.viewHelp.createDelegate(this, [this.getHelpTopic()]);

		var actions = {
			add: {
				xtype: 'oce.rbbutton'
				// addRecord cannot be used as a direct callback because it
				// wants its first argument to be a callback (but the handler
				// will send the button as first arg...)
				,scope: this
				,handler: function() {this.addRecord()}
				,text: "Ajouter" // i18n
				,iconCls: 'ribbon icon add'
				,actionId: 'add'
			}
			,remove: {
				xtype: 'oce.rbbutton'
				,scope: this
				,handler: this.deleteSelectedRecords
				,text: "Supprimer" // i18n
				,iconCls: 'ribbon icon delete'
				,actionId: 'delete'
				,dependsOnSelection: true
			}

			,columns: {
				xtype: 'oce.rbbutton'
				,text: "Colonnes" // i18n
				,iconCls : 'ribbon icon columns'
				,menu: {
					listeners: {
						scope: this
						,beforeshow: this.columnMenu_onBeforeShow
					}
				}
			}

			,pdf: {
				xtype: 'oce.rbbutton'
				,handler: this.exportData.createDelegate(this, ['pdf'])
				,text: "Pdf" // i18n
				,iconCls: 'ribbon icon export_pdf'
			}
			,ods: {
				xtype: 'oce.rbbutton'
				,handler: this.exportData.createDelegate(this, ['ods'])
				,text: "OOCalc" // i18n
				,iconCls: 'ribbon icon export_ods'
			}
			,xls: {
				xtype: 'oce.rbbutton'
				,handler: this.exportData.createDelegate(this, ['xls'])
				,text: "Excel" // i18n
				,iconCls: 'ribbon icon export_excel'
			}
			,csv: {
				xtype: 'oce.rbbutton'
				,handler: this.exportData.createDelegate(this, ['csv'])
				,text: "Texte" // i18n
				,tooltip: "Exporter les données filtrées au format texte CSV, compatible MS Excel" // i18n
				,iconCls: 'ribbon icon export_csv'
			}
			,help: {
				xtype: 'oce.rbbutton', handler: helpHandler.createDelegate(this)
				,iconCls: 'icon ribbon help'
				,depends: this.hasHelp.createDelegate(this)
				,actionId: 'help'
			}
		};

		this.actions = Ext.apply(actions, this.actions);
	}

	// private
	,afterInitActions: function() {
		Ext.iterate(this.actions, function(name, action) {
			Ext.applyIf(action, {
				xtype: 'oce.rbbutton'
			});
		});
	}
	
}); // GridModule declaration

Oce.GridModule.ptypes = {};
Oce.GridModule.registerPlugin = function(name, constructor) {
    Oce.GridModule.ptypes[name] = constructor;
};

// Legacy support (deprecated)
Oce.Modules.GridModule.GridModule = Oce.GridModule;

Oce.GridModule.plugins = {

	IconCls: eo.Class({
		constructor: function(gm) {
			this.gm = gm;

			if (!gm.extra.iconCls) return;

			Ext.apply(gm, {

				beforeCreateWindow: Ext.Function.createSequence(gm.beforeCreateWindow,
					function(config, action) {
						this.configWindowIcon(config, action);
					}
				)

				,beforeCreateTabPanel: Ext.Function.createSequence(gm.beforeCreateTabPanel, function(config) {
					// icon plugin
					if (this.extra.iconCls) {
						config.iconCls = this.getIconCls();
					}
				})

				,getIconCls: function(action) {
					var c;
					if (!this.extra.iconCls) {
						c = action || "";
					} else {
						c = this.extra.iconCls
								.replace("%module%", this.name)
								.replace("%action%", action || "");
					}
					return c;
					return c.split(' ');
				}

				// functionnality: icon
				,configWindowIcon: function(config, action) {
					var ic = this.getIconCls(action),
						current = config.iconCls;
					if (current) {
						config.iconCls = current + " " + ic;
					} else {
						config.iconCls = ic;
					}
				}

				,afterCreateGridStore: Ext.Function.createSequence(gm.afterCreateGridStore, function(store) {
					store.on({
						scope: this
						,beforeload: function() {
							var t = this.tab;
							if (t) {
								t.setIconClass(this.getIconCls("loading gm-tab-loading").split(' '));
							}
						}
						,load: function() {
							var t = this.tab;
							if (t) {
								t.setIconClass(this.getIconCls());
							}
						}
						,exception: function() {
							var t = this.tab;
							if (t) {
								t.setIconClass(this.getIconCls());
							}
						}
					});
				})

			});
		}
	})
};

// Makes all children wait for the Ext4 application to be started
Oce.deps.wait('Eoze.app.Application', function() {
	eo.getApplication().onStarted(function() {
		Oce.deps.reg('Oce.GridModule');
		// Required for inheritance dependency
		Oce.deps.reg('Oce.Modules.GridModule.GridModule');
	});
});

})(); // closure
