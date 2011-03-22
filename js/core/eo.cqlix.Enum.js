Ext.ns('eo.cqlix');

Oce.deps.wait('eo.cqlix.Model', function() {
//(function() {

var NS = eo.cqlix;

NS.EnumValue = eo.Object.create({

	/**
	 * @param {Object} config
	 *  config = {
	 *		CODE: {
	 *			label: ...
	 *			,default: true|false (default FALSE)
	 *			,value: ...
	 *			,code: ...
	 *		}
	 *  }
	 */
	constructor: function(config) {
		Ext.apply(this, config);
		this['default'] = this.isDefault() === true;
	}

	,isDefault: function() {
		return this['default'];
	}

	,getLabel: function(type) {
		return eo.Text.get(this.label, type);
	}
});

NS.EnumField = NS.Enum = eo.Object.extend(NS.ModelField, {
	
	/**
	 * @var {String} (default: " : ") the separator used between the checkbox 
	 * label (boxLabel) and the radio buttons, when this enum is rendered as a 
	 * checkbox:
	 * 
	 * [ ] boxLabel SEPARATOR ( ) radio1 ( ) radio2 ...
	 */
	labelSeparator: " : "

	,constructor: function(config) {

		config = config || {};

		NS.Enum.superclass.constructor.call(this, config);

		var codeLookup = this.codeLookup = {};
		var valueLookup = this.valueLookup = {};
		var items = this.values = [];

		if (!config.items) return;

//		Ext.iterate(config.items, function(value, item) {
		Ext.each(config.items, function(item) {

//			if (!item.value || !item.code) item.value = value;
			if (false == item instanceof NS.EnumValue) item = new NS.EnumValue(item);

			items.push(item);
			valueLookup[item.value] = item;
			codeLookup[item.code || item.value] = item;
		});

		this.doCreateField = this.createComboBox;
	}

	,getCodeLabel: function(code, type) {
		var e = this.codeLookup[code];
		if (!e) return undefined;
		return e.getLabel(type);
	}

	,getValueLabel: function(value, type) {
		var e = this.valueLookup[value];
		if (!e) return undefined;
		return e.getLabel(type);
	}
	
	/**
	 * Get the index in the array this.values of the EnumValue with value equal
	 * to the given value.
	 */
	,getValueIndex: function(value) {
		var vv = this.values,
			l = vv.length,
			o = this.valueLookup[value];
		
		for (var i=0; i<l; i++) {
			if (vv[i] === o) return i;
		}
		
		throw new Error("Invalid value for enum: " + value);
	}

	,extractDisplayValue: function(value) {
		return this.getValueLabel(value);
	}

	,createReadOnlyField: function(config) {
		return Ext.apply({
			enumField: this
			,xtype: "enumdisplayfield"
		}, config);
	}

	,testValue: function(testedVar, enumCode, strict) {
		if (enumCode === null || enumCode === undefined) {
			if (strict) return testedVar === enumCode;
			else return testedVar === null || testedVar === undefined || testedVar === "";
		}
		if (enumCode in this.codeLookup == false) {
			throw new Error('Enum has no code: ' + enumCode);
		}
		var ev = this.codeLookup[enumCode].value;
		if (strict) {
			return (testedVar === ev);
		} else {
			return (testedVar == ev);
		}
	}

	,createCheckboxGroupItems: function(config) {
		var r = [];
		Ext.each(this.values, function(ev) {
			r.push(Ext.apply({
				boxLabel: this.getLabel(["formField", "form"])
				,checked: ev.isDefault()
				,inputValue: ev.value
				,enumValue: ev
			}, config))
		});
		return r;
	}

	,createRadioGroup: function(config) {
		return Ext.apply({
			xtype: "radiogroup"
			,fieldLabel: this.getLabel(["formField", "form"])
			,defaults: {name: this.name}
			,items: (config ? config.items : false) || this.createCheckboxGroupItems()
		}, config);
	}
	,createRadios: function(config) {
		return this.createRadioGroup(config);
	}
	
	,createCheckbox: function(config) {
		
		var findRG = function(i) { return i instanceof Ext.form.RadioGroup; };
			
		var findValueField = function(field, p) {
			return p.items.find(function(i) { return i.name === field.name; });
		};
		
		var sync = function(cb) {
			
			if (!cb.checked) return;
			
			var p = cb.findParentByType("compositefield"),
				radios = p.items.find(findRG),
				vField = findValueField(this, p);

			radios.items.each(function(radio) {
				if (radio !== cb) radio.setValue(false);
			});
				
			vField.setValue(cb.enumValue.value);
		};
		
		var checkHandler = function(cb, checked) {
			var p = cb.findParentByType("compositefield"),
				radios = p.items.find(findRG),
				vField = findValueField(this, p);
				
			if (checked) {
				var sel, first;
				radios.items.each(function(radio) {
					if (!first) first = radio;
					radio.enable();
					if (radio.getValue()) sel = radio;
				});
				// sync value field
				if (!sel) first.setValue(true);
				else vField.setValue(sel.enumValue.value);
			} else {
				radios.items.each(function(radio) { radio.disable() });
				// sync value field
				vField.setValue(0);
			}
		};
		
		return function(config) {
			var zero = this.valueLookup[0];
			if (!zero) {
				throw new Error("Enum must have a zero-value to be created as checkbox");
			}
			
			var zDef = zero.isDefault();

			var radios = this.createRadioGroup(
				Ext.apply({
					items: this.createCheckboxGroupItems({
						disabled: zDef
						,handler: sync.createDelegate(this)
						,name: ""
					})
				}, !config ? config : (config.radioGroup || config.radios))
			);
			var rItems = radios.items;
			rItems.splice(this.getValueIndex(0), 1);

			// initial value
			var value = "";
			if (zDef) {
				value = 0;
			} else {
				Ext.each(rItems, function(radio) {
					if (radio.checked) {
						value = radio.enumValue.value;
						return false;
					}
				});
			}
			
			var r= Ext.apply({
				xtype: "compositefield"
				,items: [
					{
						xtype: "checkbox"
						,boxLabel: this.getLabel(["formField", "form"]) + this.labelSeparator
						,checked: !zDef
						,handler: checkHandler.createDelegate(this)
						,submitValue: false
					}
					,{
						xtype: "hidden"
						,value: value
						,name: this.name
						,width: 50
//						,setValue: function(v) {
//							TODO
//						}
					}
					,radios
				]
				,modelField: this
			}, config);

			return Ext.create(r);
		}
	}()

	,createComboBoxData: function() {
		var r = {};
		if (this.allowNull) {
			r['null'] = eo.Text.get(this.undefinedText, 'select') || "Non renseigné"; // i18n
		}
		Ext.each(this.values, function(item) {
			r[item.value] = this.getLabel(["formField", "form"])
		});
		return r;
	}

	,createComboBox: function(config) {
		return Ext.apply({
			xtype: "oce.simplecombo"
			,field: this.name
			,fieldLabel: this.getLabel(["formField", "form"])
			,data: this.createComboBoxData()
		}, config);
	}
});

NS.Enums = eo.Object.create({

	constructor: function(config) {

		config = config || {};
		if (!config.items) config = {items: config || {}};

		this.items = {};

		Ext.iterate(config.items, this.addFieldEnum.createDelegate(this));
	}

	,addFieldEnum: function(fieldName, item) {
		if (false == item instanceof NS.Enum) item = new NS.Enum(Ext.apply({
			name: fieldName
		}, item));
		this.items[fieldName] = item;
	}

	,getByField: function(fieldName) {
		return this.items[fieldName];
	}
	
});

NS.EnumsGroup = eo.Object.create({

	constructor: function(config) {
		config = config || {};

		var items = this.items = {};

		Ext.iterate(config, function(modelName, enums) {
			if (false == enums instanceof NS.Enums) {
				// Ensure items opt is present
				if (!enums.items) enums = {
					items: enums
					,modelName: modelName
				};
				// Create Enums
				enums = new NS.Enums(enums);
			}
			items[modelName] = enums;
		});
	}

	,get: function(index) {
		if (!this.items[index]) {
			throw new Error('Missing Enum group at index ' + index);
		}
		return this.items[index];
	}
})

NS.BooleanField = eo.Object.extend(NS.EnumField, {

	constructor: function(config) {

		if (!config.items) config.items = [{
			label: 'Oui', // i18n
			'default': config.hasDefault && config.defaultValue,
			code: 'YES',
			value: 1
		}, {
			label: 'Non', // i18n
			'default': config.hasDefault && !config.defaultValue,
			code: 'NO',
			value: 0
		}];

		NS.BooleanField.superclass.constructor.call(this, config);

		this.createField = this.createCheckbox;
	}

	,createCheckbox: function(config) {
		return Ext.apply({
			xtype: "checkbox"
			,name: this.name
			,fieldLabel: this.getLabel(["formField", "form"])
//			,boxLabel: this.label
			,checked: this.defaultValue !== false && this.defaultValue !== 0
//			,data: this.createComboBoxData()
			,modelField: this
//			,setValue: function(v) {
//				debugger
//				Ext.form.Checkbox.prototype.apply(this, arguments);
//			}
		}, config);
	}

	,extractValue: function(value) {
		if (value === undefined) return undefined;
		if (Ext.isString(value)) value = parseInt(value);
		return value ? true : false;
	}

	,createGridColumnEditor: function(config) {
		return "checkbox";
	}

	,doCreateGridColumn: function(config) {
		return Ext.applyIf(NS.BooleanField.superclass.doCreateGridColumn.call(this, config), {
			width: 42
			// TODO this is a GridField specific field, but it should propably 
			// tried to be used somewhat, when creating a standard grid store...
			,storeFieldConfig: {
				convert: function(v) {
					return v !== false 
						// in order to consider 0 or "0" as false...
						&& parseInt(v) !== 0;
				}
			}
		});
	}
});

Ext.apply(NS.ModelField.constructors, {
	'enum': NS.EnumField
	,'bool': NS.BooleanField
});

}); // eo.cqlix closure
