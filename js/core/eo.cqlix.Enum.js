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

	constructor: function(config) {

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

	,createCheckboxGroupItems: function(create) {
		var r = [];
		Ext.each(this.values, function(item) {
			r.push({
				boxLabel: item.label
				,checked: item.isDefault()
				,inputValue: item.value
			})
		});
		if (create) return Ext.create(r);
		else return r;
	}

	,createRadioGroup: function(config) {
		return Ext.apply({
			xtype: "radiogroup"
			,fieldLabel: this.label
			,defaults: {name: this.name}
			,items: this.createCheckboxGroupItems()
		}, config);
	}

	,createComboBoxData: function() {
		var r = {};
		if (this.allowNull) {
			r['null'] = eo.Text.get(this.undefinedText, 'select') || "Non renseigné"; // i18n
		}
		Ext.each(this.values, function(item) {
			r[item.value] = item.label
		});
		return r;
	}

	,createComboBox: function(config) {
		return Ext.apply({
			xtype: "oce.simplecombo"
			,field: this.name
			,fieldLabel: this.label
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
//			'default': getDefault() === true,
			code: 'YES',
			value: 1
		}, {
			label: 'Non', // i18n
//			'default': getDefault() === true,
			code: 'NO',
			value: 1
		}];

		NS.BooleanField.superclass.constructor.call(this, config);

		this.createField = this.createCheckbox;
	}

	,createCheckbox: function(config) {
		return Ext.apply({
			xtype: "checkbox"
			,name: this.name
			,fieldLabel: this.label
			,checked: this.defaultValue !== false && this.defaultValue !== 0
//			,data: this.createComboBoxData()
		}, config);
	}
});

Ext.apply(NS.ModelField.constructors, {
	'enum': NS.EnumField
	,'bool': NS.BooleanField
});

}); // eo.cqlix closure
