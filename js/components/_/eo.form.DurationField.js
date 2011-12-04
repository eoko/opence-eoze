Ext.ns('eo.form');
Ext.ns('eo.date');

eo.form.DurationField = Ext.extend(Ext.form.TextField, {

	setValue: function(v) {
		Ext.form.TextField.superclass.setValue.call(this, v);
	}
	
	,parseValue: function(value) {
		return {
			y: 1
			,m: 2
		}
	}
	
	,beforeBlur: function() {
		var v = this.parseValue(this.getRawValue());
		if (!Ext.isEmpty(v)) this.setValue(v);
	}
});

(function() {
	
	var u = eo.form.DurationField.Unit = eo.Class({
		
		pluralSuffix: "s"
		
		,getPlural: function() {
			var p = this.plural;
			if (p) return p;
			p = this.pluralSuffix;
			if (p) return this.display + p;
			return this.display;
		}
	});
	
	var unit = function(o) {
		return new u(o);
	};

	eo.form.DurationField.units = {
		second: unit({
			display: "second"
			,value: 1
		})
		,minute: unit({
			display: "minute"
			,value: 60
		})
		,hour: unit({
			display: "hour"
			,value: 3600
		})
		,day: unit({
			display: "day"
			,value: 86400
		})
		,week: unit({
			display: "week"
			,value: "604800"
		})
	}
})();

eo.form.DurationField.test = function() {

	var value = new Ext.Container({
		fieldLabel: "Value"
	});
	
	var testedField = new eo.form.DurationField({
		fieldLabel: "Tested Field"
		
		,listeners: {
			change: function(f) {
				value.update(JSON.stringify(f.getValue(), null, true))
			}
		}
	});

	var ct = new Ext.Container({
		renderTo: Ext.getBody(),
		layout: "form",
		style: "padding: 20px",
		items: [testedField, value, {xtype: "button"}]
	});
};