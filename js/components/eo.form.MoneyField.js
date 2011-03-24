Ext.ns('eo.form');

eo.form.MoneyField = Ext.form.NumberField.extend({
	
	precision: 2
	,symbol: "\u20ac" // euro
	
	,initComponent: function() {
		this.cls = (this.cls + " " || "") + "x-input-money euro";
		eo.form.MoneyField.superclass.initComponent.call(this);
	}
	
	,setValue: function(v) {
		v = parseFloat(v);
		Ext.form.TextField.prototype.setValue.call(
			this, 
			Ext.isNumber(v) ? v.toFixed(this.precision) : ""
		);
	}
	
	,onRender: function(ct, position) {
		eo.form.MoneyField.superclass.onRender.apply(this, arguments);
		if (!this.wrap) {
			var wrap = this.wrap = this.el.wrap();
			wrap.createChild({tag:"span", cls:"x-input-money symbol euro", html: this.symbol})
			this.resizeEl = this.positionEl = this.wrap;
		}
	}
	
	,onResize: function(w, h) {
		eo.form.MoneyField.superclass.onResize.apply(this, arguments);
		this.el.setWidth(w-13);
	}
	
});

Ext.reg("moneyfield", eo.form.MoneyField);