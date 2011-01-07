Ext.ns('Oce');
Ext.ns('eo.window');

// ----<<  FormWindow  >>-------------------------------------------------------

eo.Window = Ext.extend(Ext.Window, {

	constructor: function(config) {

		var me = this;

		// clone the config object, and applies defaults
		config = Ext.apply({
			constrainHeader: true
			,bodyStyle: 'padding:0'
		}, config);

//		config.minimizable = true;
		config.minimizable = config.minimizable !== false;

		this.addPlugins(config);

		if (Ext.isChrome) {
			Ext.applyIf(config, {
				autoWidth: true
			});
		}

		eo.Window.superclass.constructor.call(this, config);

		if (Ext.isChrome) {
			this.on('afterrender', function() {
				me.setWidth(me.getWidth());
			})
		}
//		eo.Window.superclass.constructor.call(this, config);

		if (this.modalTo) {
			this.setModalTo(this.modalTo);
		}
	}

	,setModalTo: function(rootWin) {

		var maskEl;
		var win = this;
		var WIN_CLASS = Oce.FormWindow;

		if (rootWin) {
			maskEl = rootWin && rootWin.items.first();
			if (maskEl) maskEl = maskEl.el;

			this.mon(rootWin, {
				scope: this
				,beforehide: function() {
					var v = !hidding;
					hidding = true;
					WIN_CLASS.prototype.hide.call(this);
					modal = v;
				}
				,show: function() {
					if (modal) this.show();
				}
			});
		}

		var hidding = false // this one's to prevent activate event from being process on win hide
		,modal = false // used to control that when the rootWin is restored, the win
		//is displayed only if it was visible when the rootWin was hidden
		,onRootActivateFn = function() {

			if (hidding) return;

			var el = win.el,
				x = el.getX(),
				y = el.getY(),
				h = 8, v = 6, d = .085;

			el.stopFx();

			// OptionFX
			var next = function() { return el && !hidding; };

			if (next) WIN_CLASS.prototype.show.call(win);

			if (next()) el.moveTo(x, y-v, {
				duration: d
				,callback: function() {
					if (next()) el.moveTo(x,y+v/4, {
						duration: d
						,easing: "easeOut"
						,callback: function() {
							if (next()) el.moveTo(x, y-v*.75, {
								duration: d
								,easing: "easeOut"
								,callback: function() {
									if (next()) el.moveTo(x, y, {
										duration: d
										,callback: function() {
											if (next()) win.focus();
										}
									})
								}
							})
						}
					})
				}
			});

			if (next()) win.el.frame(null, null, {
				duration: 3*d
			});
		}
		,onRootActivate = function() {
			onRootActivateFn.defer(50);
		};

		this.show = function() {

			modal = true;

			if (this.isVisible()) {
				WIN_CLASS.prototype.show.apply(this, arguments);
				return;
			}

//			var bf = form.form;
			var bf = this.form;
			bf.clearInvalid();
			Oce.FormWindow.prototype.show.apply(this, arguments);
			bf.reset();

			if (rootWin) {
				if (rootWin.deactivateContent) {
					rootWin.deactivateContent();
				}
				if (maskEl) maskEl.mask();
				rootWin.on('activate', onRootActivate);
			}
			hidding = false;
		}

		this.hide = function() {

			modal = true;
			hidding = true;

			this.el.stopFx();

			if (rootWin) {
				if (rootWin.activateContent) {
					rootWin.activateContent();
				}
				rootWin.un('activate', onRootActivate);
				WIN_CLASS.prototype.hide.apply(this, arguments);
				maskEl.unmask();
			}
		}
	}

	,addPlugins: function(config) {
		if (!config.plugins) config.plugins = [];
		if (config.minimizable !== false) {
			config.plugins.push(new eo.Window.MinimizePlugin);
		}
	}
});

Oce.deps.reg('eo.Window');

Oce.FormWindow = Ext.extend(eo.Window, {

	formRefreshers: []
	,formPages: null // wait events
	,pkName: 'id'

	,constructor: function(config) {

		var me = this;

		// --- Items default ---
		var formPanel;
		if (config.formPanel) {
			if (config.formPanel instanceof Ext.Component) {
				formPanel = config.formPanel;
				if (formPanel.initialConfig.autoScroll === undefined) {
					formPanel.setAutoScroll(true);
				}
			} else {
//				Ext.applyIf(config.formPanel, {
//					autoScroll: true
//				});
//				formPanel = Ext.create(config.formPanel);
				formPanel = Ext.create(
					Ext.applyIf(config.formPanel, {
						autoScroll: true
					})
				);
			}

			config.items = [formPanel];
		}

//REM (must be done after the window exists!!!)		formPanel.setWindow(this);

		// --- Reload tool ---
		// Refresh gear button

		// Implements the window refresh tool button, if not already set in the
		// config (in this case, we must find by its id to know...)
// Hardly ever useful... Keep some time to be sure it was not used, however
//		if (config.tools !== undefined) {
//			var ids = Ext.pluck(config.tool, 'id');
//			var free = true;
//			for (var i=0,l=ids; i<l; i++) {
//				if (ids[i] === 'refresh') {
//					free = false;
//					break;
//				}
//			}
//			if (free) config.tools.push({
//				id: 'refresh'
//				,handler: this.refresh.createDelegate(this)
//			});
//		} else {
//			config.tools = [];
//			config.tools = [{
//				id: 'refresh'
//				,handler: this.refresh.createDelegate(this)
//			}];
//		}
		config.tools = config.tools || [];
		if (config.refreshable) {
			config.tools.push({
				id: 'refresh'
				,handler: this.refresh.createDelegate(this)
			});
		}

		// --- Default config ---

		Ext.applyIf(config, {
			 closable: true
			,collapsible: false
			,loadMask: true
//			,constrainHeader: true
//			,bodyStyle: 'padding:0'
			,maximizable: true
//			,layout:'fit'
//			,closeAction: 'hide'
		})


		// --- Fix crazy window auto width for Chrome ---

//		if (Ext.isChrome) {
//			Ext.applyIf(config, {
//				autoWidth: true
//			});
//		}
//
		Oce.FormWindow.superclass.constructor.call(this, config)
//
//		if (Ext.isChrome) {
//			this.on('afterrender', function() {
//				me.setWidth(me.getWidth());
//			})
//		}

		formPanel.setWindow(this);
		
		// --- Shortcut accessors ---

		this.formPanel = formPanel;
		this.form = this.formPanel.form;
	}

	,disable: function() {
		var oldWidth = this.getWidth();
		this.setWidth(oldWidth);
		Oce.FormWindow.superclass.disable.apply(this, arguments);
	}

	,getForm: function() {return this.form;}
	,getFormPanel: function() {return this.formPanel;}
	,getRowId: function() {return this.idValue;}

	/**
	 * @param {boolean} force if set to TRUE, the beforerefresh event won't be
	 * fired, so external observers won't be given the opportunity to cancel the
	 * refreshing.
	 */
	,refresh: function(force) {
		force = force === true;
		var me = this;
		var doRefresh = function() {
			me.items.each(function(item) {
				if (Ext.isFunction(item.refresh)) {
					item.refresh();
				}
			});
		}
		if (force || this.fireEvent('beforerefresh', this, doRefresh) !== false) {
			doRefresh();
		}
	}

	,setRow: function(row) {

		var rowId = this.rowId = this.idValue
			= row.data[this.pkName];

		this.form.items.each(function(item) {
			if (item instanceof Oce.form.ForeignComboBox
				|| item instanceof Oce.form.GridField) {

				if (item.idField) {
					item.setRowId(row.data[item.idField], true);
				} else {
					item.setRowId(rowId, true);
				}
			}
		})
	}

	,setRowId: function(rowId) {
		this.rowId = this.idValue = rowId;
		this.formPanel.initFormItems = this.setRow.createDelegate(this);
	}

});

eo.Window.MinimizePlugin = eo.Object.create({

	init: function(win) {
		var taskbar = Oce.mx.TaskBar;
		if (!taskbar) return;

		Ext.each(win.tools, function(tool) {
			if (tool.id === "minimize") {
				tool.handler = function() {
					win.minimize()
				};
			}
		});

		win.afterRender = win.afterRender.createSequence(function() {
			taskbar.addWindow(this);
		});
	}
});