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
Ext.override(Ext.Component, {

	setEnabled: function(enabled) {
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

	format : 'm/d/Y',
	constructor: function(cfg){
		Ext.grid.DateColumn.superclass.constructor.call(this, cfg);
//		this.renderer = Ext.util.Format.dateRenderer(this.format);
		var me = this;
		this.renderer = function(v) {
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
		
		max = 0;
		Ext.each(cols, function(l) {
			max = Math.max(max, l);
		});
		
		this.columns = max;

		var items = [], mi = this.items;
		Ext.each(cols, function(l) {
			items = items.concat(mi.splice(0, l));
			if (mi.length) {
				for (var i=0; i<max-l; i++) {
					items.push({xtype:"container"}); // spacer
				}
			}
		});
		
		this.items = items;
	}

	/**
	 * @option {Array|Integer} rowColumns The number of items to be placed on
	 * each row; spacers will be added at the end of rows that haven't the max
	 * number of items.
	 */
	Ext.form.CheckboxGroup.prototype.initComponent = function() {
		if (this.rowColumns) {
			calcColumns.call(this);
		}
		uber.call(this);
	};
})();