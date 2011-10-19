/**
 * CheckableFieldSet is a standard FieldSet, except that it is considered a
 * submittable checkbox by BasicForm.
 *
 * @author Éric Ortéga <eric@planysphere.fr>
 * @since 08/03/11 13:04
 */
Ext.ns("eo.form").CheckableFieldSet = Ext.extend(Ext.form.FieldSet, {
	spp: Ext.form.FieldSet.prototype
	
	,checked: false
	
	,isFormField: true
	
	// Dirty hack... On one hand, the isFormField property is required for 
	// BasicForm to consider this as a form field (most notably in its 
	// findField() method), and so process this field in setValue() ; on the 
	// other hand, FormLayout will apply this a fieldLabel if isFormField is 
	// true -- what we don't want. 
	// We cannot fix the layout problem with the label by setting hideLabel to
	// true, because that setting will be inherited by the children of this
	// container (without being possibly disabled).
	// So the inputType "hidden" is used to prevent the layout manager from
	// considering this as a field to lay out...
	,inputType: "hidden"
	
	,initComponent: function() {
		if (!this.checkboxName && this.name) {
			this.checkboxName = this.name;
		}
		
		this.defaults = Ext.apply({
			hideLabel: false
		}, this.defaults);
		
		this.checkboxToggle = true;
		
		this.spp.initComponent.call(this);
	}
	
	,onAdded: function(container, pos) {
		this.spp.onAdded.apply(this, arguments);
		
		// This is needed for this to be added to a BasicForm, since FormPanel
		// considers its items either as a field (added to the BasicForm), or as
		// a Container (which items are added to the BasicForm), but in an
		// exclusive fashion. That is, the FormPanel consider that an item 
		// cannot be both, Field & Container. So, we finish the job (adding this
		// container's items to the BasicForm) here.
		var fp = this.findParentBy(function(c) {
			return !!c.form;
		});
		
		if (fp) {
			fp.applySettings(this);
			fp.form.add.apply(fp.form, fp.findBy(fp.isField));
		}
	}
	
	,getName: function() {
		return this.name || this.checkboxName;
	}
	
	,initValue: function() {
		this.originalValue = this.getValue();
	}
	
	,reset: function() {
		this.setValue(this.originalValue);
		this.clearInvalid();
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
		if (this.rendered) {
			this.onCheckClick();
		}
		return this; 
	}
	
	,setEditable: function(editable) {
		this.checkbox.setDisplayed(editable);
	}
	
	// TODO Maybe that's fast expedient, and the isValid method should try to
	// integrate more gracefuly with Ext's validation process... A bit of
	// investigation of form.Field isValid method could be useful.
	,isValid: eo.trueFn
	
	,markInvalid: Ext.emptyFn
	,clearInvalid: Ext.emptyFn
	
	,validate: function() {
		return true;
	}
	
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
	
	,onCollapse: function() {
		this.spp.onCollapse.apply(this, arguments);
		this.fixOwnerCt();
	}
	
	,onExpand: function() {
		this.spp.onExpand.apply(this, arguments);
		this.fixOwnerCt();
	}
	
	// private
	,fixOwnerCt: function() {
		// might be called when setting form values, that is before the form
		// is really rendered (but rendered has already been set to true...
		// va savoir pourquoi)
		if (!Ext.isObject(this.layout)) return;
		
		var ct;
		this.doLayout();
		// fix vbox layout
		var vboxCt = this.findParentBy(function(c) {
			return c.layout instanceof Ext.layout.VBoxLayout
					|| (c.layout && (c.layout === "vbox" || c.layout.type === "vbox"));
		});
		if (vboxCt) {
			ct = this.ownerCt;
			if (ct && ct.syncSize) ct.syncSize();
			vboxCt.doLayout();
		}
		// fix shadow if in window
		ct = this.findRootCt();
		if (ct && ct.syncShadow) ct.syncShadow();
	}
	
	,findRootCt: function() {
		
		var finder = function(c) {
			return !c.ownerCt;
		};
		
		return function() {
			return this.findParentBy(finder);
		};
	}()
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
