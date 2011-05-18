/**
 * @author Éric Ortéga
 * @copyright 2010 (c), Éric Ortéga
 */

Oce.deps.wait('Ext.ux.form.TwinComboBox', function() {

	Oce.form.SimpleComboBox = Ext.extend(Ext.ux.form.TwinComboBox, {

		oData: {}

		,createRenderer: function(defaultLabel) {
			var me = this;
			return function(v) {
				if (me.oData[v]) return me.oData[v];
				else if (defaultLabel !== undefined) return defaultLabel;
				else return v;
			}
		}

		,constructor: function(cfg) {
			
			if (!cfg.data) {
				cfg = Ext.apply({
					data: []
				}, cfg);
			}

			var data;
			if (Ext.isObject(cfg.data)) {
				this.oData = cfg.data;
				data = [];
				Ext.iterate(cfg.data, function(value,label){
					data.push([value,label]);
				});
			} else if (Ext.isArray(cfg.data)) {
//				data = cfg.data;
				var i=0;
				data = [];
				Ext.each(cfg.data, function(v){
					data.push(Ext.isArray(v) ? v : [i, v]);
					this.oData[i++] = v;
				}, this);
			} else {
				throw new Error('Invalid config option "data": ' + cfg.data);
			}

			this.store = new Ext.data.ArrayStore({
				fields: ['value', 'name'],
				data: data
			})

			// do NOT load local store!
			// this.store.load();

			var config = {
				 xtype: 'combo'
				,store: this.store
				,displayField:'name'
				,valueField:'value'
				,hiddenName:cfg.field
				,mode:'local'
				,editable: false
				,triggerAction: 'all'
			}

			Ext.apply(config, Ext.apply({
				field:undefined,data:undefined
			}, cfg));

			Oce.form.SimpleComboBox.superclass.constructor.call(this, config);
		}
	});

	Ext.reg('oce.simplecombo', Oce.form.SimpleComboBox);

	Oce.form.SimpleChainedCombo = Ext.extend(Oce.form.SimpleComboBox, {

		chainedField: "value"

//		,constructor: function(config) {
//			Oce.form.SimpleChainedCombo.superclass.constructor.call(this, config);
//		}

//		,doQuery: function() {
//			debugger;
//			Oce.form.SimpleChainedCombo.superclass.doQuery.apply(this, arguments);
//		}
			
		,afterRender: function() {
			Oce.form.SimpleChainedCombo.superclass.afterRender.apply(this, arguments);

			var me = this
				,store = this.store
				,other
				;

			var filter = function(rv) {
				var values = me.chainedValues[rv];
				if (values === undefined) {
					store.clearFilter();
				} else {
					var l = values.length,
						v = me.getValue()
						ch = false
						;

					store.filterBy(function(rec) {

						var recV = rec.data[me.chainedField];

						for (var i=0; i<l; i++) {
							if (values[i] == recV) {
								return true;
							}
						}
						
						if (!ch && v == recV) ch = true;

						return false;
					});

					if (ch) {
						me.setValue(store.data.items[0] !== undefined ? store.data.items[0].data.value : null);
					}
					
					me.onFocus();
				}
			}
			
			var listener = function(combo, record, i) {
				filter(record.data[me.chainedField]);
			};

			other = this.chainedCombo;

			if (Ext.isString(this.chainedCombo)) {
				var form = me.findParentByType('oce.form').form || me.findParentByType('form');
				other = form.findField(this.chainedCombo);
				filter(other.getValue());

//				form.on('actioncomplete', function(f, action) {
//					if (action.type === 'load') {
//						me.doQuery();
//						filter(other.getValue());
//					}
//				});
			}
			
			me.doQuery(); // this will set the lastQuery value, else the first
			// doQuery call will clearFilter on the store
			filter(other.getValue());
			
			if (other instanceof Ext.util.Observable) {
				other.on('select', listener, this);
			} else if (Ext.isString(other)) {
				other.listeners = Ext.apply(other.listeners, {
					select: listener
				});
			}
		}
	});

	Ext.reg('oce.simplechainedcombo', Oce.form.SimpleChainedCombo);

	Oce.form.ForeignComboBox = Ext.extend(Ext.ux.form.TwinComboBox, {

//REM (unused) 		hasLoading: true

		/**
		 * @cfg {Boolean} remoteOnce
		 * True to switch the store on local mode after the initial loading has
		 * occured. This will make the combo more reactive, especially on query
		 * operation, if you know that all the data will be loaded in one time
		 * and won't depend on server side processing after that.
		 */
		remoteOnce: false

		,constructor: function(cfg) {

			var me = this;
			var storeBaseParams = Ext.apply({
				controller:cfg.controller,
				action: cfg.action || 'auto_complete'
			}, cfg.baseParams || {});

			if (cfg.autoComplete) {
				storeBaseParams.autoComplete = cfg.autoComplete;
			}

			this.store = new Ext.data.JsonStore({
				url: 'index.php'

				,baseParams: storeBaseParams
				
				,root: 'data'
				,fields: ['id', 'name']

				,totalProperty: 'count'
				,autoSelect: false
			});

			if (cfg.pageSize !== undefined) {
				this.store.baseParams.limit = cfg.pageSize;
				this.paginated = true;
			}

			var storeLoaded = false;
			this.whenStoreLoaded = function(fn) {
				if (storeLoaded) {
					fn();
				} else {
					if (!me.storeLoadingListeners) {
						me.storeLoadingListeners = [fn];
					} else {
						me.storeLoadingListeners.push(fn);
					}
				}
			}

//			this.store.load({
//				callback: function() {
//					if (!storeLoaded) {
//						this.fireEvent('storefirstloaded');
//					}
//					storeLoaded = true;
//					Ext.each(storeLoadingListeners, function(l) {l()});
//					this.fireEvent('storeloaded');
//				}.createDelegate(this)
//			})

			this.store.load = function(options) {
				if (Oce.form.LoadLatch.getFrom(this).canLoad()) {
					this.store.load = function(options) {
						Ext.data.JsonStore.prototype.load.apply(this, arguments);
						this.fireEvent('storeloaded');
					}
					var opts = {
						callback: function() {
							if (!this.store) return;

							if (!storeLoaded) {
								this.onStoreFirstLoaded();
								storeLoaded = true;
							}
							
							// If the selected item is not in the first page size
							// limit, then the server will send an additional row
							// for the selected value, so that the value can correctly
							// be displayed... This row must be removed, after the
							// value has been set:
							if (this.paginated && this.store.data.length > cfg.pageSize) {
								this.store.data.removeAt(this.store.data.length-1);
							}
						}.createDelegate(this)
					};
					// If the combo is paginated, the initial value must be sent
					// to the server, so that it can ensure that the data for the
					// selected row are included in the response (in order to
					// correctly set the display value of the selected value in the
					// combo):
					if (this.paginated && this.initialisationValue) {
						opts.params = {
							initValue: this.initialisationValue
						};
					}
					this.store.load(opts);
				}

//				if (!options) options = {};
//
//				Ext.apply(options, {
//					callback: function() {
//						if (!storeLoaded) {
//							this.fireEvent('storefirstloaded');
//						}
//						storeLoaded = true;
//						Ext.each(storeLoadingListeners, function(l) {l()});
//						this.fireEvent('storeloaded');
//					}.createDelegate(this)
//				})
//
//				Ext.data.JsonStore.prototype.load.call(this.store, options);
			}.createDelegate(this);

			var config = {
				 xtype: 'combo'
				,store: this.store
				,displayField: 'name'
				,valueField: 'id'
				,hiddenName: cfg.column
				,mode: 'remote'
				,editable: false
				,triggerAction: 'all'
				,lastQuery: ''
			}

			delete cfg.controller;
			delete cfg.column;

			var defaults = {};
			if (cfg.pageSize !== undefined) {
				defaults.minListWidth = 250;
				defaults.resizable = true;
			}

			Ext.apply(config, cfg, defaults);

			if (config.editable) {
				Ext.applyIf(config, {
					 typeAhead: true
					,selectOnFocus: true
	//				,triggerAction: query
	//				,forceSelection: true
					,minChars: 0
	//				,mode: local
				})
			}

			Oce.form.ForeignComboBox.superclass.constructor.call(this, config);

			this.addEvents('storeloaded', 'storefirstloaded');

			this.store.load();
			// Trying to force theading... doesn't seem to work
//			this.store.load.createDelegate(this.store).defer(50);
		}

		,expand: function() {
			if (!this.hiddenLoad) {
				Oce.form.ForeignComboBox.superclass.expand.call(this);
			}
		}

		,onStoreFirstLoaded: function() {
			
			if (this.remoteOnce) this.mode = 'local';

			// init setValue
			var me = this;
			// restore initial preventMark config option
			me.preventMark = me.initialConfig.preventMark || false;
			if (this.initialisationValue !== undefined) {
				Oce.form.ForeignComboBox.superclass.setValue.call(me, me.initialisationValue);
				me.originalValue = me.initialisationValue;
			}
			// if the combo is paginated, then we must ensure that the
			// server will include data for the set row each time the
			// value is changed, by specifying the initValue param.
			// It is best not to be set as a baseParam though, as this is
			// not useful for all other load operations initiated
			// internally by ext.
			// It seems that it is useless to remove the aditional row
			// for the selected value (see the KEEP below) because
			// apparently ext kinda reset the store params after setValue...
			// That works this way, I haven't investigated any further,
			// uncomment the lines bellow in case of problem)
			if (me.paginated) {
				me.setValue = function(v, cb) {
					if (v !== undefined && v !== null) {
						me.hiddenLoad = true;
						me.store.load({
							params: {
								initValue: v
							}
							,callback: function() {
								Oce.form.ForeignComboBox.superclass.setValue.call(me, v);
//KEEP										// Remove the aditionnal row added by the
//										// server in the response (containing the
//										// selected value's data), if any.
//										if (me.store.data.length > cfg.pageSize) {
//											me.store.data.removeAt(me.store.data.length-1);
//										}
//
								// Prevent the combo list from popping
								// again on the store load event
								me.hiddenLoad = false;
								if (cb) cb();
							}
						})
					}
				}
			} else {
				me.setValue = Oce.form.ForeignComboBox.superclass.setValue;
			}
			
			// initialisation value callback
			if (this.initialisationValueCallback) {
				this.initialisationValueCallback.call(
				this.initialisationValueCallbackScope || this, this);
			}

			this.fireEvent('storefirstloaded', this);
			this.fireEvent('afterfirstload', this);
			if (this.storeLoadingListeners) {
				Ext.each(this.storeLoadingListeners, function(l) {l()});
			}
		}

		,initialisationValue: undefined

		,setValue: function(v, cb, scope) {
			var me = this;
			// prevent mark while we are waiting for the first load before
			// setting the value
			this.preventMark = true;
			this.initialisationValue = v;
			this.initialisationValueCallback = cb;
			this.initialisationValueCallbackScope = scope;
		}

		// Overidden, so that the store is not reloaded when a selection is
		// made in the already available data
		,onSelect : function(record, index){
			if(this.fireEvent('beforeselect', this, record, index) !== false){
//				this.setValue(record.data[this.valueField || this.displayField]);
				Oce.form.ForeignComboBox.superclass.setValue.call(this,
					record.data[this.valueField || this.displayField]
				);
				this.collapse();
				this.fireEvent('select', this, record, index);
			}
		}

		,setRowId: function(id) {
			this.rowId = id;
			if (this.store) {
				this.store.baseParams.rowId = id;
			}
		}

		,getParams: function(q) {
			var p = Oce.form.ForeignComboBox.superclass.getParams.call(this, q);

			if (this.rowId !== undefined) {
				p.rowId = this.rowId;
			}
			if (this.autoComplete !== undefined) {
				p.autoComplete = this.autoComplete;
			}

			return p;
		}

		// Fixing the rendering in deferred: false
		// http://www.sencha.com/forum/showthread.php?75035-CLOSED-3.0.0-
		// TriggerField-render-problem-in-Toolbar&highlight=card%20layout%20toolbar
		,onResize : function(w, h) {

			if (false === this.ownerCt instanceof Ext.Toolbar) {
				Oce.form.ForeignComboBox.superclass.onResize.apply(this, arguments);
			} else {
				// Temporarily ensure that the passed element (and all ancestors)
				// is display:block for the calling of the specified function
				function ensureLayout(elm, func, scope) {
					var r,
						e = Ext.get(elm),
						elmStyle = elm.style,
						oldDisp = elmStyle.display,
						wasXHidden = e.hasClass('x-hide-display');

					if (wasXHidden) {
						e.removeClass('x-hide-display');
					}
					elmStyle.display = "block";
					if (!elm.offsetWidth) {
						r = ensureLayout(elm.parentNode, func, scope);
					} else {
						r = func.call(scope || window);
					}
					elmStyle.display = oldDisp;
					if (wasXHidden) {
						e.addClass('x-hide-display');
					}
					return r;
				}

				ensureLayout(this.wrap.dom, function() {
//					Ext.form.TriggerField.superclass.onResize.call(this, w, h);
					Oce.form.ForeignComboBox.superclass.onResize.call(this, w, h);
					var tw = this.trigger.getWidth();
					if(typeof w == 'number'){
						if (this.adjustWidth) {
							this.el.setWidth(this.adjustWidth('input', w - tw));
						} else {
							this.el.setWidth(w - tw);
						}
					}
					this.wrap.setWidth(w);
				}, this);
			}
		}
	});

	Ext.reg('oce.foreigncombo', Oce.form.ForeignComboBox);

	Oce.deps.reg('Oce.form.ForeignComboBox');
})