Ext.ns("Oce");
// ----<<  FormPanel  >>--------------------------------------------------------
/**
 * @xtype oce.form
 */
Oce.FormPanel = Ext.extend(Ext.FormPanel, {

    idValue: undefined

    ,initFormItems: undefined

    ,constructor: function(config) {

		this.successCallbacks = [];
		this.failureCallbacks = [];

        this.addEvents({
            modificationcleared: true
            ,modified: true
        });

        config = Ext.applyIf(config || {}, {
             url:'api'
            ,bodyStyle: 'background:transparent'
            ,padding: 10
            ,border: false
            ,waitMsg: "Chargement des données" // i18n
            ,waitTitle: "Veuillez patientez" // i18n
            ,trackResetOnLoad: true
        });

        // --- Default Keys ---

        var keys = new Array();
        if (keys.length > 0) {
            if ("keys" in config) {
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

        // Mark the form as unmodified, after a successful submit action

        this.form.on({
            scope: this
            ,actioncomplete: function(form, action) {
                if (action.type === 'submit'
                        && action.result.success) {
                    this.clearModified();
                }
            }
        });

        var lu = this.tabsByName = {},
			slu = this.tabsBySlug = {};

        Ext.each(this.findBy(function(o) { return !Ext.isEmpty(o.tabName) }), function(item) {

			lu[item.tabName] = item;

			var slug = item.slug || item.tabName.toLowerCase().replace(/\s/g, '-');

			item.slug = slug;
			slu[slug] = item;
        });

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
                item.on('specialkey', l);
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

    // private
    ,formChangeListener: function() {
        var me = this,
            waitingToTest = this.waitingToTest,
            refreshDelay = this.refreshDelay;

        if (!me.preventModificationEvents) {
            // aggregate cumulated events
            if (waitingToTest) {
                return;
            }
            waitingToTest = true;
            if (me.modified) {
                setTimeout(function() {
                    if (!me.form.isDirty()) {
                        me.clearModified();
                    }
                    waitingToTest = false;
                }, refreshDelay);
            } else {
                // We must let the pass finish, in order for BasicForm to
                // restore the fields original values. Indeed, we can get
                // here with events fired from getValue() of the fields,
                // and BasicForm do as follow:
                // `
                //        f.setValue(v.value);
                //        if(this.trackResetOnLoad){
                //            f.originalValue = f.getValue();
                //        }
                //    `
                // bufferize simultaneous events
                setTimeout(function() {
                    // ... and here, ensure that the form would still be considered
                    // diry
                    if (me.form.isDirty()) {
                        me.modified = true;
                        me.fireEvent('modified');
                    }
                    waitingToTest = false;
                }, refreshDelay);
            }
        }
    }

    ,addFormChangeListeners: function() {
        this.waitingToTest = false,
        this.refreshDelay = 100;
        var changeListener = this.formChangeListener.createDelegate(this);
        var addChangeListener = function(item) {
            // Defaults on the change event to cover the max of corner cases
            item.on('change', changeListener);
            // But tries to find more appropriate events for specific types of
            // fields
            if (item instanceof Ext.form.TextField) {
                if (item.el) {
                    item.mon(item.el, {
                        buffer: 100
                        ,keyup: changeListener
                    });
                } else {
                    item.on('afterrender', function() {
                        addChangeListener(item);
                    });
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
//                item.on('sync', changeListener);
                item.on('change', changeListener);
            }
            if (item instanceof Ext.form.CompositeField) {
                item.items.each(addChangeListener);
            }
            if (item instanceof Ext.ux.form.SpinnerField) {
                item.on('spin', changeListener);
            }
            if (item instanceof Ext.form.RadioGroup) {
                // we've got a problem here... RadioGroup will bufferize the
                // events of its children for 10ms before firing its own change
                // event; as a consequences, this event won't be in the same
                // thread as the one launching the loading action of the
                // underlying form, and so it won't be blocked here. However, it
                // needs to be, in order to prevent the initial value setting of
                // the form, when it's loaded, to intenpestively trigger this
                // FormPanel's own modification event...
                // ... so, first, let's remove the faulty event listener
                item.un('change', changeListener);
                // ... and listen on the source instead!
                // (to make it even funnier, the eachItem method only works when
                // items have already been constructed)
                if (item.items && item.items.each) {
                    item.eachItem(function(item) {
                        item.on('check', changeListener);
                    });
                } else {
                    item.on({
                        single: true
                        ,afterrender: function(item) {
                            item.eachItem(function(item) {
                                item.on('check', changeListener);
                            });
                        }
                    });
                }
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
        };
        walkItems(this);
    }

    ,clearModified: function() {
        this.modified = false;
        // clear dirty mark on form items
        var clearItemDirty = function(item) {
            if (Ext.isFunction(item.clearDirty)) {
                item.clearDirty();
            }
            if (item instanceof Ext.form.CompositeField) {
                item.items.each(clearItemDirty);
            }
        };
        this.form.items.each(clearItemDirty);
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
        if (this.tabsByName[tabName]) {
            this.tabsByName[tabName].show();
        } else if (this.tabsBySlug[tabName]) {
			this.tabsBySlug[tabName].show();
		}
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

	/**
	 * Reloads data, but only if the initial load has already finished.
	 *
	 * @param {Function} [callback]
	 */
	,refresh: function(callback) {
		if (this.loaded) {
			this.load(callback);
		}
	}

	/**
	 * Reloads data, but only if the initial load has already finished.
	 *
	 * @param {Function} [callback]
	 */
	,load: function(callback) {

		if (callback) {
			if (Ext.isFunction(callback)) {
				this.successCallbacks.push(callback);
			} else {
				function bind(fn) {
					return callback.scope
						? Ext4.bind(fn, callback.scope)
						: fn;
				}
				if (callback.success) {
					this.successCallbacks.push(bind(callback.success));
				}
				if (callback.failture) {
					this.failureCallbacks.push(bind(callback.failure));
				}
			}
		}

		if (this.loading) {
			return;
		}

		var me = this,
        	win = this.win;

        var options = {
            url: 'api'
			,sourceComponent: win
            ,waitMsg: "Chargement des données" // i18n
            ,waitTitle: "Veuillez patientez" // i18n
            ,scope: this

			,callback: function() {
				this.loading = false;
			}

			,success: function(response, action, type) {
                win.formPages = action.result.pages;
                for (var i=0,l=win.formRefreshers.length; i<l; i++) {
                    win.formRefreshers[i]();
                }
                if (this.initFormItems) {
                    this.initFormItems(action.result);
                }
                // Only force refresh after initial load
                if (this.loaded) {
					// Load grid fields
					this.form.items.each(function(field) {
						if (field instanceof Oce.form.GridField) {
							field.load();
						}
					});
                }
                this.loaded = true;
				this.preventModificationEvents = false;
				this.clearModified();
                this.fireEvent('afterload', this.form, action.result, this);

				// Flush callbacks
				var form = this.form;
				this.successCallbacks.forEach(function(cb) {
					cb(form)
				}, this);
				this.successCallbacks = [];
				this.failureCallbacks = [];
			}

			,failure: function(form, action) {
				// Flush callbacks
				this.failureCallbacks.forEach(function(cb) {
					cb(form);
				});
				this.successCallbacks = [];
				this.failureCallbacks = [];
			}
        };

        var params = {
            controller: this.controller
            ,action: 'load_one'
        };
        params[win.pkName] = win.idValue;
        options.params = params;
        // 07/12/11 02:10 changed the signature of the event from
        // beforeLoad(params) to beforeLoad(formPanel, form, options), where
        // params === options.params
        this.fireEvent('beforeload', this, this.form, options);
        this.preventModificationEvents = true;
        this.form.load(options);
    }

    // Overridden to allow for JsonForm
    ,createForm: function() {
        var config = Ext.applyIf({listeners: {}}, this.initialConfig);
        if (config.jsonFormParam || config.serializeForm) {
            return new eo.form.JsonForm(null, config);
        } else {
            return new Ext.form.BasicForm(null, config);
        }
    }

    ,onRender:function() {
        // call parent
        Oce.FormPanel.superclass.onRender.apply(this, arguments);
        // set wait message target
        this.getForm().waitMsgTarget = this.getEl();
    }
}); // << FormPanel

Ext.reg('oce.form', 'Oce.FormPanel')

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
 * @cfg {Integer/String} spacing The spacing between each columns (default to
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
            layout: 'column'
            ,labelAlign: 'top'
            ,frame: true
            ,spacing: 10
            ,columns: 2
        }, config);
        delete cfg.cols;
        delete cfg.items;
        delete cfg.columns;
        var defaults = Ext.apply({
            xtype: 'container'
            ,layout: 'form'
        }, config.defaults);
        var cols = (function(){
            if (config.cols) {
                return config.cols;
            } else if (config.columns === undefined || !config.items) {
                throw new Error("Invalid config options: cols || (columns && items)");
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
        Ext.each(cols, function(col) {
            var items = col.items;
            for (var i=0,l=items.length; i<l; i++) {
                var item = items[i];
                if (item === ' ') {
                    items[i] = {
                        xtype: 'displayfield'
                        ,fieldLabel: ""
                        ,value: ""
                        ,hideLabel: true
                    }
                }
            }
        });
        var n = cols.length,
            items = [],
            spacing = cfg.spacing,
            percent = false;
        defaults.columnWidth = 1/n;
        if (Ext.isString(spacing)) {
            var m = /^(\d+)(%?)$/.exec(spacing);
            if (m === null) throw new Error("Invalid config option value for spacing: " + spacing);
            spacing = parseInt(m[1]);
            percent = !!(m[2]);
        }
        var lw = (100 - (n-1) * spacing) / n,
            ow = (100 - lw)/(n-1),
            anchor = (100 - spacing*n) + '%';
        lw /= 100;
        ow /= 100;
        for (var i=0,l=cols.length; i<l-1; i++) {
            var ct = Ext.apply({
                defaults: Ext.apply({
                    anchor: '100%'
                }, defaults.defaults)
                ,items: cols[i].items
            }, defaults);
            items.push(ct);
            if (percent) {
                items.push({
                    xtype: 'container'
                    ,columnWidth: spacing
                    ,html: '&nbsp;'
                });
            } else {
                items.push({
                    xtype: 'container'
                    ,width: spacing
                    ,html: '&nbsp;'
                });
            }
        }
        // last col
        items.push(Ext.apply({
            defaults: Ext.apply({
                anchor: '100%'
            }, defaults.defaults)
            ,items: cols[cols.length - 1].items
        }, defaults));
        cfg.items = items;
        Oce.ColumnContainer.superclass.constructor.call(this, cfg);
    }
});
Ext.reg('colcontainer', 'Oce.ColumnContainer');
