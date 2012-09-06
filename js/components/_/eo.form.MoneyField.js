Ext.ns('eo.form');

/**
 * @xtype moneyfield
 */
eo.form.MoneyField = Ext.extend(Ext.form.NumberField, {
	
	precision: 2
	,symbol: "\u20ac" // euro
	
	,initComponent: function() {
		if (this.width === undefined) this.width = 200;
		this.cls = (this.cls + " " || "") + "x-input-money euro";
		eo.form.MoneyField.superclass.initComponent.call(this);
		if (this.readOnly) this.setReadOnly(true);
	}
	
	,setValue: function(v) {
		Ext.form.TextField.prototype.setValue.call(this, v);
		var hv = '';
		if (this.isValid(true)) {
			v = this.getValue();
			if (Ext.isNumber(v)) {
				hv = v.toFixed(this.precision);
				Ext.form.TextField.prototype.setValue.call(this, hv);
			}
		}
	}
	
	,setReadOnly: function(readOnly) {
		// superclass will add the readonly attribute to the input element
		eo.form.MoneyField.superclass.setReadOnly.apply(this, arguments);
		
		if (this.el && this.wrap) {
			if (readOnly) {
				this.wrap.addClass('readonly');
			} else {
				this.wrap.removeClass('readonly');
			}
		} else {
			this.readOnly = readOnly;
		}
	}
	
	,onRender: function(ct, position) {
		eo.form.MoneyField.superclass.onRender.apply(this, arguments);
		if (this.submit === false) {
			delete this.el.name;
		}
		if (!this.wrap) {
			var wrap = this.wrap = this.el.wrap({cls: "x-input-money-wrap"});
			var c = wrap.createChild({tag:"span", cls:"x-input-money symbol euro", html: this.symbol});
			wrap.insertFirst(c);
			this.resizeEl = this.positionEl = this.wrap;
		}
		this.setReadOnly(this.readOnly);
	}
	
	,onResize: function(w, h) {
		eo.form.MoneyField.superclass.onResize.apply(this, arguments);
		this.el.setWidth(w-17);
	}
	
	,onDestroy: function() {
		if (this.rendered) this.wrap.remove();
	}
	
});

Ext.reg("moneyfield", eo.form.MoneyField);

eo.form.MoneyDisplayField = eo.form.MoneyField.extend({
	
	initComponent: function() {
		this.cls = this.cls + " readonly";
		this.disabled = true;
		eo.form.MoneyDisplayField.superclass.initComponent.call(this);
	}
	
	,afterRender: function() {
		if (!this.submit) delete this.el.name;
		eo.form.MoneyDisplayField.superclass.afterRender.apply(this, arguments);
	}
});

Ext.reg("moneydisplayfield", eo.form.MoneyDisplayField);
