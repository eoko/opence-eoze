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
	,idField: 'id'
	/**
	 * @cfg {String} typeField The name that will be used in the object retruned 
	 * {#getValue} by get value to hold the value of the type combo. (if undefined, 
	 * the type combo will not be created).
	 * @see types
	 */
	,typeField: 'type'
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
	,primaryField: 'primary'
	
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
	 * @cfg {Boolean} reservePrimaryButtonSpace If `true`, reserves the space 
	 * (screeen estate), the "set primary" button would have occupied, even if
	 * this type of Field cannot actually be selected as primary. This is to allow
	 * display alignement between multiple {eo.form.contact.AbstractFieldSet FieldSet}s.
	 * 
	 * If left to `undefined`, and the parent FieldSet is contained in a 
	 * {eo.form.contact.ContactPanel ContactPanel}, then the ContactPanel will use
	 * {#defaultReservePrimaryButtonSpace} to handle this automatically by 
	 * introspecting its children FieldSets.
	 */
	,reservePrimaryButtonSpace: undefined
	/**
	 * @cfg {Boolean} defaultReservePrimaryButtonSpace This value will be used instead
	 * of {#reservePrimaryButtonSpace}, if the latter is not defined. This options 
	 * exists in order to enable {eo.form.contact.ContactPanel} to set a default value,
	 * while preserving the possibility to override this default for the classes 
	 * extending {eo.form.contact.AbstractField AbstractField}, or at runtime.
	 */
	,defaultReservePrimaryButtonSpace: undefined
	/**
	 * @cfg {Boolean} autoTooltip `true` to automatically apply the {@link #emptyText}
	 * of children fields as tooltip (on them).
	 */
	,autoTooltip: true
	
	/**
	 * @property {Boolean} 
	 * All the children fields of the component, that is the base fields
	 * and the extra fields.
	 * @private
	 */
	,allFields: undefined
	
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
	 * Returns `true` if this Field has a type selection combo.
	 * @return {Boolean}
	 */
	,hasTypeField: function() {
		return this.types && this.typeField;
	}
	
	/**
	 * Adds listeners to the given fields, in order to track when they
	 * are notified. The event type may depends on the field type, but
	 * the listener used will generally allways be `this.fieldChangeListener`.
	 *
	 * @param {Object|Array} fields The fields to which the listener should 
	 * be added. When first called by AbstractField, this method is passed 
	 * all the `this.valueFields`. Overriding methods can call their parent 
	 * method with some other fields (but take care not to modify the original 
	 * object, which is the actual object used for the state of this instance.
	 *
	 * @protected
	 */
	,addChangeTrackingListeners: function(fields) {
		Oce.walk(fields, function(name, field) {
			if (field instanceof Ext.form.Checkbox) {
				field.on('check', this.fieldChangeListener, this);
			
			} else { // fields with change event
				
				field.on('change', this.fieldChangeListener, this);

				if (field instanceof Ext.form.TextField && field.enableKeyEvents) {
					field.on({
						scope: this
						,buffer: 200
						,keyup: this.fieldChangeListenerIfDirty
					});
				}
				if (field instanceof Ext.form.TriggerField) {
					field.on('select', this.fieldChangeListener, this);
				}
			}
		}, this);
	}
	
	/**
	 * {Boolean} autoField `true` if this field has been
	 * automatically added to its parent fieldSet. Automatically
	 * added fields won't fire {@link #event-change} if they are
	 * not valid.
	 * 
	 * The property is set by the parent FieldSet itself.
	 * 
	 * @private
	 */
	,autoField: false
	
	/**
	 * Listener to be added to children fields event that should be
	 * considered as a modification.
	 * 
	 * @protected
	 */
	,fieldChangeListener: function() {
		if (!this.autoField || this.isValid(true)) {
			this.fireEvent('change', this);
		}
	}
	
	,fieldChangeListenerIfDirty: function(field) {
		if (field.isDirty()) {
			return this.fieldChangeListener.apply(this, arguments);
		}
		return undefined;
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
	
	// private
	,getItemsLayout: function() {
		var fields = this.allFields;
		if (!this.itemsLayout) {
			if (!this.fieldsLayout) {
				// if the fields array has nested array, that means that the
				// component developper meant to use composite layout,
				// else horizontal layout will be enought
				var hasNested = false;
				Ext.each(fields, function(field) {
					if (Ext.isArray(field)) {
						hasNested = true;
					}
				});
				this.itemsLayout = hasNested ? 'v' : 'h';
			} else if (/^v(?:ertical)?$/i.test(this.fieldsLayout)) { // vertical
				this.itemsLayout = 'v';
			} else if (/^h(?:orizontal)?$/i.test(this.fieldsLayout)) { // this is horizontal
				this.itemsLayout = 'h';
			} else {
				throw new Error('Illegal layout: ' + this.fieldsLayout);
			}
		}
		return this.itemsLayout;
	}
	
	/**
	 * @private
	 */
	,layoutItems: function() {
		var fields = this.allFields;
		switch (this.getItemsLayout()) {
			case 'v':
				// if we are in V mode, we can honnor the layout requested by
				// the component (that is, have multiple fields on one single
				// row, if needed)
				return [this.mainCt = Ext.create({
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
				})];
			case 'h':
				// if we are in H, we *force* the h
				// even if the component is trying to compose h/v
				this.mainCt = this;
				return this.flexHorizontalItemGroup(eo.flattenArray(fields));
		}
	}
	
	// private
	,createChildrenFields: function(method) {
		
		var initFields = function(field) {
			var idName = this.idField,
				valueFields = this.instanceValueFields;
			// store submit fields
			if (field.submitValue !== false) {
				valueFields[field.getName()] = field;
				field.submitValue = false;
				// check for own id field
				if (field.name === idName) {
					this.hasOwnIdField = true;
				}
			}
			// emptyText to tooltip
			if (this.autoTooltip && !field.hasOwnProperty('tooltip') && field.emptyText) {
				field.on({
					single: true
					,afterrender: function(field) {
						field.el.dom['qtip'] = field.emptyText;
					}
				});
			}
			// prevent mark on automatic fields
			if (!field.hasOwnProperty('preventMark') && this.autoField) {
				field.preventMark = true;
			}
		};
		
		return function(method) {
			var fields = [];
			if (this[method]) {
				fields = this[method]();
			}
			if (!Ext.isArray(fields)) {
				fields = [fields];
			}
			Ext.each(fields, initFields, this);
			return fields;
		};
	}() // closure
		
	/**
	 * Abstract method that must be implemented to create children value fields.
	 * 
	 * @return {Array} The `array` of **instanciated** value fields to be
	 * added to the main container of this field.
	 * 
	 * @protected
	 * @method
	 */
	,createFields: undefined
	
	/**
	 * Abstract method that can be implemented to create extra children value fields.
	 * Those fields would be hidden by default, and they will always be optionnal.
	 * 
	 * @return {Array} The `array` of **instanciated** value fields to be
	 * added to the main container of this field.
	 * 
	 * @protected
	 * @method
	 */
	,createExtraFields: undefined

	/**
	 * @private
	 */
	,initComponent: function() {
		var idName = this.idField,
			typeName = this.typeField;
		
		this.instanceValueFields = {};
		
		var fields = this.createChildrenFields('createFields'),
			extraFields = this.createChildrenFields('createExtraFields');

		// store all fields
		this.allFields = fields.concat(extraFields);
			
		// filled by createChildrenFields
		var instanceValueFields = this.instanceValueFields;
		
		Ext.each(extraFields, function(field) {
			field.hide();
		});
		
		var items = this.items = this.layoutItems();

		// id
		if (idName && !this.hasOwnIdField) {
			var idField = new Ext.form.Hidden({
				emptyValue: null
				,getValue: function() {
					var v = Ext.form.Hidden.prototype.getValue.call(this);
					if (v !== undefined && v !== null) {
						v = parseInt(v);
					}
					return v;
				}
			});
			items.unshift(idField);
			this.valueFields[this.idField] = idField;
		}

		// Type
		if (this.hasTypeField()) {
			var typeCombo = Ext.create(this.createTypeComboConfig());
			items.unshift(typeCombo);
			this.valueFields[typeName] = typeCombo;
		}

		// primary
		var reservePrimaryButtonSpace = this.reservePrimaryButtonSpace;
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
				,reset: function() {
					this.toggle(false);
				}
				,toggle: function(pressed) {
					if (this.pressed !== pressed) {
						this.fireEvent('change', this, pressed, this.pressed);
						Ext.Button.prototype.toggle.call(this, pressed);
					}
				}
			});
			items.unshift(this.primaryButton);
			
			this.valueFields[this.primaryField] = this.primaryButton;
		} else if (Ext.isDefined(reservePrimaryButtonSpace) ? reservePrimaryButtonSpace 
				: this.defaultReservePrimaryButtonSpace) {
			items.unshift({
				xtype: 'box'
				,width: 22
			})
		}
		
		var rightCol = new Ext.Container({
			layout: {
				type: 'vbox'
			}
			,height: '100%'
		});
		items.push(rightCol);
		
		// removable
		if (this.removable) {
			rightCol.add(new Ext.Button({
				iconCls: 'ico delete'
				,scope: this
				,tooltip: this.textRemove
				,handler: this.removeHandler
			}));
			rightCol.add(new Ext.BoxComponent({
				flex: 1
			}));
		}
		
		if (this.createExtraFields) {
			
			if (this.getItemsLayout() !== 'v') {
				throw new Error('Not implemented yet');
			}
			
			this.mainCt.autoHeight = true;
			rightCol.add(new Ext.Button({
				iconCls: 'ico contact expand'
				,scope: this
				,tooltip: NS.locale('showAllFields')
				,isCollapsed: true
				,collapseData: {
					'true': {
						fn: 'hide',
						cls: 'expand',
						tooltip: NS.locale('showAllFields')
					},
					'false': {
						fn: 'show',
						cls: 'collapse',
						tooltip: NS.locale('onlyBaseFields')
					}
				}
				,handler: function(button) {
					button.isCollapsed = !button.isCollapsed;
					var cd = button.collapseData[button.isCollapsed],
						fn = cd.fn;
					button.setTooltip(cd.tooltip)
					Ext.each(extraFields, function(field) {
						field[fn]();
					});
					this.mainCt.syncSize();
					rightCol.setHeight(this.mainCt.getHeight());
					
					this.doLayout();
					
					var top = this.findParentBy(function(p) {
						return !p.ownerCt;
					});
					if (top) {
						top.doLayout();
					}
					
					button.setIconClass('ico contact ' + cd.cls);
				}
			}));
			rightCol.add(new Ext.BoxComponent({
				height: 2
			}));
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
			(this.hasTypeField() ? 1 : 0)
			+ (this.hasPrimaryField() ? 1 : 0)
		);
		if (field) {
			field.focus(defer);
		}
	}

	,isValid: function(preventMark) {
		var vf = this.valueFields,
			i = vf.length;
		while (i--) {
			if (!vf[i].isValid(preventMark)) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Gets the value of the field. The value will be returned as an `Object`
	 * holding the value of this field's children fields.
	 * @return {Object}
	 */
	,getValue: function() {
		if (!this.rendered) {
			return this.value;
		}
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
		Ext.iterate(this.valueFields, function(name, field) {
			if (field.rendered) {
				field.reset();
			}
		});
		if (!data) {
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
		// init value
		if (this.value) {
			this.setValue(this.value);
		}
		this.originalValue = this.getValue();
	}
	
	,setbaseParams: function(baseParams) {}

});

Oce.deps.reg('eo.form.contact.AbstractField');
	
}); // deps