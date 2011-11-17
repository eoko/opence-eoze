/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
eo.deps.waitIn('eo.form.contact', 'locale', function(NS, ns) {
	
var sp  = Ext.form.FieldSet,
	spp = sp.prototype;

NS.AbstractFieldSet = Ext.extend(sp, {

	cls: 'line'
	,collapsible: true
	
	,autoHide: true

	// disable bubbling of add/remove events, to prevent parent
	// FormPanel to become aware of this FieldSet's children
	// Fields
	,bubbleEvents: []
	
	,getName: function() { return this.name; }
	
	,constructor: function() {
		this.addEvents('change');
		spp.constructor.apply(this, arguments);
		Ext.applyIf(this, {
			title: NS.locale(this.fieldConfig.textKeyItem)
		});
	}

	// private
	,initComponent: function() {
		
		this.defaults = this.defaults || {};
		
		Ext.applyIf(this.defaults, {
			hideLabel: true
			,removable: true
		});
		
		spp.initComponent.call(this);
	}

	// private
	,afterRender: function() {
		spp.afterRender.apply(this, arguments);
		if (this.autoHide && !this.items.length) {
			this.hide();
		}
		if (this.value) {
			this.setValue(this.value);
		}
	}
	
	/**
	 * Creates a button with a handler configured to add a field
	 * to that FieldSet.
	 */
	,createAddButton: function() {
		var fc = this.fieldConfig,
			key = fc.textKeyNaturalItem || fc.textKeyItem;
		return new Ext.Button({
			iconCls: 'ico add'
			,text: this.textItem || this.title
			,scope: this
			,handler: this.addField
			,tooltip: NS.locale(
				'addA', 
				NS.locale.genre(key), 
				{item: ':' + key}
			)
		});
	}
	
	// protected
	,addField: function() {
		var field = this.createField();
		field.on({
			scope: this
			,removeline: this.removeFieldHandler
			,becomedefault: this.setDefaultFieldHandler
			,change: function() {
				this.fireEvent('change', this);
			}
		});
	
		spp.add.call(this, field);
		
		// default
		if (this.items.length === 1) {
			field.setDefault(true);
		}
		
		// collapse
		if (this.collapsed) {
			this.expand();
		}
		
		// autohide
		if (this.autoHide) {
			this.show();
		}
		
		// layout
		this.redoLayout();
		
//		this.fireEvent('change', this);
		
		field.focus();
		
		return field;
	}

	,add: function() {
		eo.warn('Direct add() to eo.form.contact.AbstractFieldSet is disabled.');
	}
	
	,remove: function() {
		eo.warn('Direct remove() to eo.form.contact.AbstractFieldSet is disabled.');
	}
	
	// protected
	,createField: function() {
		var c = this.getFieldClass();
		return new c({
			removable: true
		});
	}
	
	// private
	,removeFieldHandler: function(field) {
		var wasDefault = field.isDefault(); // isDefault won't work once the field is destroyed
		
		spp.remove.call(this, field);
		
		if (this.items.length) {
			if (wasDefault) {
				this.setDefaultField(this.items.get(0));
			}
			if (this.autoHide) {
				this.show();
			}
		} else {
			if (this.allowBlank === false) {
				this.addField();
			} else if (this.autoHide) {
				this.hide();
			}
		}
		
		this.fireEvent('change', this);
		
		this.redoLayout();
	}
	
	// private
	,setDefaultFieldHandler: function(field) {
		this.setDefaultField(field);
	}
	
	,hasDefaultField: function() {
		return !!this.items.each(function(item) {
			if (item.isDefault()) {
				return false;
			}
		});
	}
	
	,setDefaultField: function(field) {
		this.items.each(function(item) {
			item.setDefault(false);
		});
		field.setDefault(true);
	}
	
	,getValue: function() {
		if (!this.rendered) {
			return this.value;
		}
		var data = [];
		this.items.each(function(item) {
			if (item.isValid()) {
				data.push(item.getValue());
			}
		});
		return data;
	}
	
	,setValue: function(data) {
		if (!this.rendered) {
			this.value = data;
			return;
		}
		this.removeAll();
		if (data) {
			Ext.each(data, function(value) {
				var field = this.addField();
				field.setValue(value);
				if (field.isDefault()) {
					this.setDefaultField(field);
				}
			}, this);
		}
	}
	
	,redoLayout: function() {
		var owner = this.ownerCt;
		this.doLayout();
		if (owner) {
			owner.doLayout();
		}
	}
	
	// Needed to be recognized by FormPanel.isField
	,markInvalid: Ext.emptyFn
	,clearInvalid: Ext.emptyFn
	
	,validate: function() {
		return true;
	}
	
	,reset: function() {
		this.setValue([]);
	}
	
});

eo.deps.reg('AbstractFieldSet', ns);
	
}); // deps