/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
eo.deps.waitIn('eo.form.contact', 'locale', function(NS, ns) {

var sp  = Ext.form.CompositeField,
	spp = sp.prototype;

/**
 * Base class for contact fields.
 * 
 * Provides base functionnalities for removing the field, and selecting 
 * the field's type.
 */
eo.form.contact.AbstractContactField = Ext.extend(sp, {
	
	textRemove: NS.locale('remove')

	,removable: true
	,defaultPickable: true

	,idName:      'id'
	,typeName:    'type'
	,defaultName: 'default'

	,constructor: function(config) {
		this.addEvents('beforeremoveline', 'removeline', 'becomedefault');
		this.valueFields = {};
		spp.constructor.call(this, config);
	}

	// private
	,initComponent: function() {
		
		this.typeCombo = Ext.create(this.createTypeComboConfig());
		this.items.unshift(this.typeCombo);
		
		this.idField = new Ext.form.Hidden({
			emptyValue: null
		});
		this.items.unshift(this.idField);
		
		this.valueFields[this.idName] = this.idField;
		this.valueFields[this.typeName] = this.typeCombo;

		// default
		if (this.defaultPickable) {
			this.defaultButton = new Ext.Button({
				iconCls: 'ico tick pressable'
				,scope: this
				,tooltip: NS.locale('setDefault',
						NS.locale.genre(this.textKeyNaturalItem || this.textKeyItem),
						{type: ':' + (this.textKeyNaturalItem || this.textKeyItem)})
//				,enableToggle: true
//				,pressed: true
				,handler: function() {
					if (!this.isDefault()) {
						this.fireEvent('becomedefault', this);
					}
				}
				,getValue: function() {
					return this.pressed;
				}
				,setValue: function(value) {
					this.toggle(!!value);
				}
			});
			this.items.unshift(this.defaultButton);
			
			this.valueFields[this.defaultName] = this.defaultButton;
		}
		
		// removable
		if (this.removable) {
			this.deleteButton = new Ext.Button({
				iconCls: 'ico delete'
				,scope: this
				,tooltip: this.textRemove
				,handler: this.removeHandler
			});
			this.items.push(this.deleteButton);
		}
		
		spp.initComponent.call(this);
	}
	
	,isDefault: function() {
		return !this.defaultButton || this.defaultButton.pressed;
	}
	
	,setDefault: function(on) {
		this.defaultButton.toggle(on);
	}
	
	// protected
	,createTypeComboConfig: function() {
		return {
			xtype: 'combo'
			,mode: 'local'
			,valueField: 'type'
			,displayField: 'label'
			,triggerAction: 'all'
			,value: this.defaultType
			,editable: false
			,minListWidth: 130
			,width: 130
			,store: this.getTypeStore()
		};
	}

	// protected
	,getTypeStore: function() {
		var s = this.typeStore;
		if (!s) {
			
			var data = [];
			if (Ext.isArray(this.types)) {
				Ext.each(this.types, function(type) {
					type = NS.locale(type);
					data.push([type, type]);
				});
			} else if (Ext.isObject(this.types)) {
				Ext.iterate(this.types, function(type, label) {
					data.push([type, NS.locale(label)]);
				});
			}
			
			return this.typeStore = new Ext.data.ArrayStore({
				fields: ['type', 'label']
				,data: data
			})
		} else {
			return s;
		}
	}
	
	// private
	,removeHandler: function() {
		this.removeline();
	}
	
	/**
	 * Removes the field.
	 * 
	 * <b>Note:</b> this method just trigger the remove event, the
	 * parent container needs to handle actual removable logic itself.
	 * 
	 * @param {bool} suppressEvent TRUE to skip the beforeremove event.
	 */
	,removeline: function(suppressEvent) {
		if (!suppressEvent) {
			if (false === this.fireEvent('beforeremoveline', this)) {
				return;
			}
		}
		this.fireEvent('removeline', this);
	}
	
	,focus: function(defer) {
		var field = this.items.get(
			1
			+ (this.defaultPickable ? 1 : 0)
		);
		if (field) {
			field.focus(defer);
		}
	}
	
	,isValid: function() {
		return false;
	}
	
	,getValue: function() {
		var data = {};
		Ext.iterate(this.valueFields, function(name, field) {
			var value = field.getValue();
			if (value === '' && 'emptyValue' in field) {
				value = field.emptyValue;
			}
			data[name] = value;
		});
		return data;
	}
	
});

Oce.deps.reg('eo.form.contact.AbstractContactField');
	
}); // deps