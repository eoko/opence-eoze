(function() {

var CollisionError = Ext.ns("eo.errors").OverrideCollisionError = function(prop) {
	return new Error("Application overrides unexpected conflict with an existing "
		+ "property of Ext: "+ prop + ". The override has been discarded. That "
		+ "should be considered a high priority issue to inspect the Ext API "
		+ "and adapt this override.");
}
//if (Ext.Component.prototype.initPlugins) {
//	throw new CollisionError("Ext.Component.initPlugins");
//} else {
//	var uber = Ext.Component.prototype.initComponent;
//	Ext.Component.prototype.initComponent = function() {
//		if (this.initPlugins) {
//			if (!this.plugins) this.plugins = [];
//			this.initPlugins();
//			if (!this.plugins.length) delete this.plugins;
//		}
//		uber.call(this);
//	};
//}

})(); // closure

// default label separator (French style)
Ext.layout.FormLayout.prototype.labelSeparator = "&nbsp;:";

if (false) {
Ext.data.JsonStore.prototype.loadRecords = function(o, options, success){
	var i, len;

	if (this.isDestroyed === true) {
		return;
	}
	if(!o || success === false){
		if(success !== false){
			this.fireEvent('load', this, [], options);
		}
		if(options.callback){
			options.callback.call(options.scope || this, [], options, false, o);
		}
		return;
	}
	var finish = function() {
		this.fireEvent('load', this, r, options);
		if(options.callback){
			options.callback.call(options.scope || this, r, options, true);
		}
	}.createDelegate(this);
	var r = o.records, t = o.totalRecords || r.length;
	if(!options || options.add !== true){
		if(this.pruneModifiedRecords){
			this.modified = [];
		}
		for(i = 0, len = r.length; i < len; i++){
			r[i].join(this);
		}
		if(this.snapshot){
			this.data = this.snapshot;
			delete this.snapshot;
		}
		this.clearData();
		this.data.addAll(r);
		this.totalLength = t;
		this.applySort();
		this.fireEvent('datachanged', this);
		finish();
	}else{
		var toAdd = [],
		rec,
		cnt = 0;
		for(i = 0, len = r.length; i < len; ++i){
			rec = r[i];
			if(this.indexOfId(rec.id) > -1){
				this.doUpdate(rec);
			}else{
				toAdd.push(rec);
				++cnt;
			}
		}
		this.totalLength = Math.max(t, this.data.length + cnt);

		var slices = [];

		var sl = 2;
		for (i=0, len=toAdd.length/sl; i<len; i++) {
			slices.push(toAdd.slice(i*sl, i*sl+sl));
		}

		var add = this.add.createDelegate(this);
		var me = this;
		var process = function() {
			var slice = slices.shift();
			add(slice);
			// update
			me.fireEvent('load', me, slice, options);
//			if(options.callback){
//				options.callback.call(options.scope || me, slice, options, true);
//			}
			// continue
			if (slices.length) {
				process.defer(50);
			} else {
				if(options.callback){
					options.callback.call(options.scope || me, slice, options, true);
				}
			}
		};
		process();

//		debugger
//
//		this.add(toAdd);
	}
};

}

Ext.override(Ext.Panel, {

	/**
	 * Hide the panel border, after it has been rendered.
	 */
	hideBorders: function() {
		if (this.border === false) return;
		this.border = false;
		if (!this.el) return;
		this.el.addClass(this.baseCls + '-noborder');
		this.body.addClass(this.bodyCls + '-noborder');
		if(this.header){
			this.header.addClass(this.headerCls + '-noborder');
		}
		if(this.footer){
			this.footer.addClass(this.footerCls + '-noborder');
		}
		if(this.tbar){
			this.tbar.addClass(this.tbarCls + '-noborder');
		}
		if(this.bbar){
			this.bbar.addClass(this.bbarCls + '-noborder');
		}
	}

	/**
	 * Hide the panel border, after it has been rendered.
	 */
	,showBorders: function() {
		if (this.border !== false) return;
		this.border = true;
		if (!this.el) return;
		this.el.removeClass(this.baseCls + '-noborder');
		this.body.removeClass(this.bodyCls + '-noborder');
		if(this.header){
			this.header.removeClass(this.headerCls + '-noborder');
		}
		if(this.footer){
			this.footer.removeClass(this.footerCls + '-noborder');
		}
		if(this.tbar){
			this.tbar.removeClass(this.tbarCls + '-noborder');
		}
		if(this.bbar){
			this.bbar.removeClass(this.bbarCls + '-noborder');
		}
	}
});


// Fixes negative dates...
(function() {
	var uber = Ext.form.DateField.prototype.parseDate;
	Ext.form.DateField.prototype.parseDate = function(value) {
		if (value === "0000-00-00") {
			return uber.call(this, null);
		} else {
			return uber.call(this, value);
		}
	};
})();


// Add setEnabled && setVisible function to ext components
(function() {
var spp = Ext.Component.prototype,
	enable = spp.enable,
	disable = spp.disable;
Ext.override(Ext.Component, {

	enable: function() {
		// fixes a bug that might happen if the component has already been destroyed
		var rendered = this.rendered;
		this.rendered = !!this.el && !!this.el.dom;
		enable.apply(this, arguments);
		this.rendered = rendered;
	}
	
	,disable: function() {
		// fixes a bug that might happen if the component has already been destroyed
		var rendered = this.rendered;
		this.rendered = !!this.el && !!this.el.dom;
		disable.apply(this, arguments);
		this.rendered = rendered;
	}
	
	,setEnabled: function(enabled) {
		if (enabled) {
			this.enable();
		} else {
			this.disable();
		}
	}
	
	,setVisible: function(visible) {
		if (visible) {
			this.show();
		} else {
			this.hide();
		}
	}
});
})(); // closure


// Add a change event to HtmlEditor
(function() {
	var uber = Ext.form.HtmlEditor.prototype.initComponent;
	Ext.override(Ext.form.HtmlEditor, {

		initComponent: function() {
			uber.apply(this, arguments);
			var lastValue = this.getValue();
			this.on({
				sync: {
					fn: function(me, html) {
						if (!lastValue) {
							lastValue = html;
							return;
						}
						if (lastValue !== html) {
							lastValue = html;
							this.fireEvent("change", this, html);
						}
					}
					,buffer: 200
				}
			});
		}
	});
})();


// Fix DateColumn rendering
// 
// There is a problem with editable DateColumns... The DateField value is
// retrieved according to its format configuration. The DateColumn, though,
// uses Ext.util.Format.dateRenderer(this.format) to set its renderer. That's
// ok for display, since the DateColumn's this.format is effectively the format
// intended for display; however, no input format can be set...
// 
// Ext.util.Format.dateRenderer will, in turn, use Ext.util.Format.date() to
// convert the submitted value. If the value submitted to this function is
// not a Date object, it will convert it to a Date using the _native_ 
// Date.parse() method!!! This native function doesn't account for localisation
// at all, and so produce akward value in many cases...
// 
// The solution implemented here uses an additionnal inputFormat for DateColumn,
// or it parses submitted value with its display format configuration... That is
// not perfect; the solution should probably take place at the level of the
// communication between the form.Field used as editor and the DateColumn.
// Unfortunatly, I do not have time to investigate any further :-/
//
Ext.grid.Column.types.datecolumn =
	Ext.grid.DateColumn = Ext.extend(Ext.grid.Column, {

	format : 'd/m/Y',
	constructor: function(cfg){
		Ext.grid.DateColumn.superclass.constructor.call(this, cfg);
//		this.renderer = Ext.util.Format.dateRenderer(this.format);
		var me = this;
		this.renderer = function(v) {
			if (!v) return "";
			var format = me.inputFormat || me.format || "d/m/Y";
			if (!(v instanceof Date)) {
				// instead of:
				// Date.parse(v)
				// which doesn't account for various possible formats
				var tmp = Date.parseDate(v, format);
				if (tmp) v = tmp;
				else v = new Date(Date.parse(v));
			}
			return v.dateFormat(format);
		}
	}
});

(function() {
	
	var uber = Ext.form.CheckboxGroup.prototype.initComponent;
	
	var calcColumns = function() {
		var cols, max;
		
		cols = this.rowColumns;
		
		if (Ext.isNumber(cols)) {
			this.columns = cols;
			return;
		}
		
		max = Math.max.apply(Math, cols);
		
		this.columns = max;

		// We don't want to modify original configuration
		var mi = [];
		Ext.each(this.items, function(item) {
			mi.push(item instanceof Ext.Component ? item : Ext.apply({}, item));
		});

		var items = [];
		Ext.each(cols, function(l) {
			items = items.concat(mi.splice(0, l));
			if (mi.length) {
				for (var i=0; i<max-l; i++) {
					items.push({xtype:'container', cls: 'x-form-item x-form-check-wrap'}); // spacer
				}
			}
		});
		
		this.items = items;
	};

	/**
	 * @cfg {Array/Integer} rowColumns 
	 * 
	 * **Added by Éric O. (Eoko)**
	 * 
	 * The number of items to be placed on each row; spacers will be added at the 
	 * end of rows that haven't the max number of items.
	 */
	Ext.form.CheckboxGroup.prototype.initComponent = function() {
		if (this.rowColumns) {
			calcColumns.call(this);
		}
		uber.call(this);
	};

	/**
	 * @member Ext.form.CheckboxGroup
	 * @cfg {Object} columnCtConfig 
	 * 
	 * **Added by Éric O. (Eoko)**
	 * 
	 * A config object that will override the default configuration of the 
	 * {@link Ext.Container} created to materialize the columns configured with 
	 * the {@link #columns} option.
	 */
	Ext.form.CheckboxGroup.prototype.onRender = function(ct, position) {
        if(!this.el){
            var panelCfg = {
                autoEl: {
                    id: this.id
                },
                cls: this.groupCls,
                layout: 'column',
                renderTo: ct,
                bufferResize: false // Default this to false, since it doesn't really have a proper ownerCt.
            };
            var colCfg = Ext.apply({
                xtype: 'container',
                defaultType: this.defaultType,
                layout: 'form',
                defaults: {
                    hideLabel: true,
                    anchor: '100%'
                }
            }, this.columnCtConfig);

            if(this.items[0].items){

                // The container has standard ColumnLayout configs, so pass them in directly

                Ext.apply(panelCfg, {
                    layoutConfig: {columns: this.items.length},
                    defaults: this.defaults,
                    items: this.items
                });
                for(var i=0, len=this.items.length; i<len; i++){
                    Ext.applyIf(this.items[i], colCfg);
                }

            }else{

                // The container has field item configs, so we have to generate the column
                // panels first then move the items into the columns as needed.

                var numCols, cols = [];

                if(typeof this.columns == 'string'){ // 'auto' so create a col per item
                    this.columns = this.items.length;
                }
                if(!Ext.isArray(this.columns)){
                    var cs = [];
                    for(var i=0; i<this.columns; i++){
                        cs.push((100/this.columns)*.01); // distribute by even %
                    }
                    this.columns = cs;
                }

                numCols = this.columns.length;

                // Generate the column configs with the correct width setting
                for(var i=0; i<numCols; i++){
                    var cc = Ext.apply({items:[]}, colCfg);
                    cc[this.columns[i] <= 1 ? 'columnWidth' : 'width'] = this.columns[i];
                    if(this.defaults){
                        cc.defaults = Ext.apply(cc.defaults || {}, this.defaults);
                    }
                    cols.push(cc);
                };

                // Distribute the original items into the columns
                if(this.vertical){
                    var rows = Math.ceil(this.items.length / numCols), ri = 0;
                    for(var i=0, len=this.items.length; i<len; i++){
                        if(i>0 && i%rows==0){
                            ri++;
                        }
                        if(this.items[i].fieldLabel){
                            this.items[i].hideLabel = false;
                        }
                        cols[ri].items.push(this.items[i]);
                    };
                }else{
                    for(var i=0, len=this.items.length; i<len; i++){
                        var ci = i % numCols;
                        if(this.items[i].fieldLabel){
                            this.items[i].hideLabel = false;
                        }
                        cols[ci].items.push(this.items[i]);
                    };
                }

                Ext.apply(panelCfg, {
                    layoutConfig: {columns: numCols},
                    items: cols
                });
            }

            this.panel = new Ext.Container(panelCfg);
            this.panel.ownerCt = this;
            this.el = this.panel.getEl();

            if(this.forId && this.itemCls){
                var l = this.el.up(this.itemCls).child('label', true);
                if(l){
                    l.setAttribute('htmlFor', this.forId);
                }
            }

            var fields = this.panel.findBy(function(c){
                return c.isFormField;
            }, this);

            this.items = new Ext.util.MixedCollection();
            this.items.addAll(fields);
        }
        Ext.form.CheckboxGroup.superclass.onRender.call(this, ct, position);
    };
	
})();

/**
 * @author Éric Ortéga <eric@planysphere.fr>
 * @since 29/04/11 17:10
 * 
 * Allow custom xtype for the PagingToolbar.
 */
//
//Ext.override(Ext.form.ComboBox, {
//	
//	initList : function(){
//		if(!this.list){
//			var cls = 'x-combo-list',
//			listParent = Ext.getDom(this.getListParent() || Ext.getBody());
//
//			this.list = new Ext.Layer({
//				parentEl: listParent,
//				shadow: this.shadow,
//				cls: [cls, this.listClass].join(' '),
//				constrain:false,
//				zindex: this.getZIndex(listParent)
//			});
//
//			var lw = this.listWidth || Math.max(this.wrap.getWidth(), this.minListWidth);
//			this.list.setSize(lw, 0);
//			this.list.swallowEvent('mousewheel');
//			this.assetHeight = 0;
//			if(this.syncFont !== false){
//				this.list.setStyle('font-size', this.el.getStyle('font-size'));
//			}
//			if(this.title){
//				this.header = this.list.createChild({
//					cls:cls+'-hd', 
//					html: this.title
//					});
//				this.assetHeight += this.header.getHeight();
//			}
//
//			this.innerList = this.list.createChild({
//				cls:cls+'-inner'
//				});
//			this.mon(this.innerList, 'mouseover', this.onViewOver, this);
//			this.mon(this.innerList, 'mousemove', this.onViewMove, this);
//			this.innerList.setWidth(lw - this.list.getFrameWidth('lr'));
//
//			if(this.pageSize){
//				this.footer = this.list.createChild({
//					cls:cls+'-ft'
//					});
//				// <rx+>
//				var xtype = this.pagingToolbarXtype || "paging";
//				if (xtype.xtype) xtype = xtype.xtype;
//				this.pageTb = Ext.widget({
//					xtype: xtype,
//					store: this.store,
//					pageSize: this.pageSize,
//					renderTo:this.footer
//				});
//				// </rx+>
//				// <rx->
//				// this.pageTb = new Ext.PagingToolbar({
//				// 	store: this.store,
//				// 	pageSize: this.pageSize,
//				// 	renderTo:this.footer
//				// });
//				// <rx->
//				this.assetHeight += this.footer.getHeight();
//			}
//
//			if(!this.tpl){
//                
//				this.tpl = '{' + this.displayField + '}';
//			}
//            
//			/**
//            * The {@link Ext.DataView DataView} used to display the ComboBox's options.
//            * @type Ext.DataView
//            */
//			this.view = new Ext.DataView({
//				applyTo: this.innerList,
//				tpl: this.tpl,
//				singleSelect: true,
//				selectedClass: this.selectedClass,
//				itemSelector: this.itemSelector || '.' + cls + '-item',
//				emptyText: this.listEmptyText,
//				deferEmptyText: false
//			});
//
//			this.mon(this.view, {
//				containerclick : this.onViewClick,
//				click : this.onViewClick,
//				scope :this
//			});
//
//			this.bindStore(this.store, true);
//
//			if(this.resizable){
//				this.resizer = new Ext.Resizable(this.list,  {
//					pinned:true, 
//					handles:'se'
//				});
//				this.mon(this.resizer, 'resize', function(r, w, h){
//					this.maxHeight = h-this.handleHeight-this.list.getFrameWidth('tb')-this.assetHeight;
//					this.listWidth = w;
//					this.innerList.setWidth(w - this.list.getFrameWidth('lr'));
//					this.restrictHeight();
//				}, this);
//
//				this[this.pageSize?'footer':'innerList'].setStyle('margin-bottom', this.handleHeight+'px');
//			}
//		}
//	}
//});
	
Ext.form.ComboBox.override({
	
	initList: function() {
		if(!this.list){
			var cls = 'x-combo-list',
			listParent = Ext.getDom(this.getListParent() || Ext.getBody());

			this.list = new Ext.Layer({
				parentEl: listParent,
				shadow: this.shadow,
				cls: [cls, this.listClass].join(' '),
				constrain:false,
				zindex: this.getZIndex(listParent)
			});

			var lw = this.listWidth || Math.max(this.wrap.getWidth(), this.minListWidth);
			this.list.setSize(lw, 0);
			this.list.swallowEvent('mousewheel');
			this.assetHeight = 0;
			if(this.syncFont !== false){
				this.list.setStyle('font-size', this.el.getStyle('font-size'));
			}

			// <rx> Allow toolbar
			var tb = this.toolbar;
			if (tb) {
				this.header = this.list.createChild({cls:cls+'-hd-toolbar', html: this.title});
				// required for the header height to be taken into account by expand()
				this.title = true;
				if (!(tb instanceof Ext.Component)) {
					// convert array
					if (Ext.isArray(tb)) {
						tb = {
							items: tb
						};
					}
					// create component
					this.toolbar = Ext.widget(Ext.apply({
						xtype: 'toolbar'
						,renderTo: this.header
					}, tb));
				} else {
					// render
					tb.render(this.header);
				}
				this.assetHeight += this.header.getHeight();
			} else
			// </rx>
			if(this.title){
				this.header = this.list.createChild({cls:cls+'-hd', html: this.title});
				this.assetHeight += this.header.getHeight();
			}

			this.innerList = this.list.createChild({cls:cls+'-inner'});
			this.mon(this.innerList, 'mouseover', this.onViewOver, this);
			this.mon(this.innerList, 'mousemove', this.onViewMove, this);
			this.innerList.setWidth(lw - this.list.getFrameWidth('lr'));

			if(this.pageSize){
				this.footer = this.list.createChild({cls:cls+'-ft'});
				this.pageTb = new Ext.PagingToolbar({
					store: this.store,
					pageSize: this.pageSize,
					renderTo:this.footer
				});
				this.assetHeight += this.footer.getHeight();
			}

			if(!this.tpl){
				this.tpl = '<tpl for="."><div class="'+cls+'-item">{' + this.displayField + '}</div></tpl>';
			}

			this.view = new Ext.DataView({
				applyTo: this.innerList,
				tpl: this.tpl,
				singleSelect: true,
				selectedClass: this.selectedClass,
				itemSelector: this.itemSelector || '.' + cls + '-item',
				emptyText: this.listEmptyText,
				deferEmptyText: false
			});

			this.mon(this.view, {
				containerclick : this.onViewClick,
				click : this.onViewClick,
				scope :this
			});

			this.bindStore(this.store, true);

			if(this.resizable){
				this.resizer = new Ext.Resizable(this.list,  {
					pinned:true, handles:'se'
				});
				this.mon(this.resizer, 'resize', function(r, w, h){
					this.maxHeight = h-this.handleHeight-this.list.getFrameWidth('tb')-this.assetHeight;
					this.listWidth = w;
					this.innerList.setWidth(w - this.list.getFrameWidth('lr'));
					this.restrictHeight();
				}, this);

				this[this.pageSize?'footer':'innerList'].setStyle('margin-bottom', this.handleHeight+'px');
			}
		}
	}

}); // Ext.form.ComboBox.override


// Fix Ext.Element.getValue, that can crash because of a delayed event, that is
// the dom element value can be required after the DOM element has been deleted...
(function() {
	var spp = Ext.Element.prototype,
		hasClass = spp.hasClass,
		addClass = spp.addClass;
Ext.override(Ext.Element, {
	
	getValue: function(asNumber) {
		var d = this.dom;
		if (!d) return asNumber ? parseInt("") : "";
		var val = d.value;
		return asNumber ? parseInt(val, 10) : val;
	}
	
	,addClass: function() {
		if (!this.dom) {
			return this;
		}
		return addClass.apply(this, arguments);
	}
	
	,hasClass: function() {
		if (!this.dom) {
			return false;
		}
		return hasClass.apply(this, arguments);
	}
});
})(); // closure

Ext.form.TextField.prototype.selectOnFocus = true;

// Overrides Ext.form.TextField#filterValidation to stop if the dom element
// doesn't exist anymore.
(function(uber) {
	Ext.override(Ext.form.TextField, {
		filterValidation: function() {
			var dom = this.el && this.el.dom;
			if (dom) {
				uber.apply(this, arguments);
			}
		}
	});
})(Ext.form.TextField.prototype.filterValidation);

/**
 * @todo Check other browser (safari, probably...)
 * With chrome, for a reason I don't get, the wrapping .x-form-element div
 * is not given the same height as the wrapped textarea, but a greater height,
 * that may leads to layout issues.
 */
if (Ext.isChrome) {
	(function() {
		var spp = Ext.form.TextArea.prototype;
		spp.afterRender = Ext.Function.createSequence(spp.afterRender, function() {
			var el = this.el.up('.x-form-element');
			if (el) {
				el.setHeight(this.getHeight());
			}
		});
	})();
}

/**
 * Overrides ButtonGroup to make it hidden by default if it contains 0 items.
 */
(function() {

var sp = Ext.ButtonGroup,
	spp = sp.prototype,
	uber = spp.initComponent;
	
spp.initComponent = function() {
	if (!this.items.length) {
		this.hidden = true;
	}
	uber.call(this);
};
	
})(); // closure

/**
 * Overrides Button to prevent error that can happen if the button is destroyed super 
 * fast (which can happen, because Ext internally set a 60ms defered focus on windows
 * default button).
 */
Ext.Button.prototype.focus = function () {
	if (this.btnEl) {
		this.btnEl.focus();
	}
};

Ext.form.DisplayField.prototype.submitValue = false;

// Overriding Hidden.setValue(), to prevent getValue() from returning strings "true"
// or "false".
(function() {
	
var sp = Ext.form.Hidden,
	spp = sp.prototype,
	uber = spp.setValue;
	
spp.setValue = function(v) {
	if (Ext.isBoolean(v)) {
		v = v ? 1 : 0;
	}
	uber.call(this, v);
};
	
})();

// Override to test that el & el.dom still exist at the time the method is called
// (which is not guaranteed, since in ext it is wrapped in a DelayedTask).
if (Ext.form.MessageTargets) {
	Ext.form.MessageTargets.qtip.mark = function(field, msg) {
		var el = field.el,
			dom = el && el.dom;
		if (el) {
			el.addClass(field.invalidClass);
			if (dom) {
				el.dom.qtip = msg;
				el.dom.qclass = 'x-form-invalid-tip';
			}
		}
		if(Ext.QuickTips){ // fix for floating editors interacting with DND
			Ext.QuickTips.enable();
		}
	};
} else if (window.console && console.warn) {
	console.warn('Overridding a class that does not exist anymore.');
}
