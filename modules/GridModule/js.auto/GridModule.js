Ext.ns('Oce');

/**
 *
 * beforeCreateWindow(windowConfig, action): must be called before a window
 * is created, with the config object as argument. This is an opportunity for
 * customizing components to modify the configuration of any window (to set icon,
 * for example).
 *
 * @todo IMPORTANT the configuration objects for windows are shared between all
 * GridModule instances. This is very problematic, since that prevent
 * functionnality providers (plugins) to easily and efficiently customize a
 * window before its creation.
 *
 */
//Ext.ns('Oce.Modules.GridModule').GridModule = Oce.GridModule = Ext.extend(Ext.Panel, {
Ext.ns('Oce.Modules.GridModule').GridModule = Oce.GridModule = Ext.extend(Ext.util.Observable, {

	spp: Ext.Panel.prototype
	
	/**
	 * True if this component fires an "open" event. Read-only.
	 * @type Boolean
	 * @property openEvent
	 */
	,openEvent: true
	/**
	 * True if this component is opened. Read-only.
	 * @type Boolean
	 * @property opened
	 */
	,opened: false

	,constructor: function() {

		this.my = Ext.applyIf({
			selectAllText: "Tout sélectionner"
			,showAllColsText: "Toutes"
			,tab: null
		}, this.my || {})

		Ext.apply(this, this.my);
		this.sc = this.extra.shortcuts;

		this.reloadLatch = 0;
		this.activeAddWindows = {};
		this.editWindows = {};
		this.openActionHandlers = {};

		this.pageSize = this.extra.pageSize || 30;

		Oce.GridModule.superclass.constructor.apply(this, arguments);

		this.addEvents("open", "close");
		
		this.model.initRelations(this.modelRelations);

		// Init plugins
		// Must be done before initActions, to give plugins the opportunity to
		// add their own actions.
		this.initPlugins();

		this.initActions();
		Ext.iterate(this.actions, function(name, action) {
			Ext.applyIf(action, {
				xtype: 'oce.rbbutton'
			});
		});

		this.initSearchPlugin();
		this.initMultisortPlugin();
		this.initYearPlugin();
		this.initFilterPlugin();

		Ext.iterate(this.extra, function(name, config) {
			var pg = Oce.GridModule.plugins[
				name.substr(0,1).toUpperCase() + name.substr(1)
			];
			if (pg) {
				this[name + "Plugin"] = new pg(this, config);
			}
		}, this);

		this.initConfiguration();
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

	,initPlugins: function() {}

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
		this.my.tab.hide();
		this.my.tab.destroy();
		this.my.toolbar = undefined;
		this.my.tab = null;
	}

	,initDefaults: function() {

		Ext.applyIf(this.my, {
			addWindowTitle: this.title + ' : Nouveau'
		});

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

	,beforeInitStore: function(storeConfig) {

	}

	,afterInitStore: function(store) {

	}

	,initStore: function() {

		this.store = this.store || {};

		if (!(this.store instanceof Ext.data.Store)) {

			var controller = this.controller;

			Ext.applyIf(this.store, {
				baseParams: {'controller': controller, action:'load'},
				url: 'index.php',
				root: 'data',
				idProperty: this.primaryKeyName,
				totalProperty: 'count',
				fields: this.storeColumns,
				remoteSort: true
			});

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

			this.afterInitStore(this.store);
//			this.doFirstLoad();
		}
	}

	,createStore: function(config) {
		return new Oce.MultipleSortJsonStore(this.store);
	}

	,editRecord: function(recordId, startTab, cb, scope) {
		// Msg syntax
		if (Ext.isObject(recordId)) {
			cb = recordId.callback;
			scope = recordId.scope || this;
			startTab = recordId.tab;
			recordId = recordId.id;
		}
		// Verify args
		if (recordId === undefined || recordId === null) {
			throw new Error('Record id is required');
		}
		// Go, baby!
		return this.editRowById(recordId, startTab, cb, scope);
	}

	,editRowById: function(rowId, startTab, cb, scope) {
		var win = this.getEditWindow(rowId, function(win) {
			if (!win.hasBeenLoaded) {
				win.setRowId(rowId);
				win.form.reset();
				win.show();
				win.formPanel.refresh(function() {
					win.hasBeenLoaded = true;
					if (cb) cb.call(scope, win);
				});
			} else {
				win.show();
			}

			if (startTab) {
				win.setTab(startTab);
			}
		});
	}

	,editRow: function(row) {

		var el,
			index = this.grid.store.indexOf(row);
		if (index >= 0) {
			var rc = this.grid.view.resolveCell(index,0);
			if (rc) el = Ext.get(rc.cell);
		}

		var win = this.getEditWindow(row.data[this.primaryKeyName], function(win) {
			if (!win.hasBeenLoaded) {
				win.setRow(row);
				win.form.reset();
				win.show(el);
				win.formPanel.refresh(function() {
					win.hasBeenLoaded = true;
				});
			} else {
				win.show();
			}
		});
	}

	,editReccordLine: function(grid, rowIndex) {
		if (this.my.recordEditable !== false) {
			this.editRow(grid.store.getAt(rowIndex))
		}
	}

	,beforeCreateGrid: function(config) {

	}

	,afterCreateGrid: function(grid) {

	}

	,initGrid: function() {

		var checkboxSel = this.gridColumns[0];
		
		var pagingToolbar = {
			xtype: "paging"
			,pageSize: this.pageSize
			,store: this.store
			,params: {action:'load'}
			,displayInfo: true
			,displayMsg: 'Enregistrements {0} - {1} sur {2}'
			,emptyMsg: "Aucune données existantes"
		};

		var config = Ext.apply({
			store: this.store
			,columns: this.gridColumns
			,sm:checkboxSel
			//,autoExpandColumn: 'username'
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
				rowdblclick: this.editReccordLine.createDelegate(this)
			}
		}, this.extra.grid);

		this.beforeCreateGrid(config);

		this.grid = new Ext.grid.GridPanel(config);
		this.grid.ownerGridModule = this;

		this.afterCreateGrid(this.grid);

		return this.grid;
	}

	,saveEdit: function(win) {
		var me = this;
		this.beforeSaveEdit(win, function(stop, successCb, params) {
			if (false !== stop) {
				var action = !params ? 'mod' : {
					name: 'mod'
					,params: params
				};
				me.save.call(me, win, action, successCb);
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
		if (this.extra.editAfterAdd) this.editRowById(newId, this.extra.editAfterAddTab);
	}

	,save: function(win, action, onSuccess, loadModelData) {

		var me = this;
		var form = win.getForm();

		if (form.isValid()) {

			var successHandler = function(form, action) {

				win.formPanel.modified = false;
				// The aftersave event must be fired *before* the win is closed
				// or it will be considered modified and will trigger a confirmation
				// dialog
				win.fireEvent('aftersave', win, action);
				win.close();

				this.reload();
				var msg = Oce.pickFirst(action.result, ['message','messages','msg']);
				if (msg !== undefined) {
					Oce.Ajax.defaultSuccessMsgHandler(msg);
				}
				
				if (onSuccess) {
					if (!loadModelData) {
						onSuccess(action.result.newId);
					} else {
						onSuccess(action.result.newId, action.result.data)
					}
				}
			}.createDelegate(this);

			var extraParams;
			if (Ext.isObject(action)) {
				extraParams = action.params;
				action = action.name;
			}
			var params = Ext.apply({'controller': this.controller, 'action': action}, extraParams);

			var opts = {
				url: 'index.php'
				,params: params
				,waitTitle : 'Interrogation du serveur' // i18n
				,waitMsg : 'Enregistrement en cours' // i18n
				,success: successHandler
				,failure: function(form, action) {
					Oce.Ajax.handleFormError(
						form,
						action,
						me.isForceErrorMessage(win.formPanel, action)
					);
				}
			};

			if (loadModelData) opts.params.dataInResponse = true;

			this.beforeFormSubmit(form, opts);

			form.submit(opts);
		} else {
			Ext.MessageBox.alert("Erreur", 'Certains champs ne sont pas correctement remplis.') // i18n
		}
	}
	
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

		win.on('destroy', function(){
			delete this.activeAddWindows[win.getId()]
		}.createDelegate(this))

		//
		if (loadModelData) win.loadModelData = true;

		if (initValues) {
			Ext.iterate(initValues, function(field,val) {
				win.form.findField(field).setValue(val);
			})
		}

		var addButton = this.getToolbar().getItemForAction("add");
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

	,getSelectedRowsId: function() {
		var ids = [];
		this.checkboxSel.each(function(reccord) {
			ids.push(reccord.data[this.primaryKeyName]);
		}, this)
		return ids;
	}

	,deleteSelectedRecords: function() {
		var ids = this.getSelectedRowsId.call(this);
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
					,action: "delete_multiple"
				}
				,onSuccess: function() {
					if (grid && grid.el) grid.el.unmask();
					me.reload(function() {
						if (callback) callback();
					}, me);
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
		var tbarItems = [{
			text: 'Supprimer'
			,iconCls: 'ico_bin'
			,handler: deleteHandler
		}];

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

		var btnGroups = {
			base: {
				 xtype: 'buttongroup'
				,anchor: 'right'
				,items: [
					 saveButton = new Ext.Button({
						 text: 'Enregistrer'
						 //,iconCls: 'ico_tick'
						 ,iconCls: 'ico_disk'
						 ,isSaveButton: true
						 ,handler: handlers.save
					 })
					 ,cancelButton = new Ext.Button({
						 text: 'Annuler'
						 ,iconCls: 'ico_cross'
						 ,isCloseButton: true
						 ,handler: handlers.forceClose
						 ,hidden: true
					 })
				]
			}
		};

		if (opts && opts.monitorFormModification) {

			saveButton.disable();
			cancelButton.setText('Fermer');

			handlers.onFormPanelCreated(function(formPanel) {
				formPanel.on({
					modified: function() {
						saveButton.enable();
						cancelButton.setText('Annuler');
					}
					,modificationcleared: function() {
						saveButton.disable();
						cancelButton.setText('Fermer');
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
				,handler: this.viewHelp.createDelegate(this, [this.getHelpTopic('add')]) // TODO help
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
		if (tbar instanceof Ext.Component == false) tbar = Ext.create(tbar);

		var kit = new Oce.GridModule.ContentKit({

			pkName: this.primaryKeyName
			,controller: this.controller

			,content: Ext.apply({
				submitHandler: handlers.save
				,controller: this.controller
				,autoScroll: true
			}, formConfig)

			,winConfig: Ext.apply({
				 title: "Enregistrement" // i18n
				,layout: this.my.editWinLayout
				,tbar: tbar
				,actionToolbar: tbar
				,tools: [this.createEditWindowGearTool(tbar, winConfig)]
				,submitButton: tbar.saveButton
			}, winConfig)
			
		});

		kit.on('formpanelcreate', function(formPanel) {
			formPanel.on('beforeload', this.beforeFormLoad);
			formPanel.on('afterload', this.afterFormLoad);
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

	,beforeFormLoad: function(loadParams) {}
	,afterFormLoad: function(form) {}
	,onConfigureAddFormPanel: function(formConfig) {}

	,getEditWindow: function(rowId, cb) {

		if (rowId in this.editWindows) {
			return this.editWindows[rowId];
		}

		var fn = function(win) {

			this.editWindows[rowId] = win;
			this.afterCreateEditWindow(win);

	//		win.on('destroy', function(){
	//			delete this.editWindows[rowId]
	//		}.createDelegate(this))

			win.forceClosing = false;
			win.forceClose = function() {
				win.forceClosing = true;
				win.close();
			};
			win.on({
//				destroy: function() {
				hide: function() {
					// TODO... changed the event because of a bug
					this.editWindows[rowId].destroy();
					delete this.editWindows[rowId];
				}
				,beforerefresh: function(win) {
					if (win.formPanel.isModified()) {
						Ext.Msg.show({
							// i18n
							title: "Confirmer le rechargement"
							,msg: "Cette fenêtre comporte des modifications qui n'ont pas été "
								+ "enregistrées. Si elle est rechargée maintenant, ces "
								+ "modifications seront perdues. Souhaitez-vous continuer "
								+ "en abandonnant les modifications ?"
							,buttons: {
								yes: "Recharger"
								,cancel: "Annuler"
							}
							,fn: function(btn) {
								switch (btn) {
									case 'yes':
										win.refresh(true);
										break;
									case 'cancel':
										break;
								}
							}
						});

						return false;
					}
					return true;
				}
				,beforeclose: function() {
					if (win.forceClosing) return true;
					if (win.formPanel.isModified()) {
						// i18n
						Ext.Msg.show({
							title: "Confirmer la fermeture"
							,msg: "Cette fenêtre comporte des modifications qui n'ont pas été "
								+ "enregistrées. Souhaitez-vous les enregistrer ?"
							,buttons: {
								yes: "Oui"
								,no: "Non"
								,cancel: "Annuler"
							}
							,fn: function(btn) {
								switch (btn) {
									case 'yes':
										win.formPanel.save(function(){
											win.forceClosing = true;
											win.close();
										});
										break;
									case 'no':
										win.forceClosing = true;
										win.close();
										break;
									case 'cancel':
										break;
								}
							}
						});
						return false;
					}
					return true;
				}
				,scope: this
			})

			if (cb) cb(win);
		}.createDelegate(this);

		var win = this.createEditWindow(rowId, fn);
		if (win) fn(win);

		return undefined;
	}

	,afterCreateEditWindow: function(win) {
		this.parseContextHelpItems(win);
	}

	,beforeCreateWindow: function(config, action) {}

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
					})
				}
				if (item instanceof Ext.Container && item.items) {
					walkChildren(item);
				}
			});
		}

		walkChildren(win.formPanel);

		/**
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
		/**
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
	 * Actually creates the edit window. This method can be overriden to
	 * modify the creation of the window, but it should be called only internaly
	 * by the GridModule. Any other use should use the getEditWindow, which
	 * takes keeps track of already opened edit windows, to avoid opening twice
	 * the same record for edit
	 */
	,createEditWindow: function(recordId, cb) {
		
		var winConfig = Ext.apply({}, this.applyExtraWinConfig('edit', {
			 title: this.my.editWindowTitle || (this.title + " : Modifier") // i18n
			,id: this.editWinId(recordId)
			,layout: this.my.editWinLayout
			,refreshable: true
		}));
		
		this.beforeCreateWindow(winConfig, "edit");

		return this.createFormWindow(
			this.getEditFormConfig(),
			winConfig,
			this.saveEdit,
			this.editWindowToolbarAddExtra.createDelegate(this),
			{
				monitorFormModification: true
			}
		);
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
			 title: this.my.addWindowTitle || ("Ajouter : " + this.title) // i18n
			,layout: this.my.addWinLayout
			,id: this.nextAddWinId()
		}));

		this.beforeCreateWindow(winConfig, "add");

		return this.createFormWindow(
			formConfig
			,winConfig
			,function(win) {
				this.saveNew.call(this, win, callback, win.loadModelData);
			}, this.addWindowToolbarAddExtra.createDelegate(this)
		);

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
				Ext.apply({}, params)
				,{
					controller: me.controller
				}
			);

			// Retrieve values from options window
			if (win) {
				if (opts.jsonOptions) {
					requestParams.json_options = encodeURIComponent(Ext.util.JSON.encode(win.form.getValues()));
				} else {
					Ext.iterate(win.form.getValues(), function(k,v) {
						requestParams[k] = v;
					});
				}
			}

			var onSuccess = function(data, response) {
				//location.href = data.url;
				if (win) win.close();
				if (Ext.isIE6 || Ext.isIE7) {
					Ext.Msg.alert(
                'Fichier',
                String.format(
                    "Le fichier est disponible à l'adresse suivante : "
                    + '<a href="{0}">{0}</a>.', 
                    response.url
                )
            );
				} else {
					window.open(response.url);
				}
			};

			if (opts.onSuccess) {
				onSuccess = onSuccess.createSequence(opts.onSuccess, opts.scope);
				delete opts.onSuccess;
			}

			Oce.Ajax.request(Ext.applyIf({
				params: requestParams
				,waitTitle: 'Veuillez patienter' // i18n
				,waitMsg: 'Fichier en cours de création...' // i18n
				,waitTarget: win ? win : undefined
				,onSuccess: onSuccess
			}), opts);
		}

		if (opts) {
			if (opts.form) {

				var form = !Ext.isArray(opts.form) ? opts.form : {
					xtype: 'oce.form'
					,items: opts.form
				};

				win = new Oce.FormWindow(Ext.apply({
					title: opts.winTitle || 'Options'
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
			case 'csv': case 'pdf':break;
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
		Ext.apply(exportParams,
			this.grid.store.lastOptions.params
		);
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

	,getToolbar: function() {
		if (this.my.toolbar === undefined) {
			var items = [];
			var me = this;
			Ext.iterate(this.extra.toolbar, function(name, menuItems) {
				if (menuItems === false) return;
				name = name.replace(/\%title\%/, me.getTitle());
				var groupItems = [];
				Ext.each(menuItems, function(item) {
					if (item === '-') {
						groupItems.push(new Ext.Toolbar.Separator());
					} else {
						if (Ext.isString(item)) {
							item = me.actions[item];
						} else if (Ext.isObject(item)) {
							var itemitem = item.item;
							if (!itemitem) throw new Error('Invalid toolbar item config');
							if (Ext.isString(itemitem)) itemitem = me.actions[itemitem];
							if (false == itemitem instanceof Ext.Component) {
								item = Ext.apply({}, item, itemitem);
							}
						}
						// DEBUG INFO: dying here often means that the module
						// actions have not been initialized correctly
						if (item.depends && !me.hasDependance(item.depends)) return;
						groupItems.push(item);
					}
				});
				if (groupItems.length) items.push({
					xtype: 'buttongroup',
					title: name,
					align: 'bottom',
					items: groupItems
				});
			});

			this.my.toolbar = new Ext.Toolbar({
				items: items
			})

			this.my.toolbar.getItemForAction = function(actionId) {
				var items = this.findBy(function(item){
					if (item.actionId === actionId) return true;
					return undefined;
				});
				if (items.length) return items[0];
				return undefined;
			}
		}

		return this.my.toolbar;
	}

	,buildGridColumnsConfig: function() {
		
		var defaults = this.getGridColumnDefaults();

		var col;
		for (var i=0,l=this.columns.length; i<l; i++) {

			col = this.columns[i];

			if (col.primary !== undefined && col.primary) {
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
				col.renderer = this.renderers[col.renderer];
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

			if (col.grid !== false) {
				this.gridColumns.push(col);
			}

			// --- Grid Store
			this.storeColumns.push(Ext.apply({
				name: col.dataIndex || col.name
			}, col.store));
		}
	}

//	,buildStoreColumnsConfig: function() {
//		Ext.each(this.gridColumns, function(col) {
//			if (col.dataIndex) { // exclude plugin columns
//				this.storeColumns.push({name: col.dataIndex});
//			}
//		}, this);
//	}

	// private
	,FormConfig: Ext.extend(Object, {

		constructor: function(columns, action, defaults) {
			
			this.action = action;
			this.defaults = defaults;

			this.fields = [];
			this.originalIndexes = []; // used to create tabs

			this.stickingTop = []; // tmp
			this.stickingBottom = []; // tmp

			Ext.each(columns, function(col) {
				this.processCol(col);
			}, this);

			// Flush sticking fields
			for (var i = this.stickingTop.length-1; i>=0; i--)
				this.fields.unshift(this.stickingTop[i]);

			Ext.each(this.stickingBottom, function(field) {
				this.fields.push(field);
			}, this);

			delete this.stickingTop;
			delete this.stickingBottom;
		}
		
		// private
		,inherit: function(a, b) {
			Ext.iterate(b, function(k, v) {
				if (a[k] === undefined) {
					a[k] = v;
				} else if (Ext.isObject(a[k])) {
					a[k] = this.inherit(a[k], v);
				}
			}, this);
			return a;
		}

		// private
		,inheritNode: function(obj, src, name) {
			if (name in src) {
				if (name in obj) {
					obj[name] = this.inherit(obj[name], src[name]);
				} else {
					obj[name] = src[name];
				}
			}
		}
		
		// private
		,applyProp: function(name, obj, src, defaultSrc) {
			if (obj.name !== undefined) return;
			else if (src.name !== undefined) obj[name] = src[name];
			else if (defaultSrc.name !== undefined) obj[name] = defaultSrc[name];
		}
		
		// private
		,applyProps: function(obj, src, defaultSrc, props) {
			// TODO: investigate what name should be, bellow (instead of an
			// undeclared var ...)
			Ext.each(props, function(prop) {
				// (see TODO upper) my guess:
				this.applyProp(prop, obj, src, defaultSrc);
				//applyProp(name, obj, src, defaultSrc);
			}, this);
			return obj;
		}

		// private
		,processCol: function(col) {
			
			if (col.form === false) {
				this.originalIndexes.push(null);
				return;
			}
			
			var action = this.action,
				defaults = this.defaults;

			var colForm = col.form || {};

			// --- Inherit
			var config = {
				xtype: col.type !== undefined ? col.type : defaults.type,
				name: col.name,
				fieldLabel: colForm.fieldLabel || col.header,
				'allowBlank': col.allowBlank !== undefined ? col.allowBlank : defaults.allowBlank
			}

			if (action in col) {
				if (col[action] === false) {
					this.originalIndexes.push(null);
					return;
				}
				Ext.apply(config, col[action]);
			}

			if (Ext.isObject(col.form)) this.inherit(config, col.form);

			this.inheritNode(config, col, 'formField');

			// --- Process
			if ('formField' in config) {
				config = Ext.apply({
					fieldLabel: col.header
					,allowBlank: col.allowBlank
				}, config.formField)
			} else {
				if ('hidden' in config) {
					config.xtype = 'hidden';
					delete config.hidden;
				} else if ('readOnly' in config) {
					config.xtype = config.submit === false ?
						'displayfield' : 'oce.submitdisplayfield';
					delete config.readOnly;
					delete config.submit;
				}

				if (col.primary) config.itemId = '*id*';

				this.applyProps(config, col, defaults, [
					'maxLength', 'minLength','inputType'
				]);
			}

			if (Ext.isDefined(config.verticalFlex)) {
				debugger
			}

			if ('regex' in config) {
				config.getErrors = function(regex) {
					return function(v) {
						if (regex.test(v)) return [];
						else return [
							config.validationError || 'Champ invalide' // i18n
						]
					}
				}(
					config.regex instanceof RegExp ? config.regex
						: new RegExp(config.regex.replace(/\\/g,'\\\\'))
				);
			}

			// --- Store result
			this.originalIndexes.push(config);

			if ('stick' in config) {
				if (config.stick == 'top') {
					this.stickingTop.push(config);
				} else if (config.stick == 'bottom') {
					this.stickingBottom.push(config);
				} else {
					throw 'Invalid stick config (must be top|bottom): ' + config.stick;
				}
				delete config.stick;
			} else {
				this.fields.push(config);
			}
		}

	}) // FormConfig

	/**
	 * Must call afterBuildFormsConfig()
	 */
	,buildFormsConfig: function() {

		var defaults = this.getGridColumnDefaults();
		
		function FormConfig(columns, action) {} // << FormConfig

		var addConfig = new this.FormConfig(this.columns, 'add', defaults),
			editConfig = new this.FormConfig(this.columns, 'edit', defaults);

		if (this.forms) {
			Ext.iterate(this.forms, function(name, config) {
				var formFields = new FormConfig(this.columns, name);
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
			// Removed on 15/02/11 14:01
			// This caused window to not autosize on the contained form panel.
			// Issue copb #7
			// Was probably here for a reason, though :/
			// this.my.addWinLayout = this.my.editWinLayout = "fit";
			this.my.addWinLayout = this.my.editWinLayout = "auto";
		}

		//... Edit form
		if (editTabFormItems !== null) {
			if (editTabFormItems.xtype && editTabFormItems.xtype === 'oce.form') {
				this.my.editFormConfig = editTabFormItems;
			} else {
				this.my.editFormConfig = {
					 xtype: 'oce.form'
					,jsonFormParam: 'json_form'
					,bodyStyle: 'padding:0; background:transparent'
					,items: editTabFormItems
				};
			}
		} else {
			this.my.editFormConfig = {
				xtype: 'oce.form'
				,jsonFormParam: 'json_form'
				,items: editConfig.fields
			}
		}

		//... Add form
		if (addTabFormItems !== null) {
			if (addTabFormItems.xtype && addTabFormItems.xtype === 'oce.form') {
				this.my.addFormConfig = addTabFormItems;
			} else {
				this.my.addFormConfig = {
					 xtype: 'oce.form'
					,jsonFormParam: 'json_form'
					,bodyStyle: 'padding:0; background:transparent'
					,items: addTabFormItems
				}
			}
		} else {
			this.my.addFormConfig = {
				xtype: 'oce.form'
				,jsonFormParam: 'json_form'
				,items: addConfig.fields
			}
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

		if (this.gridColumns === undefined) {

			this.checkboxSel = new Ext.grid.CheckboxSelectionModel();
			this.gridColumns = [this.checkboxSel];
			this.storeColumns = [this.checkboxSel];

			if (!this.primaryKeyName) this.primaryKeyName = 'id';

			this.buildGridColumnsConfig();
//			this.buildStoreColumnsConfig();
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
			title: this.my.title
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

		return tab;
	}

	,beforeCreateTabPanel: function(config) {
		// toolbar plugin
		if (this.extra.toolbar !== false) {
			config.tbar = this.getToolbar();
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

		var tabPanelItems = [];

		var firstFormTabItems = null;
		var addedItemNames = {};

		function makeFormTab(tabName, tabElts) {

			var me = this;

//			var convertItemIndexes = function(items) {
//				var convertedItems = [];
//				Ext.each(items, function(itemIndex) {
//					if (Ext.isNumber(itemIndex)) {
//						var item = formConfig.originalIndexes[itemIndex];
//
//						if (item !== null) {
//							convertedItems.push(item);
//							addedItemNames[item.name] = true;
//						}
//					} else {
//						if (itemIndex.items) {
//							itemIndex.items = convertItemIndexes(itemIndex.items);
//						}
//						convertedItems.push(itemIndex);
//					}
//				}, me)
//				return convertedItems;
//			}
			var convertItem = function(index) {
				if (Ext.isNumber(index)) {
					var item;
					if ((item = formConfig.originalIndexes[index])) {
						addedItemNames[item.name] = true;
						return item;
					} else {
						if (item === null) {
							if (window.console) {
								var fn = console.warn ? console.warn
									: (console.log ? console.log : false);
								if (fn) {
									try {
										fn('Item inactive for this action: ' + index);
									} catch (ignored) {}
								}
							}
						} else {
							throw new Error('Invalid item index: ' + index);
						}
						return undefined;
					}
				} else if (index.field) {
					var i = index.field;
					delete index.field;
					item = Ext.apply({}, index, formConfig.originalIndexes[i]);
					addedItemNames[item.name] = true;
					return item;
				} else {
					return false;
				}
			}
			var convertObjectItemIndexes = function(o) {
				Oce.walk(o, function(i,oo) {
					if (Ext.isArray(oo) || Ext.isObject(oo)) {
						convertObjectItemIndexes(oo);
					}
				});
				if (o.items) {
					Oce.walk(o.items, function(i, item) {
						var cItem;
						if ((cItem = convertItem(item))) {
							o.items[i] = cItem;
						}
					});
				}
			}
			var convertItemIndexes = function(items) {
				convertObjectItemIndexes(items);
				Oce.walk(items, function(i, item) {
					var cItem;
					if ((cItem = convertItem(item))) {
						items[i] = cItem;
					}
//					var myItem;
//					if ((myItem = convertItem(item))) {
//						items[i] = myItem;
//					} else if (Ext.isArray(item) || Ext.isObject(item)) {
//						convertItemIndexes(item);
//					}
				});
//				if (items.items) {
//					Oce.walk(items.items, function(i, item) {
//						var myItem;
//						if ((myItem = convertItem(item))) {
//							items.items[i] = myItem;
//						}
//					});
//				}

				return items;
			}

			var tabItems = convertItemIndexes(tabElts);

			return {
				title: tabName
				,tabName: tabName.toLowerCase()
				,autoScroll:true
				,defaults:{anchor:'-20'}
				,items: tabItems
			};
		}

		function applyFormTabConfig(tab, tabConfig) {
			delete tabConfig.items;
			Ext.apply(tab, tabConfig);
		}

		var iterateTabItems = function (tabItems) {
			var rTabItems = [];

			Ext.iterate(tabItems, function(tabName, tabConfig) {

				var tab = null;

				if (Ext.isArray(tabConfig)) {
					// Form tab, shortcut form
					tab = makeFormTab(tabName, tabConfig);
					if (firstFormTabItems === null) firstFormTabItems = tab.items;
				} else if (Ext.isObject(tabConfig)) {
					if (tabConfig.xtype) {
						tab = Ext.applyIf(tabConfig, {
							tabName: tabName.toLowerCase
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
						tab = makeFormTab(tabName, tabConfig.items);
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
				tabPanelItems.push({
					expanded: true

					,deferredRender:false
					,hideMode:'offsets'

					,defaults:{
						 layout:'form'
						,autoScroll: true
						,defaultType:'textfield'
						,bodyStyle:'padding:10px;  background:transparent;'
					}

					,items: iterateTabItems(groupTabConfig)
				})
			})
		} else {
			tabPanelItems.push(iterateTabItems(tabItems));
		}

		// Add hidden fields to first tab
		if (firstFormTabItems === null) {
			Ext.each(formConfig.fields, function(field) {
				if (field.xtype == 'hidden' && !addedItemNames[field.name]) {
					throw new Error('Missing form tab...');
				}
			})
		} else {
			Ext.each(formConfig.fields, function(field) {
				if (field.xtype == 'hidden' && !addedItemNames[field.name]) {
					firstFormTabItems.push(field);
				}
			}, this);
		}

		if (tabConfig.groupTabs) {
			return [{
				 xtype:'grouptabpanel'

				,tabWidth: 130
				,activeGroup: 0

				,width: tabConfig.windowWidth
				,height: tabConfig.windowHeight

				// Is this necessary with GroupTab ??
				// this line is necessary for anchoring to work at
				// lower level containers and for full height of tabs
				,anchor:'100% 100%'

				,items: tabPanelItems
			}]
		} else {
//			if (tabPanelItems.length == 1) {
//				return {
//					xtype: 'oce.form'
//					,jsonFormParam: 'json_form'
//					,items: tabPanelItems[0].items
//				};
//			}
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
					,bodyStyle:'padding:10px;  background:transparent;'
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
			this.actions.filter = {
				xtype: 'oce.rbbutton'
				,text: 'Filtres'
//				,iconCls: 'b_ico_add'
				,menu: this.createFilterMenu(filterItems)
			};
		}
	}

	,createFilterMenu: function(filters) {

		var me = this
			,blockCheckHandler = false
			;

		var checkAllItem = new Ext.menu.CheckItem({
			 text:'Tous' // i18n
			,checked:!(this.checkIndexes instanceof Array)
			,hideOnClick:false
			,filterName: 'all'
			,handler:function(item) {
				var checked = ! item.checked;
				blockCheckHandler = true;
				item.parentMenu.items.each(function(i) {
					if(item !== i && i.setChecked && !i.disabled && !i.isOption) {
						i.setChecked(checked);
					}
				});
				blockCheckHandler = false;
			}
		});

		var lastFilters = {};
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
			})

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

		return {
			items: items
			,listeners: {
				scope: this
				,beforehide: searchHandler
				,afterrender: function(menu) {
					checkHandler.call(this, {
						parentMenu: menu
					});
					if (this.store) {
						searchHandler.call(this, menu);
					} else {
						this.afterInitStore = this.afterInitStore.createSequence(function() {
							searchHandler.call(me, menu);
						});
					}
				}
			}
		};
	}

	,initSearchPlugin: function() {

		if (!this.extra.search) return;


		this.beforeCreateGrid = this.beforeCreateGrid.createSequence(function(config) {

			if (this.extra.search === true)
				this.extra.search = {}

			var search = this.extra.search;

			Ext.applyIf(search, {
				minChars: false
				,iconCls:'icon-magnifier'
				,autoFocus: false
				,columnGroups: this.extra.columnGroups
			})

			var plugin = new Ext.ux.grid.Search(
				Ext.apply({mode: 'remote'}, search)
			)

			if (!search.compiled) {

				var searchCheckIndexes   = [],
					enableIndexes        = [],
					searchReadOnly       = [];

				Ext.each(this.columns, function(col) {
					var colExtra = col.extra.search;

					if (colExtra && !colExtra.disabled) { // !== false && !== undefined

						if (colExtra.enabled === undefined || colExtra.enabled)
							enableIndexes.push(col.dataIndex);

						if (colExtra.select)
							searchCheckIndexes.push(col.dataIndex);

						if (colExtra.readOnly)
							searchReadOnly.push(col.dataIndex);

					}
				})

				search.compiled = {
					enableIndexes: enableIndexes
					,checkIndexes: searchCheckIndexes
					,readOnlyIndexes: searchReadOnly
				}
			}

			plugin.checkIndexes = search.compiled.checkIndexes;
			plugin.enableIndexes = search.compiled.enableIndexes;
			plugin.readonlyIndexes = search.compiled.readOnlyIndexes;

			if (config.plugins !== undefined)
				config.plugins.push(plugin);
			else
				config.plugins = [plugin];
		}, this)
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

		if (!this.extra.multisort) return;

		this.afterInitStore = this.afterInitStore.createSequence(function(store) {
			this.doInitMultisortPlugin(store);
		}, this)
	}

	,doInitMultisortPlugin: function(store) {

		this.beforeCreateGrid = this.beforeCreateGrid.createSequence(function(config) {

			var reorderer = new Ext.ux.ToolbarReorderer();

			var droppable = this.gridTbarDroppable = new Ext.ux.ToolbarDroppable({
				/**
				 * Creates the new toolbar item from the drop event
				 */
				createItem: function(data) {
					var column = this.getColumnFromDragDrop(data);

					return createSorterButton({
						text    : column.header,
						sortData: {
							field: column.dataIndex,
							direction: "ASC"
						}
					});
				},

				calculateEntryIndex: function(e) {
					var baseEntryIndex = this.calculateBaseEntryIndex(e);
					return Math.max(2, this.calculateBaseEntryIndex(e)); // label cannot be moved
				},

				/**
				 * Custom canDrop implementation which returns true if a column can be added to the toolbar
				 * @param {Object} data Arbitrary data from the drag source
				 * @return {Boolean} True if the drop is allowed
				 */
				canDrop: function(dragSource, event, data) {
					var sorters = getSorters(),
						column  = this.getColumnFromDragDrop(data);

					for (var i=0; i < sorters.length; i++) {
						if (sorters[i].field == column.dataIndex) return false;
					}

					return true;
				},

				afterLayout: doSort,

				/**
				 * Helper function used to find the column that was dragged
				 * @param {Object} data Arbitrary data from
				 */
				getColumnFromDragDrop: function(data) {
					var index    = data.header.cellIndex,
						colModel = this.grid.colModel,
						column   = colModel.getColumnById(colModel.getColumnId(index));

					return column;
				}.createDelegate(this)
			});

			var tbar = config.tbar = new Ext.Toolbar({
				plugins: [reorderer, droppable],
				items  : [{
						xtype: 'tbtext'
						,text: 'Trier par :'
					}, {
						iconCls: 'ico_cross'
						,handler: clearSort.createDelegate(this)
					}]
				,listeners: {
					scope    : this,
					reordered: function(button) {
						changeSortDirection(button, false);
					}
				}
			});

			/**
			 * Callback handler used when a sorter button is clicked or reordered
			 * @param {Ext.Button} button The button that was clicked
			 * @param {Boolean} changeDirection True to change direction (default). Set to false for reorder
			 * operations as we wish to preserve ordering there
			 */
			function changeSortDirection(button, changeDirection) {
				var sortData = button.sortData,
					iconCls  = button.iconCls;

				if (sortData != undefined) {
					if (changeDirection !== false) {
						button.sortData.direction = button.sortData.direction.toggle("ASC", "DESC");
						button.setIconClass(iconCls.toggle("sort-asc", "sort-desc"));
					}

					store.clearFilter();
					doSort();
				}
			}

			/**
			 * Convenience function for creating Toolbar Buttons that are tied to sorters
			 * @param {Object} config Optional config object
			 * @return {Ext.Button} The new Button object
			 */
			function createSorterButton(config) {
				config = config || {};

				Ext.applyIf(config, {
					listeners: {
						click: function(button, e) {
							changeSortDirection(button, true);
						}
					},
					iconCls: 'sort-' + config.sortData.direction.toLowerCase(),
					reorderable: true
				});

				return new Ext.Button(config);
			}

			var hasMultiSort = false, ignoreSingleSort = false;

			function doSort() {
				hasMultiSort = true;
				ignoreSingleSort = true;
				store.sort(getSorters(), "ASC");
			}

			var clearSortToolbar = function() {
				hasMultiSort = false;
				Ext.each(tbar.findByType('button'), function(button) {
					if (button.reorderable) {
						tbar.remove(button);
					}
				});
			}

			function clearSort() {
				clearSortToolbar();
				store.sort(
					this.defaultSortColumn || this.getDefaultSortColumn(this.grid),
					this.defaultSortDirection || 'ASC'
				);
				store.reload();
			}

			/**
			 * Returns an array of sortData from the sorter buttons
			 * @return {Array} Ordered sort data from each of the sorter buttons
			 */
			function getSorters() {
				var sorters = [];

				Ext.each(tbar.findByType('button'), function(button) {
					if (button.reorderable) {
						sorters.push(button.sortData);
					}
				}, this);

				return sorters;
			}

			// --- Store Sort Event ---

			this.store.on('singlesort', function() {
				if (hasMultiSort && !ignoreSingleSort) clearSortToolbar();
				else ignoreSingleSort = false;
			}, this);
		}, this)

		// --- Override initGrid ---

		this.afterCreateGrid = this.afterCreateGrid.createSequence(function(grid) {

			this.grid.on('render', function() {
				var dragProxy = this.grid.getView().columnDrag,
					ddGroup   = dragProxy.ddGroup;

				this.gridTbarDroppable.addDDGroup(ddGroup);
			}.createDelegate(this))
		}, this)
	}

	,initYearPlugin: function() {

		if (!Oce.mx.application.YearManager) return;
		
		this.beforeFormSubmit = this.beforeFormSubmit.createSequence(function(form, opts) {
			opts.params = Ext.apply(opts.params || {}, {
				year: Oce.mx.application.YearManager.value
			})
		});

		if (this.extra.year === false) {
			this.year = false;
			return;
		}

		// Prevent first load before year has been loaded
		var uberDoFirstLoad = this.doFirstLoad;
		this.doFirstLoad = function() {
			Oce.mx.application.YearManager.waitFirstLoad(uberDoFirstLoad.createDelegate(this));
		}

		this.afterYearChanged = function(year) {};

		this.setYear = function(year) {
			if (year !== this.year) {
				this.year = year;
				this.store.setBaseParam('year', year);
				this.reload();
				this.afterYearChanged(year);
			}
		};

		this.getYear = function() {
			return this.year;
		}

		Oce.mx.application.YearManager.on('yearchanged', this.setYear.createDelegate(this));
		this.year = Oce.mx.application.YearManager.value;

		this.afterInitStore = this.afterInitStore.createSequence(function() {
			this.store.setBaseParam('year', this.year);
		}, this);

		this.afterYearChanged = this.afterYearChanged.createSequence(function(year) {
			Ext.iterate(this.activeAddWindows, function(id, win) {
				Ext.each(win.form.items.items, function(item) {
					if (item.name == 'year') { // TODO:rx config year field (year)
						item.setValue(year);
					} else if (item instanceof Oce.form.ForeignComboBox) {
						item.store.setBaseParam('year', year);
						item.store.load();
						item.reset();
					} else if (item instanceof Oce.form.GridField) {
						item.setBaseParam('year', year, true);
					}
				})
			})
		}, this)

		this.configureFormPanel = function(items, year) {
			var yearBaseParams = {'year': year};
			Ext.each(items, function(item) {
				if (item instanceof Oce.form.ForeignComboBox) {
					item.store.baseParams.year = year;
					item.store.load();
				} else if (item instanceof Oce.form.GridField) {
					item.setBaseParam('year', year, true);
				}else if (item.xtype === 'oce.foreigncombo' || item.xtype === 'gridfield') {
					item.baseParams = !item.baseParams ? yearBaseParams
						: Ext.apply(item.baseParams, yearBaseParams);
				} else if (item.name == 'year') {
					if (item instanceof Ext.Component) {
						item.setValue(year);
					} else {
						item.value = year;
					}
				}
			})
		};

		this.beforeFormLoad = this.beforeFormLoad.createSequence(function(params) {
			params.year = this.year;
		}, this);

		this.afterFormLoad = this.afterFormLoad.createSequence(function(form) {
			this.configureFormPanel(
				form.items.items,
				this.year
			);
		}, this)

		this.onConfigureAddFormPanel = this.onConfigureAddFormPanel.createSequence(function(config) {
			this.configureFormPanel(config.items, this.year);
		}, this);

		this.onEditFormItemsConfig = this.onEditFormItemsConfig.createSequence(function(items) {
			Ext.each(items, function(item) {
				if (item instanceof Oce.form.ForeignComboBox
						|| item.xtype === 'oce.foreigncombo') {

					Oce.form.LoadLatch.addFirstTo(item);
				} else if (item instanceof Oce.form.GridField
						|| item.xtype === 'gridfield') {

					Oce.form.LoadLatch.addTo(item);
				}
			})
		}, this)
	}
	
	,open: function(destination, config, action) {
		
		this.opening = true;
		
		if (!destination) destination = Oce.mx.application.getMainDestination();

		if (this.my.tab === null) {
			this.my.tab = this.create(config);
			destination.add(this.my.tab);
		}
		
		this.my.tab.show();

		if (this.my.toolbar) this.my.toolbar.doLayout();
		
		this.opening = false;
		this.opened = true;
		this.fireEvent("open", this);
		
		if (action) {
			Ext.iterate(action, function (k, v) {
				var fn = this.openActionHandlers[k];
				if (fn) fn.call(this, v);
			}, this);
		}
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
	
	,executeAction: function(name, callback, scope, args) {
		if (Ext.isObject(name)) {
			callback = name.callback || name.fn;
			scope = name.scope || this;
			args = name.args;
			name = name.action || name.name;
		}
		return this.moduleActions[name].call(this, callback, scope, args);
	}
	
	,getAction: function(name) {
		return this.actions[name].createDelegate(this);
	}
	
	,firstLoad: false

	,doFirstLoad: function() {
		if (!this.firstLoad) {
			var store = this.store;
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
	 * @param {Function|Object} callback (Optional) A callback or a config 
	 * object to be passed to the store.reload() function.
	 * @param {Object} scope (Optional) Scope with which to call the callback 
	 * (defaults to the GridModule object). This argument is ignored if a config
	 * Object is given for callback, instead of a function.
	 */
	,reload: function(callback, scope) {
		if (this.reloadLatch > 0) {
			this.reloadLatch--;
		} else {
			var o;
			if (callback) {
				if (Ext.isFunction(callback)) {
					o = {
						callback: callback
						,scope: scope || this
					}
				} else {
					o = callback;
				}
			}
			if (this.firstLoad) {
				this.reloadLatch = 0;
				this.store.reload(o);
			}
		}
	}
	
	// private
	,columnMenu_onBeforeShow: function(colMenu) {
		var cm = this.grid.getColumnModel(),
			colCount = cm.getColumnCount();
			
		colMenu.removeAll();
		
		// --- Select all ---
		var checkAllItem = new Ext.menu.CheckItem({
			 text:this.my.showAllColsText
			,checked:!(this.checkIndexes instanceof Array)
			,hideOnClick:false
			,handler:function(item) {
				var checked = ! item.checked;
				apply(function() {
					item.parentMenu.items.each(function(i) {
						if(item !== i && i.setChecked && !i.disabled) {
							i.setChecked(checked);
						}
					});
				});
			}
		});
		colMenu.add(checkAllItem,'-');
		
		// This method intends to give some air to the layout to avoid some
		// overly manifest freezing of the UI
		var me = this,
			applying = 0;
		var apply = function(fn) {
			var run = function() {
				applying++;
				fn();
				applying--;
				if (!applying) {
					me.grid.el.unmask();
					me.grid.view.refresh(true);
				}
			};
			if (!applying) {
				me.grid.el.mask('Application', 'x-mask-loading');
				run.defer(100);
			} else {
				run();
			}
		};

		// --- Items ---
		var checkAllSelected = false;

		var groups = {}, groupMenus;
		if (this.extra.columnGroups) {
			groupMenus = [];
			Ext.iterate(this.extra.columnGroups.items, function(title,items) {
				var menu = new Ext.menu.Menu();

				groupMenus.push(menu);

				colMenu.add(new Ext.menu.CheckItem({
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
							})
						}
					}
				}));

				Ext.each(items, function(item) {
					groups[item] = menu;
				});
			});
			colMenu.add('-');
		}

		for(var i = 0; i < colCount; i++){
			if(cm.config[i].hideable !== false){
				checkAllSelected = checkAllSelected || !cm.isHidden(i);
				(groups[cm.config[i].dataIndex] || colMenu).add(new Ext.menu.CheckItem({
					itemId: 'col-'+cm.getColumnId(i),
					text: cm.getColumnHeader(i),
					checked: !cm.isHidden(i),
					hideOnClick:false,
					disabled: cm.config[i].hideable === false,
					checkHandler: function(item, checked, cm) {
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
							});
						}
					}.createDelegate(this, [cm], 2)
				}));
			}
		}

		checkAllItem.setChecked(checkAllSelected);

		// Finish group menus -- determine checked statut from items
		if (groupMenus) {
			Ext.each(groupMenus, function(menu) {
				var checked = false,
					cb = menu.ownerCt;
				if (menu.items) {
					menu.items.each(function(item) {
						if (item.checked) checked = true;
					});
				} else {
					cb.disable();
				}
				cb.setChecked(checked);
				cb.initiated = true;
			});
		}

	}

	,initActions: function() {

		var helpHandler = this.viewHelp.createDelegate(this, [this.getHelpTopic()]);
//		var helpHandler = function() {
//			var panel = new Oce.AutoloadPanel({
//				 controller: 'help'
//				,action: 'get_topic'
//				,name: this.my.name
//				,collapsible: false
//				,titleCollapse: false
//			})
//			var win = new Oce.w({
//				 items: [panel]
//				,title: "Aide: " + this.getTitle()
//				,width: 350
//				,height: 180
//	//					,layout: 'fit'
//				,collapsible: true
//			});
//			var win = new eoko.ext.IFrameWindow({
//				title: "Aide: " + this.getTitle()
//				,width: 350
//				,height: 180
//				,collapsible: true
//				,url: this.my.helpUrl
//			});
//			win.show();
//		}.createDelegate(this);

		var actions = {
			add: {
				xtype: 'oce.rbbutton'
				// addRecord cannot be used as a direct callback because it
				// wants its first argument to be a callback (but the handler
				// will send the button as first arg...)
				,handler: function() {this.addRecord()}.createDelegate(this)
				,text: 'Ajouter'
				,iconCls: 'b_ico_add'
				,actionId: "add"
			}
			,remove: {
				xtype: 'oce.rbbutton'
				,handler: this.deleteSelectedRecords.createDelegate(this)
				,text: 'Supprimer'
				,iconCls: 'b_ico_del'
				,actionId: "delete"
			}

			,columns: {
				xtype: 'oce.rbbutton'
				,text: 'Colonnes'
				,iconCls : 'b_ico_columns'
				,menu: {
					listeners: {
						scope: this
						,beforeshow: this.columnMenu_onBeforeShow
					}
				}
			}

			,pdf: {
				xtype: 'oce.rbbutton', handler: this.exportData.createDelegate(this, ['pdf']),
				text: 'Export Pdf', iconCls: 'export_pdf'
			}
			,csv: {
				xtype: 'oce.rbbutton', handler: this.exportData.createDelegate(this, ['csv']),
				text: 'Export Excel', iconCls: 'export_excel'
			}
			,help: {
				xtype: 'oce.rbbutton', handler: helpHandler.createDelegate(this),
				text: '- Aide -', iconCls: 'bhelp'
				,depends: this.hasHelp.createDelegate(this)
				,actionId: "help"
			}

		}

		if (this.actions) Ext.applyIf(this.actions, actions);
		else this.actions = actions;
	}

});

Oce.GridModule.plugins = {

	IconCls: eo.Class({
		constructor: function(gm) {
			this.gm = gm;

			if (!gm.extra.iconCls) return;

			Ext.apply(gm, {

				beforeCreateWindow: gm.beforeCreateWindow.createSequence(
					function(config, action) {
						this.configWindowIcon(config, action);
					}
				)

				,beforeCreateTabPanel: gm.beforeCreateTabPanel.createSequence(function(config) {
					// icon plugin
					if (this.extra.iconCls) {
						config.iconCls = this.getIconCls();
					}
				})

				,getIconCls: function(action) {
					if (!this.extra.iconCls) {
						return action || "";
					} else {
						return this.extra.iconCls
								.replace("%module%", this.name)
								.replace("%action%", action || "");
					}
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

			});
		}
	})
}

Oce.deps.reg('Oce.GridModule');