Ext.ns('eo.form');

eo.form.MoneyField = Ext.form.NumberField.extend({
	
	precision: 2
	,symbol: "\u20ac" // euro
	
	,initComponent: function() {
		this.cls = (this.cls + " " || "") + "x-input-money euro";
		eo.form.MoneyField.superclass.initComponent.call(this);
		if (this.readOnly) this.setReadOnly(true);
	}
	
	,setValue: function(v) {
		v = parseFloat(v);
		Ext.form.TextField.prototype.setValue.call(
			this, 
			Ext.isNumber(v) ? v.toFixed(this.precision) : ""
		);
	}
	
	,setReadOnly: function(on) {
		if (this.el) {
			if (on) {
				this.disable();
				this.el.addClass("readonly");
			} else {
				this.enable();
				this.el.removeClass("readonly");
			}
		}
	}
	
	,onRender: function(ct, position) {
		eo.form.MoneyField.superclass.onRender.apply(this, arguments);
		if (!this.wrap) {
			var wrap = this.wrap = this.el.wrap();
			var c = wrap.createChild({tag:"span", cls:"x-input-money symbol euro", html: this.symbol})
			wrap.insertFirst(c);
			this.resizeEl = this.positionEl = this.wrap;
		}
		this.setReadOnly(this.readOnly);
	}
	
	,onResize: function(w, h) {
		eo.form.MoneyField.superclass.onResize.apply(this, arguments);
		this.el.setWidth(w-13);
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