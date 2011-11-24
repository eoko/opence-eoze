/**
 * Base class for extensible FieldSets in contact panel. This class is called
 * abstract but can actually be instanciated if given enougth config options,
 * in order to allow for on the fly configuration in pure text (e.g. Yaml files).
 * 
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
eo.deps.waitIn('eo.form.contact', 'locale', function(NS, ns) {
	
var sp  = Ext.form.FieldSet,
	spp = sp.prototype;

eo.form.contact.AbstractFieldSet = Ext.extend(Ext.form.FieldSet, {
	
	/**
	 * @cfg {String/Object} fieldConfig The global configuration Object of the
	 * children fields. In not set, this value will be deciphered from the 
	 * {#getFieldConstructor children field constructor}'s prototype.
	 */
	fieldConfig: undefined
	
	,isFormField: true
	,hideLabel: true
	
	/**
	 * @fg {Integer} maxFieldNumber the maximum number of fields that can be added 
	 * to this FieldSet.
	 */
	,maxFieldNumber: null
	/**
	 * @cfg {Integer} initialFieldNumber the initial number of empty fields that will 
	 * be added to the FieldSet. Note that this property is not used if the 
	 * FieldSet is given a {@link eo.form.contact.AbstractFieldSet#value value} (or 
	 * if {@link eo.form.contact.AbstractFieldSet#setValue#setValue setValue} is
	 * called before the Component is rendered.
	 */
	,initialFieldNumber: 0
	/**
	 * @cfg {String} fieldsLayout Forces the layout of the children's 
	 * {@link Ext.form.Field fields} (defaults to undefined). 
	 * 
	 * (The value is forwarded as is to 
	 * {@link eo.form.contact.AbstractField#fieldsLayout}).
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
	 * @cfg {Integer} numTitle The number to which the title must be accorded
	 * (default to 1).
	 */
	,numTitle: 1
	/**
	 * @cfg {Boolean} returnSingleValue If set to `true`, the {@link #getValue}
	 * method will not return the value of multiple children as an array, but
	 * instead, it will return only one value object for only one child (or
	 * `null` if it has no children. This option is considered only if 
	 * {@link #maxFieldNumber} is set to `1`.
	 */
	,returnSingleValue: false

	,cls: 'line'
	,collapsible: true
	
	,autoHide: true

	// disable bubbling of add/remove events, to prevent parent
	// FormPanel to become aware of this FieldSet's children
	// Fields
	,bubbleEvents: []
	
	,constructor: function(config) {
		
		/**
		 * @event change
		 * Fires when one the children field's value changes.
		 * @param {eo.form.contact.AbstractFieldSet} this
		 */
		/**
		 * @event fullstatechanged
		 * Fires when the FieldSet reach its 
		 * {@link eo.form.contact.AbstractFieldSet#maxFieldNumber maximum field number}.
		 * @param {eo.form.contact.AbstractFieldSet} this
		 * @param {Boolean} full true if the FieldSet is full, else false if it can accept
		 * more fields
		 */
		this.addEvents('change', 'fullstatechanged');
		
		// make a copy, since we're modifying it
		config = Ext.apply({}, config);

		// Convert fieldConfig as a string to config object
		var fieldConfig = config.fieldConfig || this.fieldConfig;
		if (!fieldConfig) {
			this.xtypeChildren = config.xtypeChildren || this.xtypeChildren;
			fieldConfig = this.getFieldConstructor().prototype.fieldConfig;
		}
		if (Ext.isString(fieldConfig)) {
			this.fieldConfig = NS.config[fieldConfig];
			delete config.fieldConfig; // because we don't want this to override
					// this.fieldConfig that we just set, when the parent's 
					// constructor will do Ext.apply(this, config)
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
	
	/**
	 * @private
	 */
	,allowMultiple: function() {
		var m = this.maxFieldNumber;
		return m === null || m === undefined || m > 1;
	}

	,initComponent: function() {
		
		this.defaults = this.defaults || {};
		
		Ext.applyIf(this.defaults, {
			hideLabel: true
			,removable: true
		});
		
		spp.initComponent.call(this);
	}

	,afterRender: function() {
		spp.afterRender.apply(this, arguments);
		if (this.autoHide && !this.items.length) {
			this.hide();
		}
		if (this.value) {
			this.setValue(this.value);
		} else if (this.initialFieldNumber) {
			for (var i=0, l=this.initialFieldNumber; i<l; i++) {
				this.addField(true);
			}
		}
	}
	
	/**
	 * Creates a button with a handler configured to add a field
	 * to that FieldSet.
	 * @return {Ext.Button}
	 * @private
	 */
	,createAddButton: function() {
		var fc = this.fieldConfig,
			key = fc.textKeyNaturalItem || fc.textKeyItem;
		
		var tooltip = NS.locale(
			'addA', 
			NS.locale.genre(key), 
			{item: ':' + key}
		);

		var button = new Ext.Button({
			iconCls: 'ico add'
			,text: NS.locale(this.fieldConfig.textKeyItem)
			,scope: this
			,handler: this.addField
			,tooltip: tooltip
		});

		if (Ext.isNumber(this.maxFieldNumber)) (function() {
			var genre = NS.locale.genre(key);
			var tooltipFull = NS.locale(
				'maxNumberIs',
				this.maxFieldNumber,
				genre,
				{item: ':' + key, number: eo.lang.intToWord(this.maxFieldNumber)}
			);
			button.mon(this, 'fullstatechanged', function(fs, full) {
				if (full) {
					button.disable();
					button.setTooltip(tooltipFull);
				} else {
					button.enable();
					button.setTooltip(tooltip);
				}
				button.setEnabled(!full);
			});
		}).call(this);
		
		return button;
	}
	
	// protected
	,addField: function(preventFocus) {
		
		var field = this.createField();
		field.on({
			scope: this
			,removeline: this.removeFieldHandler
			,becomeprimary: this.setPrimaryFieldHandler
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
		
		// The event will be fired if the field is actually modified...
//		this.fireEvent('change', this);
		if (Ext.isNumber(this.maxFieldNumber) && this.getFieldCount() >= this.maxFieldNumber
				&& !this.alreadyFull) {
			this.alreadyFull = true;
			this.fireEvent('fullstatechanged', this, true);
		}
		
		if (preventFocus !== true) {
			field.focus();
		}
		
		return field;
	}

//	,add: function() {
//		eo.warn('Direct add() to eo.form.contact.AbstractFieldSet is disabled.');
//	}
//	
//	,remove: function() {
//		eo.warn('Direct remove() to eo.form.contact.AbstractFieldSet is disabled.');
//	}
	
	/**
	 * Returns `true` if this FieldSet accept selection of one primary field in
	 * its children fields.
	 * @return {Boolean}
	 */
	,hasPrimaryFieldSelection: function() {
		var c = this.getFieldConstructor();
		if (c) {
			return c.prototype.hasPrimaryField();
		}
	}

	/**
	 * Gets the constructor for the children fields. By default, this method 
	 * uses {#xtypeChildren} to retrieve the actual constructor. This method can
	 * be overridden to directly provide the constructor function (can be useful
	 * if the constructor is not registered in {@link Ext.ComponentMgr}.
	 * @return {Function}
	 * @protected
	 */
	,getFieldConstructor: function() {
		var xtype = this.xtypeChildren;
		if (xtype) {
			return Ext.ComponentMgr.types[xtype];
		}
	}
	
	/**
	 * Creates a new child Field. This method can be overridden to customize the
	 * creation process.
	 * @protected
	 */
	,createField: function() {

		// apply defaults to the config passed to the field constructor
		// because some defaults options are ignored (like 
		// reservePrimaryButtonSpace)
		var config = Ext.applyIf({
			removable: true
		}, this.defaults);
		
		if (Ext.isDefined(this.fieldsLayout)) {
			config.fieldsLayout = this.fieldsLayout;
		}
		
		if (this.xtypeChildren) {
			return Ext.create(Ext.apply({xtype: this.xtypeChildren}, config));
		} else {
			var c = this.getFieldConstructor();
			return new c(config);
		}
	}
	
	// private
	,removeFieldHandler: function(field) {
		var wasDefault = field.isDefault(); // isDefault won't work once the field is destroyed
		
		spp.remove.call(this, field);
		
		if (this.items.length) {
			if (wasDefault) {
				this.setPrimaryField(this.items.get(0));
			}
			if (this.autoHide) {
				this.show();
			}
		} else {
			if (this.allowBlank === false) {
				this.addField(true);
			} else if (this.autoHide) {
				this.hide();
			}
		}
		
		this.fireEvent('change', this);
		
		this.redoLayout();
		
		if (Ext.isNumber(this.maxFieldNumber) && this.getFieldCount() < this.maxFieldNumber
				&& this.alreadyFull) {
			this.alreadyFull = false;
			this.fireEvent('fullstatechanged', this, false);
		}
	}

	/**
	 * Gets the number of children fields in the FieldSet.
	 */
	,getFieldCount: function() {
		return this.items.getCount();
	}
	
	/**
	 * 
	 * @param {Ext.Component} field
	 * @private
	 */
	,setPrimaryFieldHandler: function(field) {
		this.setPrimaryField(field);
	}
	
	/**
	 * Returns `true` if the FieldSet contains a child Field selected as
	 * the primary one for the FieldSet.
	 */
	,hasPrimaryField: function() {
		return !!this.items.each(function(item) {
			if (item.isDefault()) {
				return false;
			}
		});
	}
	
	/**
	 * Sets the given field as the primary one in this FieldSet.
	 * @param {eo.form.contact.AbstractField} field
	 */
	,setPrimaryField: function(field) {
		this.items.each(function(item) {
			item.setDefault(false);
		});
		field.setDefault(true);
	}

	/**
	 * Gets the value of the field.
	 * @return {Object[]|Object} An array containing the values of the children
	 * fields. Depending on the {@link #returnSingleValue} option, this method
	 * can also return a single `Object` value.
	 */
	,getValue: function() {
		if (!this.rendered) {
			return this.value;
		}
		if (this.maxFieldNumber === 1 && this.returnSingleValue) {
			switch (this.items.length) {
				case 0: return null;
				case 1: return this.items.get(0).getValue();
				default: throw new Error('Illegal State (not supposed to be able '
						+ 'to have more than one child)');
			}
		} else {
			var data = [];
			this.items.each(function(item) {
				if (item.isValid()) {
					data.push(item.getValue());
				}
			});
			return data;
		}
	}
	
	/**
	 * Sets the value of the field.
	 * @param {Object} data The data `object` containing the values of the 
	 * children fields.
	 */
	,setValue: function(data) {
		if (!this.rendered) {
			this.value = data;
			return;
		}
		this.removeAll();
		if (data === null || data === undefined) {
			return;
		}
		if (!Ext.isArray(data)) {
			if (this.returnSingleValue) {
				var field = this.addField(true);
				field.setValue(data);
				if (field.isDefault()) {
					this.setPrimaryField(field);
				}
			} else {
				throw new Error('Invalid value data: ' + data);
			}
		} else {
			Ext.each(data, function(value) {
				var field = this.addField(true);
				field.setValue(value);
				if (field.isDefault()) {
					this.setPrimaryField(field);
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