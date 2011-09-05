(function() {
	
var sp = Ext.form.Field,
	spp = sp.prototype;

Ext.ns("Ext.ux.form");

/**
 * Emulates a form.Field, while allowing to change the actual input
 * component. The major feature of this class is that it block its Container
 * superclass' add event in order to prevent the containing FormPanel to
 * liberally decide to add the input Component to its BasicForm, without
 * taking any action to monitor its desctruction, to remove it...
 */
Ext.ux.form.MultiTypeField = Ext.extend(sp, {

	isFormField: true
	,autoCreate: {tag: "div"}

	,defaultField: {xtype:"textfield", disabled: true}
	
	,initComponent: function() {
		this.items = [this.cloneDefaultField()];
		spp.initComponent.call(this);
	}
	
	// private
	,cloneDefaultField: function() { return Ext.apply({}, this.defaultField) }
	
	,onRender: function() {
		spp.onRender.apply(this, arguments);
		if (!this.fieldCt) {
			debugger
			this.fieldCt = new Ext.Container({
				renderTo: this.el
				,layout: "fit"
			})
//			this.resizeEl = this.positionEl = this.fieldCt.el;
			this.setField(this.field);
		}
	}
	
	,onResize: function(w, h) {
		spp.onResize.apply(this, arguments);
		this.fieldCt.setSize(w, h);
		this.fieldCt.doLayout();
	}

	,setField: function(field) {
		var ct = this.fieldCt;
		if (!ct) {
			this.field = field;
			return;
		}
		if (!field) field = this.cloneDefaultField();
		//if (this.field) ct.remove(this.field);
		ct.removeAll();
		this.field = ct.add(Ext.apply({
			name: this.name
			,allowBlank: this.allowBlank
		}, field));
		if (this.value) {
			this.field.setValue(this.value);
			delete this.value;
		}
		ct.doLayout();
	}

	,fireEvent: function(e) {
		// block the fucking add event !!!
		if (e === "add") return undefined;
		else return spp.fireEvent.apply(this, arguments);
	}

	,getValue: function() {
		var f = this.field;
		if (!f || !f.getValue) {
			return undefined;
		} else if (f instanceof Ext.form.DateField) {
			var v = f.getValue();
			if (v instanceof Date) return v.format("Y-m-d");
			else return v;
		} else {
			return f.getValue();
		}
	}

	,setValue: function(v) {
		var f = this.field;
		if (f && f.getValue) f.setValue(v);
		else this.value = v;
	}

	,isValid: function() {
		var f = this.field;
		if (f && f.isValid) return f.isValid();
		else return true;
	}

	,markInvalid: function() {
		var f = this.field;
		if (f && f.markInvalid) f.markInvalid();
	}

	,clearInvalid: function() {
		var f = this.field;
		if (f && f.clearInvalid) f.clearInvalid();
	}
});

Ext.reg("multitypefield", Ext.ux.form.MultiTypeField);
	
})(); // closure