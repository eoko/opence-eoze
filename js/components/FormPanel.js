Ext.ns('Oce');

// ----<<  FormPanel  >>--------------------------------------------------------

Oce.FormPanel = Ext.extend(Ext.FormPanel, {

	idValue: undefined
	,initFormItems: undefined

	,constructor: function(config) {

		this.addEvents({
			modificationcleared: true
			,modified: true
		});

		config = Ext.applyIf(config || {}, {
			 url:'index.php'
			,bodyStyle: 'padding:15px; background:transparent'
			,border: false
			,waitMsg: 'Chargement des données'
			,waitTitle: 'Veuillez patientez'
			,trackResetOnLoad: true
		});

//KEEP n' SEE		// --- Foreign combo stores loading callback ---
//		var foreignComboStoreLatch = 0;
//		var formPanel = this;
//
//		var countForeignComboDown = function() {
//			if (--foreignComboStoreLatch === 0) {
//				formPanel.fireEvent('allstoresloaded');
//			}
//		}
//
//		this.onAllStoreLoaded = function(fn) {
//			if (foreignComboStoreLatch === 0) {
//				fn();
//			} else {
//				formPanel.on('allstoresloaded', fn);
//			}
//		}
//
//		Ext.each(config.items, function(item) {
//			if (item.xtype === 'oce.foreigncombo') {
//				foreignComboStoreLatch++;
//				Oce.pushListener(config.items, 'storefirstloaded', countForeignComboDown);
//			} else if (item instanceof Oce.form.ForeignComboBox) {
//				foreignComboStoreLatch++;
////				item.on('storeloaded', countForeignComboDown);
//				item.whenStoreLoaded(countForeignComboDown);
//			}
//		})

		// --- Default Keys ---

		var keys = new Array();

//		if ('submitHandler' in config) {
//
//			var listener = function(field, el) {
//				if (el.getKey() == Ext.EventObject.ENTER) config.submitHandler();
//			}
//
////			for (var i in config.items) {
//			Ext.each(config.items, function(item) {
//				if ((item.xtype !== undefined && !/combo/.test(item.xtype))
//					|| (item instanceof Ext.Component && !(item instanceof Ext.form.ComboBox))) {
//
//					if (!item.listeners) item.listeners = {};
//					
//					if (!item.listeners.specialkey) {
//						item.listeners.specialkey = listener;
////					} else if (Ext.isArray(item.listeners.specialkey)) {
////						item.listeners.specialkey = item.listeners.specialkey.concat([listener]);
////					} else {
////						item.listeners.specialkey = [item.listeners.specialkey, listener]
//					}
//				}
//			});
//		}

//		if ('cancelButtonIndex' in config) {
//			var cancelButtonHandler = config.buttons[config.cancelButtonIndex].handler;
//			keys.push({
//				key: Ext.EventObject.ESC,
//				scope: this,
//				fn: function(key, e) {
//					cancelButtonHandler();
//				}
//			})
//		}

		if (keys.length > 0) {
			if ('keys' in config) {
				var previous;
				if (keys.config instanceof Array) {
					previous = keys.config;
				} else {
					previous = [keys.config];
				}
				config.keys = previous.concat(keys);
			} else {
				config.keys = keys;
			}
		}

		Oce.FormPanel.superclass.constructor.call(this, config);

		if (this.formListeners) {
			this.form.on(this.formListeners);
		}

		this.tabsByName = {};
		Ext.each(this.findByType('panel'), function(item) {
			if (item.tabName) this.tabsByName[item.tabName] = item;
		}, this)

		this.modified = false;
	}
	
	,initComponent: function() {
		Oce.FormPanel.superclass.initComponent.apply(this, arguments);
		
		if (this.submitHandler) {
			
			var h = this.submitHandler,
				l = function(field, el) {
					if (el.getKey() == Ext.EventObject.ENTER) h();
				};
			
			this.items.each(function(item) {
				item.on("specialkey", l);
			});
		}
	}
	
	,afterRender: function() {
		Oce.FormPanel.superclass.afterRender.apply(this, arguments);
		this.addFormChangeListeners();
	}

	,isModified: function() {
		return this.modified;
	}

	,addFormChangeListeners: function() {

		var me = this;

		var changeListener = function() {
			if (!this.modified && !me.preventModificationEvents) {
				this.modified = true;
				me.fireEvent('modified');
			}
		}.createDelegate(this);

		// This listener checks the dirty state of the field before considering
		// to be have changed. This is to avoid false positive with special keys
		// for example.
		var changeListenerWithDirtyCheck = function(field) {
			if (field.isDirty()) {
				changeListener();
			}
		};

		var addChangeListener = function(item) {

			// Defaults on the change event to cover the max of corner cases
			item.on('change', changeListener);

			// But tries to find more appropriate events for specific types of
			// fields
			if (item instanceof Ext.form.TextField) {
				if (item.el) {
					item.mon(item.el, 'keyup', changeListenerWithDirtyCheck.createCallback(item));
				} else {
					item.on('afterrender', function() {addChangeListener(item)})
				}
			}
			if (item instanceof Ext.form.Checkbox) {
				item.on('check', changeListener);
			}
			if (item instanceof Ext.form.TriggerField) {
				item.on('select', changeListener);
			}
			if (item instanceof Oce.form.GridField) {
				item.on('modified', changeListener);
			}
			if (item instanceof Ext.form.HtmlEditor) {
//				item.on('sync', changeListener);
				item.on('change', changeListener);
			}
			if (item instanceof Ext.form.CompositeField) {
				item.items.each(addChangeListener);
			}
			if (item instanceof Ext.ux.form.SpinnerField) {
				item.on('spin', changeListener);
			}
		};

		this.form.items.each(addChangeListener);

		// Search for fieldset with a checkbox with some name (meaning it is
		// probably intended to be sent as form data)
		var walkItems = function(container) {

			if (!container.items) return;

			container.items.each(function(item) {

				if (item instanceof Ext.form.FieldSet
						&& item.checkboxName) {

					var addCheckListener = function(fs) {
						fs.checkbox.on('click', changeListener);
					}
					
					if (item.checkbox) addCheckListener(item);
					else item.on('afterrender', addCheckListener.createCallback(item));
				}

				if (item instanceof Ext.Container) {
					walkItems(item);
				}
			});
		}

		walkItems(this);
	}

	,clearModified: function() {
		this.modified = false;
		this.fireEvent('modificationcleared');
	}

	,setWindow: function(win) {
		if (this.win !== win) {

			// remove previous event
			if (this.windowSaveListener) {
				this.win.un('aftersave', this.windowSaveListener);
				delete this.windowSaveListener;
			}

			this.win = win;
			// shortcuts
			win.setTab = this.setTab.createDelegate(this);

			// clearing modified state on successful save
			win.on('aftersave', this.windowSaveListener = this.clearModified.createDelegate(this));
		}
	}

	,setTab: function(tabName) {
		if (this.tabsByName[tabName]) this.tabsByName[tabName].show();
	}

	,whenLoaded: function(callback, scope) {
		if (this.loaded) {
			callback(this.form);
		} else {
			var me = this;
			this.on('afterload', function() {
				callback(me.form);
				callback.call(scope || me, me.form);
			});
		}
	}

	,refresh: function(callback) {

		var me = this;
		var win = this.win;

		var params = {
			controller: this.controller
			,action:'load_one'
		};
		params[win.pkName] = win.idValue;

//		this.beforeFormLoad(params);
		this.fireEvent('beforeload', params);

		this.preventModificationEvents = true;
		var afterLoadSuccess = function() {

			me.preventModificationEvents = false;

			// Load grid fields
			me.form.items.each(function(field) {
				if (field instanceof Oce.form.GridField) {
					field.load();
				}
			});

			me.clearModified();
		};

		this.form.load({
			url: 'index.php'
			,params: params
			,waitMsg: 'Chargement des données' // i18n
			,waitTitle: 'Veuillez patientez' // i18n

			,scope: this
			,success: function(response, action, type) {
				win.formPages = action.result.pages;
				for (var i=0,l=win.formRefreshers.length; i<l; i++) {
					win.formRefreshers[i]();
				}
				if (this.initFormItems) this.initFormItems(action.result);
//				this.afterEditFormLoaded(this.form);
				this.loaded = true;
				afterLoadSuccess();
				this.fireEvent('afterload', this.form);
				if (callback) callback(this.form);
			}
			,failure: function(form, action) {
				me.preventModificationEvents = false;
				if (action && action.result && action.result.cause === 'sessionTimeout') {
					Oce.mx.Security.onOnce('login', this.refresh.createDelegate(this));
				} else {
					win.close();
					Ext.MessageBox.alert("Erreur", "Impossible de charger les données") // i18n
				}
				if (callback) callback(this.form);
			}
		});
	}

	// Overridden to allow for JsonForm
	,createForm: function() {
        var config = Ext.applyIf({listeners: {}}, this.initialConfig);
		if (config.jsonFormParam || config.serializeForm) {
			return new Oce.form.JsonForm(null, config);
		} else {
			return new Ext.form.BasicForm(null, config);
		}
	}

	,onRender:function() {
        // call parent
        Oce.FormPanel.superclass.onRender.apply(this, arguments);

		// set wait message target
		this.getForm().waitMsgTarget = this.getEl();

		// loads form after initial layout
		// this.on('afterlayout', this.onLoadClick, this, {single:true});
    }

}); // << FormPanel

Ext.reg('oce.form', Oce.FormPanel)

/**
 * @class Oce.columnContainer
 * @extends Ext.Container
 * 
 * An easy to configure multicolumn form container. The columns items can be
 * specified in two ways: either by specifying the {@link #columns} number and
 * the {@link #items} that will be flowed left to right, then top to bottom, or
 * by giving the {@link #cols} items directly.
 * 
 * @cfg Array items The items to be flowed in the columns. The number of column
 * is given by the {@link #columns} option. This option will be ignored if the
 * {@link #cols} option is given.
 * 
 * @cfg Integer columns The number of columns (default to 2). This option
 * will be ignored if the {@link #cols} option is specified.
 * 
 * @cfg Array cols An <b>array</b> of objects specifying the columns' items.
 * Each element of the array must, at least, have a <b>items</b> property. 
 * The other properties of each element will be applied as options to the 
 * container that will be internally created for its column.
 * 
 * @cfg {Integer|String} spacing The spacing between each columns (default to 
 * 5). Its value can be given either as an integer, which will then be 
 * interpreted as a percentage of the total width of the container, or as a 
 * percentage string (e.g. "5%").
 * 
 * @cfg {String} labelAlign The label alignment value used for the text-align specification
 * for the container. Valid values are "left", "top" or "right"
 * (defaults to "top"). This property cascades to child containers and can be
 * overridden on any child container (e.g., a fieldset can specify a different labelAlign
 * for its fields).
 * 
 * @cfg {Integer} tabIndex If specified, the tabIndex will be incrementally
 * applied to each child items, as they are flowed left to right, top to bottom.
 * This option is only valid when the columns are specified with the
 * {@link #columns} and {@link #items} options.
 */
Oce.ColumnContainer = Ext.extend(Ext.Container, {

	constructor: function(config) {

		var cfg = Ext.apply({
			layout: "column"
			,labelAlign: "top"
			,frame: true
			,spacing: 5
			,columns: 2
		}, config);
		delete cfg.cols;
		delete cfg.items;
		delete cfg.columns;
		
		var defaults = Ext.apply({
			xtype: "container"
			,layout: "form"
		}, config.defaults);
		
		var cols = (function(){
			if (config.cols) {
				return config.cols;
			} else if (config.columns === undefined || !config.items) {
				throw new Error('Invalid config options: cols || (columns && items)');
			}
			var n = config.columns;
			var colItems = []; 
			
			var i;
			for (i=0;i<n; i++) {
				colItems.push([]);
			}
			
			var tabIndex = config.tabIndex;
			i=0;
			Ext.each(config.items, function(item) {
				if (tabIndex) {
					colItems[i%n].push(Ext.apply({
						tabIndex: tabIndex
					}, item));
					tabIndex++;
				} else {
					colItems[i%n].push(item);
				}
				i++;
			});
			
			cols = [];
			Ext.each(colItems, function(items) {
				cols.push({items: items});
			});
			
			return cols;
		})();

		var n = cols.length,
			items = [],
			spacing = cfg.spacing;
			
		if (Ext.isString(spacing)) {
			var m = /^(\d+)%?$/.exec(spacing);
			if (m === null) throw new Error("Invalid config option value for spacing: " + spacing);
			spacing = parseInt(m[1]);
		}
		
		var lw = (100 - (n-1) * spacing) / n,
			ow = (100 - lw)/(n-1),
			anchor = (100 - spacing*n) + "%";
			
		lw /= 100;
		ow /= 100;
		
		Ext.each(cols, function(col) {
			var items = col.items;
			for (var i=0,l=items.length; i<l; i++) {
				var item = items[i];
				if (item === " ") {
					items[i] = {
						xtype: "displayfield"
						,fieldLabel: ""
						,value: ""
						,hideLabel: true
					}
				}
			}
		});
			
		for (var i=0,l=cols.length; i<l-1; i++) {
			var ct = Ext.apply({
				columnWidth: ow
				,defaults: Ext.apply({
					anchor: anchor
				}, defaults.defaults)
				,items: cols[i].items
			}, defaults);
			items.push(ct);
		}
		// last col
		items.push(Ext.apply({
			columnWidth: lw
			,defaults: Ext.apply({
				anchor: "100%"
			}, defaults.defaults)
			,items: cols[cols.length - 1].items
		}, defaults));
		
		cfg.items = items;

		Oce.ColumnContainer.superclass.constructor.call(this, cfg);
	}
});

Ext.reg('colcontainer', Oce.ColumnContainer);