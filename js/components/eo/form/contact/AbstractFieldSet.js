/**
 * Base class for extensible FieldSets in contact panel. This class is called
 * abstract but can actually be instanciated if given enougth config options,
 * in order to allow for on the fly configuration in pure text (e.g. Yaml files).
 * 
 * @config {int} numTitle The number to which the title must be accorded
 * (default: undefined).
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
eo.deps.waitIn('eo.form.contact', 'locale', function(NS, ns) {
	
var sp  = Ext.form.FieldSet,
	spp = sp.prototype;

NS.AbstractFieldSet = Ext.extend(sp, {

	/**
	 * @config {int} the maximum number of fields that can be added to this FieldSet.
	 */
	maxFieldNumber: null

	,cls: 'line'
	,collapsible: true
	
	,autoHide: true

	// disable bubbling of add/remove events, to prevent parent
	// FormPanel to become aware of this FieldSet's children
	// Fields
	,bubbleEvents: []
	
	,constructor: function(config) {
		
		this.addEvents('change');
		
		// make a copy, since we're modifying it
		config = Ext.apply({}, config);

		// Convert fieldConfig as a string to config object
		var fieldConfig = config.fieldConfig;
		if (Ext.isString(fieldConfig)) {
			this.fieldConfig = NS.config[config.fieldConfig];
			delete config.fieldConfig;
			if (!Ext.isObject(this.fieldConfig)) {
				throw new Error('Config is missing');
			}
		}
		
		spp.constructor.call(this, config);

		// Automatic title
		if (!this.title) {
			var num = 'numTitle' in this ? this.numTitle : (this.allowMultiple() ? 2 : 1);
			this.title = NS.locale(this.fieldConfig.textKeyItem, num);
		}
	}
	
	,getName: function() {
		return this.name;
	}
	
	// private
	,allowMultiple: function() {
		var m = this.maxFieldNumber;
		return m === null || m === undefined || m > 1;
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
			,text: NS.locale(NS.locale(this.fieldConfig.textKeyItem))
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
		
		var config = {
			removable: true
		};
		
		if (this.fieldXType) {
			return Ext.create(Ext.apply({xtype: this.fieldXType}, config));
		} else {
			var c = this.getFieldClass();
			return new c(config);
		}
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

Ext.reg('contactfieldset', NS.AbstractFieldSet);

eo.deps.reg('AbstractFieldSet', ns);
	
}); // deps