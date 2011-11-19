/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
eo.deps.waitIn('eo.form.contact', 'locale', function(NS, ns) {

var spp = Ext.form.CompositeField.prototype;

/**
 * Base class for contact fields.
 * 
 * Provides base functionnalities for removing the field, and selecting 
 * the field's type.
 */
eo.form.contact.AbstractField = Ext.extend(Ext.form.CompositeField, {
	
	textRemove: NS.locale('remove')

	/**
	 * @cfg {Boolean} removable `true` to make the field removable from its containing
	 * {eo.form.contact.AbstractFieldSet FieldSet}.
	 */
	,removable: true

	/**
	 * @cfg {String} idField The name that will be used in the object returned by
	 * {#getValue} to hold the value of the id field (if `undefined`, the field will 
	 * not be created).
	 */
	,idField:      'id'
	/**
	 * @cfg {String} typeField The name that will be used in the object retruned 
	 * {#getValue} by get value to hold the value of the type combo. (if undefined, 
	 * the type combo will not be created).
	 * @see types
	 */
	,typeField:    'type'
	/**
	 * @cfg {Array|Object} types The data to be used to populate the combo of the
	 * {@link #typeField}.
	 */
	/**
	 * @cfg {String} primaryField The name that will be used in the object returned
	 * by {#getValue} to hold the value representing if this Field is the selected 
	 * primary Field in its parent {@link eo.form.contact.AbstractFieldSet FieldSet}.
	 * 
	 * If set, a checkable button will be created to let the user select the primary
	 * Field in the parent FieldSet. If set to `undefined`, 
	 */
	,primaryField: 'default'
	
	/**
	 * @cfg {String} fieldsLayout Forces the layout of the children 
	 * {@link Ext.form.Field fields} (defaults to undefined). 
	 * 
	 * Accepted values are 'vertical' (shortcut 'v') or horizontal (shortcut 'h').
	 * 
	 * The designer of a component (that is a child class of this) can hint the
	 * preferred grouping of children fields in a row. If this property is left
	 * blank, this composition will be used.
	 * 
	 * If this property is set to 'horizontal', all fields will be forced on one 
	 * row.
	 * 
	 * If the property is set to 'vertical', then the fields hinted as grouped by
	 * the developper will be kept on one row, but the other fields will each be
	 * put on their own row.
	 */
	,fieldsLayout: undefined
	/**
	 * @cfg {Boolean} reserveDefaultCheckboxSpace If true, reserves the space 
	 * (screeen estate), the "set default' checkbox would have occupied , and
	 * draws the this field's children fields after this margin.
	 */
	,reserveDefaultCheckboxSpace: true
	
	,constructor: function(config) {

		/**
		 * @event becomeprimary
		 * Fires when the Field is selected as its owning {@link eo.form.contact.AbstractFieldSet
		 * FieldSet} primary field.
		 * @param {eo.form.contact.AbstractField} this
		 */
		this.addEvents('beforeremoveline', 'removeline', 'becomeprimary', 'change');

		// Apply fieldConfig
		var fieldConfig = config.fieldConfig || this.fieldConfig;
		if (fieldConfig) {
			if (Ext.isString(fieldConfig)) {
				fieldConfig = NS.config[fieldConfig];
			}
			if (!Ext.isObject(fieldConfig)) {
				throw new Error('Config is missing or invalid');
			}
			Ext.apply(this, fieldConfig);
		}

		// Init instance members
		this.valueFields = {};
		
		// Call super
		spp.constructor.call(this, config);

		// initComponent has been called in the parent's constructor
		this.addChangeTrackingListeners(this.valueFields);
	}
	
	/**
	 * Returns `true` if this Field type can be selected as primary in their
	 * {@link eo.form.contact.AbstractFieldSet FieldSet}.
	 * @return {Boolean}
	 */
	,hasPrimaryField: function() {
		return Ext.isString(this.primaryField);
	}
	
	/**
	 * Adds listeners to the given fields, in order to track when they
	 * are notified. The event type may depends on the field type, but
	 * the listener used will generally allways be 
	 * <code>this.fieldChangeListener</code>.
	 *
	 * @param {Object|Array} fields The fields to which the listener should be added.
	 * When first called by AbstractField, this method is passed all the
	 * <code>this.valueFields</code>. Overriding methods can call their
	 * parent method with some other fields (but take care not to modify
	 * the original object, which is the actual object used for the state 
	 * of this instance.
	 *
	 * @protected
	 */
	,addChangeTrackingListeners: function(fields) {
		Oce.walk(fields, function(name, field) {
			if (field instanceof Ext.form.Checkbox) {
				field.on('check', this.fieldChangeListener, this);
			} else {
				field.on('change', this.fieldChangeListener, this);
			}
		}, this);
	}
	
	/**
	 * Listener to be added to children fields event that should be
	 * considered as a modification.
	 * 
	 * @protected
	 */
	,fieldChangeListener: function() {
		if (this.isValid()) {
			this.fireEvent('change', this);
		}
	}
	
	/**
	 * Ensures that at least one field in the group is flex, else apply 
	 * flex=1 to all the fields.
	 * @private
	 */
	,flexHorizontalItemGroup: function(fields) {
		var hasFlexedFields = false;
		Ext.each(fields, function(field) {
			if (field.flex) {
				hasFlexedFields = true;
				return false;
			}
		});
		if (!hasFlexedFields) {
			Ext.each(fields, function(field) {
				field.flex = 1;
			});
		}
		return fields;
	}
	
	/**
	 * Returns an array in which nested arrays in the given fields 
	 * array are converted to CompositeFields. This only applies to
	 * the first level of nesting.
	 * @private
	 */
	,composeItems: function(fields) {
		var r = [];
		Ext.each(fields, function(field) {
			if (Ext.isArray(field)) {
				r.push(new Ext.form.CompositeField({
					items: this.flexHorizontalItemGroup(field)
				}));
			} else {
				r.push(field);
			}
		}, this);
		return r;
	}
	
	/**
	 * @private
	 */
	,layoutItems: function(fields, layout) {
		if (!layout) { // auto
			// if the fields array has nested array, that means that the
			// component developper meant to use composite layout,
			// else horizontal layout will be enought
			var hasNested = false;
			Ext.each(fields, function(field) {
				if (Ext.isArray(field)) {
					hasNested = true;
				}
			});
			return this.layoutItems(fields, hasNested ? 'v' : 'h');
		} else if (/^v(?:ertical)?$/i.test(layout)) { // vertical
			// if we are in V mode, we can honnor the layout requested by
			// the component (that is, have multiple fields on one single
			// row, if needed)
			return [{
				xtype: 'container'
				,flex: 1
				,layout: {
					type: 'form'
				}
				,cls: 'closer-items'
				,defaults: {
					hideLabel: true
					,anchor: '100%'
				}
				,items: this.composeItems(fields)
			}];
		} else if (/^h(?:orizontal)?$/i.test(layout)) { // this is horizontal
			// if we are in H, we *force* the h
			// even if the component is trying to compose h/v
			return this.flexHorizontalItemGroup(eo.flattenArray(fields));
		} else {
			throw new Error('Illegal argument: ' + layout);
		}
	}

	/**
	 * @private
	 */
	,initComponent: function() {
		var items,
			idName = this.idField,
			types = this.types,
			typeName = this.typeField;
		
		var instanceValueFields = {};
		
		// If this object has a createFields methods, that supposes that
		// initComponent is not overriden (while, if done correctly, it
		// could still be).
		if (this.createFields) {
			var fields = this.createFields();
			if (!Ext.isArray(fields)) {
				fields = [fields];
			}
			// store value fields
			Ext.each(fields, function(fields) {
				if (fields.submitValue) {
					instanceValueFields[fields.getName()] = fields;
				}
				fields.submitValue = false;
			});
			// layout
			items = this.items = this.layoutItems(fields, this.fieldsLayout);
		}

		// id
		if (idName) {
			var idField = new Ext.form.Hidden({
				emptyValue: null
			});
			items.unshift(idField);
			this.valueFields[this.idField] = idField;
		}

		// Type
		if (types && typeName) {
			var typeCombo = Ext.create(this.createTypeComboConfig());
			items.unshift(typeCombo);
			this.valueFields[typeName] = typeCombo;
		}

		// default
		if (this.hasPrimaryField()) {
			this.primaryButton = new Ext.Button({
				iconCls: 'ico tick pressable'
				,scope: this
				,tooltip: NS.locale('setDefault',
						NS.locale.genre(this.textKeyNaturalItem || this.textKeyItem),
						{type: ':' + (this.textKeyNaturalItem || this.textKeyItem)})
				,handler: function() {
					if (!this.isDefault()) {
						this.fireEvent('becomeprimary', this);
					}
				}
				,getValue: function() {
					return this.pressed;
				}
				,setValue: function(value) {
					this.toggle(!!value);
				}
			});
			items.unshift(this.primaryButton);
			
			this.valueFields[this.primaryField] = this.primaryButton;
		} else if (this.reserveDefaultCheckboxSpace) {
			items.unshift({
				xtype: 'box'
				,width: 22
			})
		}
		
		// removable
		if (this.removable) {
			this.deleteButton = new Ext.Button({
				iconCls: 'ico delete'
				,scope: this
				,tooltip: this.textRemove
				,handler: this.removeHandler
			});
			items.push(this.deleteButton);
		}
		
		// adds instance value fields
		Ext.iterate(instanceValueFields, function(name, field) {
			this.valueFields[name] = field;
		}, this);
		
		spp.initComponent.call(this);
	}
	
	,isDefault: function() {
		return !this.primaryButton || this.primaryButton.pressed;
	}
	
	,setDefault: function(on) {
		this.primaryButton && this.primaryButton.toggle(on);
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
			+ (this.hasPrimaryField() ? 1 : 0)
		);
		if (field) {
			field.focus(defer);
		}
	}

	,isValid: function() {
		return false;
	}
	
	/**
	 * Gets the value of the field. The value will be returned as an `Object`
	 * holding the value of this field's children fields.
	 * @return {Object}
	 */
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

	/**
	 * Sets the value of the field.
	 * @param {Object} data The data `Object` from which the value of the
	 * children fields will be taken.
	 */
	,setValue: function(data) {
		var primaryName = this.primaryField;
		if (!this.rendered) {
			this.value = data;
			return;
		}
		if (this.hasPrimaryField() && !(primaryName in data)) {
			this.valueFields[primaryName].setValue(false);
		}
		Ext.iterate(data, function(name, value) {
			var field = this.valueFields[name];
			if (field) {
				field.setValue(value);
			}
		}, this);
	}
	
	,afterRender: function() {
		spp.afterRender.apply(this, arguments);
		if (this.value) {
			this.setValue(this.value);
		}
	}
	
});

Oce.deps.reg('eo.form.contact.AbstractField');
	
}); // deps