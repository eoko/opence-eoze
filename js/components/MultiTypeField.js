Ext.ns("Ext.ux.form");

/**
 * Emulates a form.Field, while allowing to change the actual input
 * component. The major feature of this class is that it block its Container
 * superclass' add event in order to prevent the containing FormPanel to
 * liberally decide to add the input Component to its BasicForm, without
 * taking any action to monitor its desctruction, to remove it...
 */
Ext.ux.form.MultiTypeField = Ext.Container.extend({

	isFormField: true

	,defaultField: {xtype:"textfield", disabled: true}

	,initComponent: function() {
		this.layout = "fit";
		Ext.ux.form.MultiTypeField.superclass.initComponent.call(this);
		this.setField(this.defaultField);
	}

	,setField: function(field) {
		if (!field) field = this.defaultField;
		if (this.field) this.remove(this.field);
		this.field = this.add(field);
		if (this.value) {
			this.field.setValue(this.value);
			delete this.value;
		}
		this.doLayout();
	}

	,fireEvent: function(e) {
		// block the fucking add event !!!
		if (e === "add") return undefined;
		else return Ext.ux.form.MultiTypeField.superclass.fireEvent.apply(this, arguments);
	}

	,getValue: function() {
		if (!this.field) {
			return undefined;
		} else if (this.field instanceof Ext.form.DateField) {
			var v = this.field.getValue();
			if (v instanceof Date) return v.format("Y-m-d");
			else return v;
		} else {
			return this.field.getValue();
		}
	}

	,setValue: function(v) {
		if (this.field) this.field.setValue(v);
		else this.value = v;
	}

	,isValid: function() {
		if (this.field) return this.field.isValid();
		else return true;
	}

	,markInvalid: function() {
		if (this.field) this.field.markInvalid();
	}

	,clearInvalid: function() {
		if (this.field) this.field.clearInvalid();
	}
});

Ext.reg("multitypefield", Ext.ux.form.MultiTypeField);