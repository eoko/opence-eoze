Ext.ns('Oce');
Ext.ns('eo.window');

// ----<<  FormWindow  >>-------------------------------------------------------

/**
 * {@link Ext.Window Window} with extended behaviour.
 * 
 * Notably, this class fixes the crazy width bug that happens in Chrome with windows 
 * that contains buttons, and do not have a width set.
 * 
 * It also can set modal relative to another window (see {@link #setModal}).
 */
eo.Window = Ext.extend(Ext.Window, {

	/**
	 * @cfg {Boolean} [constrainHeader=true]
	 * @inheritdoc
	 */
	constrainHeader: true
	
	/**
	 * @cfg {String} [bodyStyle='padding:0']
	 * @inheritdoc
	 */
	,bodyStyle: 'padding:0'
	
	// private
	,constructor: function(config) {

		var me = this;

		// clone or set the config object
		var initialConfig = config || {};
		
		config = config || {};

		config.minimizable = config.minimizable !== false && this.minimizable !== false;

		this.addPlugins(config);

		eo.Window.superclass.constructor.call(this, config);
		
		// fix initialConfig
		this.initialConfig = initialConfig;

		if (this.modalTo) {
			this.setModalTo(this.modalTo);
		}
	}
	
	// private
	,initComponent: function() {
		
		// If an Ext.Window has buttons and no width set, then Chrome will incorectly
		// calculate an extravagant width...
		// Using autoWidth (from Ext.Panel, hidden in Ext.Window) will fix that, but
		// the behaviour of autoWidth is unwanted (it prevents user resizing of the window).
		var fixWidth = Ext.isChrome && this.buttons && true || false;
		if (fixWidth) {
			if (this.width || this.autoWidth) {
				fixWidth = false;
			} else {
				this.autoWidth = true;
			}
		}

		eo.Window.superclass.initComponent.call(this);

		// If the width fix has been applied, then:
		// - we remove autoWidth to stop it from preventing the user to resize
		// - we ensure the window has been size at least to minWidt
		if (fixWidth) {
			this.on({
				scope: this
				,afterrender: function() {
					delete this.autoWidth;
					// As a matter of fact, the minWidth will be calculated correclty,
					// event accounting for the buttons width. So, if a minWidth is set
					// or has appeared, it is a real must to apply it. Go.
					var mw = this.minWidth,
					// Don't know why but the fix may break the buttons footer width
					// (making it too small). Resizing the window fixes this.
						w = this.getWidth() + 1;
					// So, we always resize...
					this.setWidth(mw && mw > w && mw || w);
					// ... instead if resizing only if minWidth is not obtained
					// 
					// if (mw && this.getWidth() < mw) {
					//	this.setWidth(mw);
					// }
				}
			});
		}
	}
	
	/**
	 * When it exists in the relative window, this method is used by 
	 * {@link #setModal} to disable the relative window, instead of {@link #disable}. 
	 * 
	 * It may be overridden in children classes, or provided by any component, to 
	 * implement custom disabling behaviour. If this method exists in a component,
	 * then this component **must** also provide a {@link #activateContent} method, 
	 * in order to prevent random behaviour.
	 * 
	 * {@link eo.Window}'s implementation consists of disabling every button of the
	 * window, and every button of the {@link #getTopToolbar top toolbar} and of the 
	 * {@link #getBottomToolbar bottom toolbar}.
	 */
	,deactivateContent: function() {
		Ext.each(this.buttons, function(bt) {
			if (!bt.disabled) {
				bt.disable();
				bt.wasEnabled = true;
			}
		});
		
		// Toolbar buttons
		var tbar = this.getTopToolbar(),
			bbar = this.getBottomToolbar();
		// cannot use toolbar, 'cause the button container is a toolbar and it
		// is quite ugly when masked ...
		if ((tbar = tbar && tbar.el)) {
			tbar.mask();
		}
		if ((bbar = bbar && bbar.el)) {
			bbar.mask();
		}
	}

	/**
	 * When it exists in the relative window, this method is used by {@link #setModal} 
	 * to enable the relative window, instead of {@link #enable}.
	 * 
	 * It may be overridden in children classes, or provided by any component, to
	 * implement some custom behaviour.
	 */
	,activateContent: function() {
		Ext.each(this.buttons, function(bt) {
			if (bt.wasEnabled) {
				bt.enable();
				delete bt.wasEnabled;
			}
		});
		
		// Toolbar buttons
		var tbar = this.getTopToolbar(),
			bbar = this.getBottomToolbar();
		// cannot use toolbar, 'cause the button container is a toolbar and it
		// is quite ugly when masked ...
		if ((tbar = tbar && tbar.el)) {
			tbar.unmask();
		}
		if ((bbar = bbar && bbar.el)) {
			bbar.unmask();
		}
	}

	/**
	 * Sets the window modal to another {@link Ext.Window Window}. That is, when this
	 * window is visible, then the other window is applied a disabled mask.
	 * 
	 * If the other window has a {@link #deactivateContent} method, then it will be used
	 * instead of the {@link Ext.Window#disable} method. In that case, a matching
	 * {@link #activateContent} method **must** also be provided to prevent random
	 * behaviour.
	 * 
	 * @param {Ext.Component} relativeWindow The window (or in fact any wanted component) to
	 * deactivate when this window is visible.
	 */
	,setModalTo: function(relativeWindow) {

		var maskEl;
		var win = this;
//		var WIN_CLASS = Oce.FormWindow;
		var WIN_CLASS = Ext.Window;

		var uber = eo.Window.superclass,
			doHide = function() {uber.hide.apply(win, arguments)},
			doShow = function() {uber.show.apply(win, arguments)};

		if (relativeWindow) {
			maskEl = relativeWindow && relativeWindow.items && relativeWindow.items.first() || relativeWindow;
			if (maskEl) maskEl = maskEl.el;

			this.mon(relativeWindow, {
				scope: this
				,beforehide: function() {
					var v = !hidding;
					hidding = true;
					doHide();
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

			doShow();

			var el = win.el,
				x = el.getX(),
				y = el.getY(),
				h = 8, v = 6, d = .085;
				
			if (!el) return;

			el.stopFx();

			// OptionFX
			var next = function() {return el && !hidding;};
			
			el.moveTo(x, y-v, {
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

			win.el.frame(null, null, {
				duration: 3*d
			});
		}
		,onRootActivate = function() {
			onRootActivateFn.defer(50);
		};
		
		var initModal = function(force) {

			modal = true;

			if (this.isVisible() && !force) {
				doShow();
				return;
			}

			if (this.clearFormOnShow) {
				var bf = this.form;
				bf.clearInvalid();
				doShow();
				bf.reset();
			} else {
				doShow();
			}

			if (relativeWindow) {
				// win content
				if (maskEl) maskEl.mask();
				
				if (relativeWindow.deactivateContent) {
					relativeWindow.deactivateContent();
				} else {
					relativeWindow.disable();
				}
				relativeWindow.on('activate', onRootActivate);
			}
			hidding = false;
		};

		this.doShow = function() {
			initModal.call(this, false);
		};

		this.doHide = function() {

			modal = true;
			hidding = true;

			this.el.stopFx();

			if (relativeWindow) {
				// unmask win content
				if (maskEl) maskEl.unmask();
				
				if (relativeWindow.activateContent) {
					relativeWindow.activateContent();
				} else {
					relativeWindow.enable();
				}
				relativeWindow.un('activate', onRootActivate);
				doHide.apply(this, arguments);
				//maskEl.unmask();
				//rootWin.enable();
			}
		};
		
		if (this.isVisible()) {
			initModal.call(this, true);
		}
	}

	,doHide: function() {
		eo.Window.superclass.hide.apply(this, arguments);
	}

	,doShow: function() {
		eo.Window.superclass.show.apply(this, arguments);
	}
	
	,hide: function() {
		this.doHide.apply(this, arguments);
	}
	
	,show: function() {
		this.doShow();
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
	
	,initComponent: function() {

		// --- Items default ---
		var formPanel;
		if (this.formPanel) {
			if (this.formPanel instanceof Ext.Component) {
				formPanel = this.formPanel;
				if (formPanel.initialConfig.autoScroll === undefined) {
					formPanel.setAutoScroll(true);
				}
			} else {
//				Ext.applyIf(this.formPanel, {
//					autoScroll: true
//				});
//				formPanel = Ext.widget(this.formPanel);
				formPanel = Ext.widget(
					Ext.applyIf(this.formPanel, {
						autoScroll: true
					})
				);
			}

			this.items = [formPanel];
		}

		// If no default focus item is defined, set default focus on the first
		// field in the tabItem chain, or the first field of the form
		if (!this.defaultButton) {
			this.defaultButton = (function() {
				var dr = null;
				var r = formPanel.form.items.each(function(field) {
					
					if (field instanceof Ext.form.Hidden || field.hidden
							|| field instanceof Ext.form.DisplayField) {
						return undefined;
					}
					
					var i = field.tabIndex;
					if (i !== undefined) {
						if (i === 0) return false;
						if (!dr || !(dr.tabIndex < i)) dr = field;
					} else if (dr === null) {
						dr = field;
					}
					return undefined;
				});
				if (r) return r;
				else return dr;
			})();
		}

		// --- Reload tool ---
		// Refresh gear button

		// Implements the window refresh tool button, if not already set in the
		// config (in this case, we must find by its id to know...)
		this.tools = this.tools || [];
		if (this.refreshable) {
			this.tools.push({
				id: 'refresh'
				,handler: this.refresh.createDelegate(this)
			});
		}

		// --- Default config ---

		Ext.applyIf(this, {
			 closable: true
			,collapsible: false
			,loadMask: true
			,maximizable: true
		})


		// --- Fix crazy window auto width for Chrome ---

//		if (Ext.isChrome) {
//			Ext.applyIf(config, {
//				autoWidth: true
//			});
//		}
//
		Oce.FormWindow.superclass.initComponent.call(this);
//
//		if (Ext.isChrome) {
//			this.on('afterrender', function() {
//				me.setWidth(me.getWidth());
//			})
//		}

		if (formPanel.setWindow) formPanel.setWindow(this);
		
		// --- Shortcut accessors ---

		this.formPanel = formPanel;
		this.form = this.formPanel.form;
	}
	
	/**
	 * Focuses the window.  If a defaultButton is set, it will receive focus, otherwise the
	 * window itself will receive focus.
	 */
	,focus : function(){
		var f = this.focusEl,
		db = this.defaultButton,
		t = typeof db,
		el,
		ct;
//REM		if(Ext.isDefined(db)){
		if (db) {
			if(Ext.isNumber(db) && this.fbar){
				f = this.fbar.items.get(db);
			}else if(Ext.isString(db)){
				f = Ext.getCmp(db);
			}else{
				f = db;
			}
			el = f.getEl();
			ct = Ext.getDom(this.container);
			if (el && ct) {
				if (ct != document.body && !Ext.lib.Region.getRegion(ct).contains(Ext.lib.Region.getRegion(el.dom))){
					return;
				}
			}
		}
		f = f || this.focusEl;
		if (f instanceof Ext.form.ComboBox) {
			(function() {
				f.focus();
				f.hasFocus = false;
			}).defer(10);
		} else {
			f.focus.defer(10, f);
		}
	}
	
	,afterRender: function() {
		Oce.FormWindow.superclass.afterRender.apply(this, arguments);
		
		var sb = this.getSubmitButton();
		if (sb) {
			sb.addClass("default");
		}
	}
	
	,initEvents: function() {
		Oce.FormWindow.superclass.initEvents.call(this);
		
		// Handle default (submit) button on ENTER key press
		var sh, scope;
		if (this.submitHandler) {
			sh = this.submitHandler;
			scope = this.scope || this;
		} else {
			var sb = this.getSubmitButton();
			if (sb) {
				sh = function() {
					if (sb && !sb.disabled) {
						sb.handler.call(this);
					}
				}
				scope = sb.scope || sb;
			}
		}
		
		if (sh) {
			var km = this.getKeyMap();
			km.on([10,13], function() {
				if (!this.enterDisabled) sh.call(scope);
			}, this);
			km.on({key: [10, 13], ctrl: true}, sh, scope);
			km.disable();
		}
	}
	
	,onEsc: function() {
		var h, scope;
		if (this.cancelHandler) {
			h = this.cancelHandler;
			scope = this.scope || this;
		} else {
			var b = this.getCancelButton();
			if (b) {
				h = b.handler;
				scope = b.scope || b;
			}
		}
		if (h) {
			h.call(scope);
		} else {
			Oce.FormWindow.superclass.onEsc.apply(this, arguments);
		}
	}
	
	,getCancelButton: function() {
		return this.getPropertyButton(this.cancelButton);
	}
	
	,getSubmitButton: function() {
		return this.getPropertyButton(this.submitButton);
	}
		
	// private
	,getPropertyButton: function(p) {
        if(Ext.isDefined(p)){
            if(Ext.isNumber(p) && this.fbar){
                return this.fbar.items.get(p);
            }else if(Ext.isString(p)){
                return Ext.getCmp(p);
            }else{
                return p;
            }
        }
		return null;
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

Oce.deps.reg('Oce.FormWindow');

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

		win.afterRender = Ext.Function.createSequence(win.afterRender, function() {
			taskbar.addWindow(this);
		});
	}
});

// Overrides TextArea to allow ENTER key to be used for newline, instead of
// submiting the window
(function() {
	var spp = Ext.form.TextArea.prototype,
		onFocus = spp.onFocus,
		onBlur = spp.onBlur,
		fpb = function(c) {return c instanceof Oce.FormWindow};
	Ext.apply(spp, {
		onFocus: function() {
			var p = this.findParentBy(fpb);
			if (p) p.enterDisabled = true;
			onFocus.apply(this, arguments);
		}
		,onBlur: function() {
			var p = this.findParentBy(fpb);
			if (p) delete p.enterDisabled;
			onBlur.apply(this, arguments);
		}
	});
})();
