/**
 * CheckableFieldSet is a standard FieldSet, except that it is considered a
 * submittable checkbox by BasicForm.
 *
 * @author Éric Ortéga <eric@planysphere.fr>
 * @since 08/03/11 13:04
 */
Ext.ns("eo.form").CheckableFieldSet = Ext.form.FieldSet.extend({
	
	checked: false
	
	,initComponent: function() {
		if (!this.checkboxName && this.name) {
			this.checkboxName = this.name;
		}
		
		this.checkboxToggle = true;
		
		eo.form.CheckableFieldSet.superclass.initComponent.call(this);
	}
	
	,getValue: function() {
		if (this.rendered) {
			return this.checkbox.dom.checked;
		} else {
			return this.checked;
		}
	}
	
	,setValue: function(v) {
		var checked = this.checked,
		inputVal = this.inputValue;
		this.checked = (v === true || v === 'true' || v == '1' || (inputVal ? v == inputVal : String(v).toLowerCase() == 'on'));
		if(this.rendered){
			this.checkbox.dom.checked = this.checked;
			this.checkbox.dom.defaultChecked = this.checked;
		}
		if(checked != this.checked){
			this.fireEvent('check', this, this.checked);
			if(this.handler){
				this.handler.call(this.scope || this, this, this.checked);
			}
		}
		// emulate the event that collapse/expand the fieldset by checkbox
		this.onCheckClick();
		return this; 
	}
	
	,markInvalid: Ext.emptyFn
	,clearInvalid: Ext.emptyFn
	
	,onRender: function() {
		eo.form.CheckableFieldSet.superclass.onRender.apply(this, arguments);
		if (this.checked) {
			this.setValue(true);
		} else {
			this.checked = this.checkbox.dom.checked;
		}
	}
	
	,onCheckClick: function() {
		eo.form.CheckableFieldSet.superclass.onCheckClick.apply(this, arguments);
		if (this.checkbox.dom.checked != this.checked) {
			this.setValue(this.checkbox.dom.checked);
		}
	}
});

Ext.reg("checkfieldset", eo.form.CheckableFieldSet);

// Hack initFields method to take the CheckableFieldSet as a item of the BasicForm
(function() {
	var uber = Ext.FormPanel.prototype.initFields;
	
	Ext.FormPanel.override({
		initFields : function(){
			var f = this.form;
			var formPanel = this;
			var fn = function(c){
				if (formPanel.isField(c)) {
					f.add(c);
					// here ----------------------------------------------------
					// Why have they put this else... A component cannot be
					// both?
					if (c instanceof eo.form.CheckableFieldSet) {
						c.items.each(fn, this);
					}
					// end hack ------------------------------------------------
				} else if (c.findBy && c != formPanel) {
					formPanel.applySettings(c);
					if (c.items && c.items.each) {
						c.items.each(fn, this);
					}
				}
			};
			this.items.each(fn, this);
		}
	});
})();
