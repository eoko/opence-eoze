Ext.ns('eo.cqlix');

(function() {

var NS = eo.cqlix;

NS.Model = eo.Object.create({

	constructor: function(config) {

		config = config || {};

		var fields = [], aliasLookup = {}, nameLookup = {};

		this.primaryKeyField = undefined;

		if (config.fields) {
			Ext.each(config.fields, function(field) {
				if (false == field instanceof NS.ModelField) {
					field = new NS.ModelField.create(field);
				}
				if (fields[field.name] !== undefined) {
					throw new Error('Field ' + field.name + ' already defined');
				}

				if (field.primaryKey) {
					this.primaryKeyField = field;
				}

				fields.push(field);
				nameLookup[field.name] = field;
				if (field.alias && field.alias !== field.name) {
					//fields[field.alias] = field;
					aliasLookup[field.alias] = field;
				}
			})
		}

		Ext.apply(this, config);

		fields.each = function(cb, scope) {
			if (!scope) scope = this;
			for (var i=0, l=fields.length; i<l; i++) {
				cb.call(scope, fields[i]);
			}
		};

		fields.findBy = this.findFieldsBy.createDelegate(this);
		
		this.fields = fields;
		this.nameLookup = nameLookup;
		this.aliasLookup = aliasLookup;
	}

	,getRelationModel: function(name) {
//		var baseRel = this.baseRel ? this.baseRel + '->' : '';
//		name = baseRel + name;
		if (!this.relations || !this.relations[name]) {
			debugger
			throw new Error();
		}
		else return this.relations[name];
	}

	,findFieldsBy: function(testFn) {
		return eo.findBy(this.fields, testFn);
	}

	,initRelations: function(relations) {
		var baseRel = this.baseRel ? this.baseRel + '->' : '';
		var rgBaseRel = new RegExp('^' + baseRel + '(.+)$');
		var r = this.relations = {};
		Ext.iterate(relations, function(name, model) {
			var m = rgBaseRel.exec(name);
			if (!m) return;
			name = m[1];
			
			function F(){
//				this.setBaseRelation(baseRel + name);
				this.baseRel = baseRel + name;
			}
			F.prototype = model;
			var rm = r[name] = new F();
			
			rm.initRelations(relations);
		});
	}

	,getField: function(name) {

		if (Ext.isObject(name)) {
			if (name instanceof NS.ModelField) {
				return name;
			} else if (name.name) {
				name = name.name;
			}
		}

		var field = this.aliasLookup[name] || this.nameLookup[name];

		var baseRel;

		if (field) {
			baseRel = this.baseRel;
			if (!baseRel) {
				return field;
			} else {
				function F() {
					this.name = baseRel + '->' + field.name;
				}
				F.prototype = field;
				return new F();
			}
		} else {
			var parts = name.split("->");
			if (parts.length > 1) {
				var nextRel = parts.shift();
				baseRel = nextRel;
				//
				if (this.baseRel) baseRel = this.baseRel + '->' + nextRel;

				return this.relations[nextRel] // parts[0] === 'OptProduit'
						 .getField(parts.join('->'), baseRel);
			}
		}

		throw new Error('No field: ' + name);
	}

	,getDataIndex: function(field) {
		return this.getField(field).name;
	}

	,createFormField: function(field, config) {
		return this.getField(field).createField(config);
	}

	,createGridColumn: function(field, config) {
		return this.getField(field).createGridColumn(config);
	}

	,createFormItems: function(config) {
		return eo.form.createModelFormItems(Ext.apply({
			model: this
		}, config));
	}

	,createForm: function(config) {
		config = config || {};
		if (config.ignoreBaseRel) this.ignoreBaseRel();
		var r = eo.form.createModelForm(Ext.apply({
			model: this
		}, config));
		if (config.ignoreBaseRel) this.restoreBaseRel();
		return r;
	}

	,ignoreBaseRel: function() {
		this.ignoredBaseRel = this.baseRel;
		this.baseRel = null;
	}

	,restoreBaseRel: function() {
		this.baseRel = this.ignoredBaseRel;
		delete this.ignoredBaseRel;
	}

	,createColumnModel: function(config) {
		if (!config) config = {}

		var cols = [];
		var override = Ext.apply({
			editable: config.editable
		}, config.override);


		var fields = config.fields;
		if (!fields) {
			Ext.each(this.fields, function(f) {
				var c = f.createGridColumn(override);
				if (c) cols.push(c);
			});
		} else {
			fields = eo.arrayToHash(fields, 'name');
			Ext.each(this.fields, function(f) {
				var cfg = fields[f.alias] || fields[f.name];
				if (cfg) {
					cfg = Ext.apply(Ext.apply({}, override), cfg);
				} else {
					cfg = override;
				}
				var c = f.createGridColumn(cfg);
				if (c) cols.push(c);
			});
		}

		if (config.addExtraColumns) {
			config.addExtraColumns(cols);
		}

		return cols;
	}

	,createRecord: function(values) {
		var F = function() {};
		F.prototype = this;

		var o = new F;
		Ext.apply(o, NS.Record);
//		NS.Record.constructor.call(o, values)

		o.model = o;
		o.values = values;

		return o;
	}
}); // Model

//NS.Record = eo.Object.create({
NS.Record = ({

//	constructor: function(values) {
//		this.model = this;
//		this.values = values;
//	}

	getRelationRecord: function(name) {
		return this.getRelationModel(name).createRecord(this.values);
//		var model = this.getRelationModel(name);
//		var values = {};
//		var regex = new RegExp('^' + name + '->');
//		Ext.iterate(this.values, function(k, v) {
//			var m = regex.exec(k);
//			if (m) {
//				values[k] = m[1];
//			}
//		});
//		return model.createRecord()
	}

	,getValue: function(field) {
		field = this.model.getField(field);
		return field.extractValue(this.values[field.name]);
	}

	,testValue: function(field, value, strict) {
		field = this.model.getField(field);
		return field.testValue(this.getValue(field), value, strict);
	}

	,getDisplayValue: function(field) {
		field = this.model.getField(field);
		return field.extractDisplayValue(this.values[field.name]);
	}
});

NS.Model.createBunch = function(items) {
	var r;
	if (Ext.isArray(items)) {
		r = [];
		Ext.each(items, function(item) {
			r.push(new NS.Model(item));
		});
	} else if (Ext.isObject(items)) {
		r = {};
		Ext.iterate(items, function(k, item) {
			r[k] = new NS.Model(item);
		});
	} else {
		throw new Error('Invalid argument type');
	}
	return r;
}

NS.ModelField = eo.Object.create({

	name: undefined
	,label: undefined
	,xtypeReadOnly: "displayfield"

	,constructor: function(config) {

		if (!config.name) {
			throw new Error();
		}

		Ext.apply(this, config);

		if (!this.alias) this.alias = this.name;
	}

	,isPrimaryKey: function() {
		return this.primaryKey;
	}

	,extractValue: function(value) {
		return value;
	}

	,extractDisplayValue: function(value) {
		return this.extractValue(value);
	}

	,testValue: function(tested, value, strict) {
		if (strict) {
			return tested === value;
		} else {
			return tested == value;
		}
	}

	,createReadOnlyField: function(config) {
		if (!this.xtypeReadOnly) return undefined;
		return Ext.apply({
			name: this.name
			,xtype: this.xtypeReadOnly
			,fieldLabel: this.getLabel(['readOnlyFormField','formField','form'])
		}, config);
	}

	,createField: function(config) {

		config = config || {};

		if (this.internal && !config.forceInternal) {
			if (this.isPrimaryKey()) {
				return this.createField(Ext.apply({
					forceInternal: true
					,xtype: "hidden"
				}, config));
			} else {
				return undefined;
			}

		} else if (config.readOnly) {
			return this.createReadOnlyField(config);

		}
		
		return this.doCreateField.call(this, config);
	} // createField

	// protected
	,doCreateField: function(config) {
		var cfg = {
			name: this.name
			,fieldLabel: this.getLabel(['formField','form'])
			,xtype: this.xtype
		};

		if (this.allowBlank !== undefined) {
			cfg.allowBlank = this.allowBlank;
		} else if (this.allowNull !== undefined) {
			cfg.allowBlank = this.allowNull;
		}

		Ext.apply(cfg, this.defaultConfig);
		return Ext.apply(cfg, config);
	}

	,createGridColumn: function(config) {

		if (this.internal === true) return null;

		config = config || {};
		
		var r = Ext.apply({
			dataIndex: this.name
			,header: this.getLabel(['grid','column','abbrev'])
//			,editor: this.createField(config.editor)
		}, config);

		if (config.editable) r.editor = this.createField(config.editor);

		return r;
	}

	,getLabel: function(type, defaultLabel) {
		return eo.Text.get(this.label, type) || defaultLabel || this.name;
	}
});

NS.StringField = Ext.extend(NS.ModelField, {
	xtype: "textfield"
	,constructor: function(config) {
		NS.StringField.superclass.constructor.apply(this, arguments);
		if (this.length) {
			(this.defaultConfig = this.defaultConfig || {}).maxLength = this.length;
		}
	}
//	createField: function(config) {
//		return NS.DateField.superclass.createField({
//			xtype: "textfield"
//		});
//	}
});

//NS.TextField = eo.Object.extend(NS.StringField, {
NS.TextField = Ext.extend(NS.StringField, {
	xtype: "textarea"
	,doCreateField: function(config) {
		return Ext.apply({
			height: 40
		}, NS.TextField.superclass.doCreateField.call(this, config));
	}
});

NS.NumberField = Ext.extend(NS.ModelField, {
	xtype: "numberfield"
	,constructor: function(config) {
		NS.NumberField.superclass.constructor.apply(this, arguments);
		var dc;
		if (this.length) {
			//(this.defaultConfig = this.defaultConfig || {}).maxLength = this.length;
			dc = this.defaultConfig = this.defaultConfig || {};
			if (this.decimals) {
				var length = this.decimals ? this.length : this.length - this.decimals;
				dc.maxValue = Math.pow(length, 10) - 1;
				dc.maxLength = this.length + 1; // 1 for the decimal separator
			} else {
				dc.maxValue = Math.pow(this.length, 10) - 1;
				dc.maxLength = this.length; // 1 for the decimal separator
			}
		}
	}
});

NS.IntegerField = Ext.extend(NS.NumberField, {
	defaultConfig: {
		allowDecimals: false
	}
});

NS.DecimalField = Ext.extend(NS.NumberField, {
});

NS.DateField = Ext.extend(NS.ModelField, {
	xtype: "datefield"

	,createReadOnlyField: function(config) {
		return NS.DateField.superclass.createReadOnlyField.call(this, Ext.apply({
			xtype: "datedisplayfield"
			,format: this.format
		}, config));
	}
});

NS.DateTimeField = Ext.extend(NS.DateField, {
	xtype: "compositefield"
	,doCreateField: function(config) {
		return Ext.apply({
			//,anchor: "-0"
			defaults: {flex: 1}
			,items: [{
				xtype: "datefield"
//				,name: "date_end"
				,format: "d/m/Y"
			}, {
				xtype: "timefield"
			}]
		}, NS.TextField.superclass.doCreateField.call(this, config));
	}
});

NS.ModelField.constructors = {
	field: NS.ModelField
	,'int': NS.IntegerField
//	,'string': NS.ModelField
	,'string': NS.StringField
	,text: NS.TextField
//	,'date': NS.ModelField
	,'date': NS.DateField
	,'time': NS.ModelField
//	,'datetime': NS.ModelField
	,'datetime': NS.DateTimeField
	,'bool': NS.ModelField
	,'float': NS.NumberField
	,'decimal': NS.DecimalField
};

NS.ModelField.create = function(config) {

	var constructor;
	if (!config.type) {
		//throw new Error('Missing type in config');
		constructor = NS.ModelField.constructors.field;
	} else {
		constructor = NS.ModelField.constructors[config.type];
	}

	if (!constructor) {
		throw new Error('Missing constructor for type: ' + (config.type || 'DEFAULT'));
	}

	return new constructor(config);
};

//NS.EnumField = eo.Object.extend(NS.Field, {
//	constructor: function(config) {
//
//		NS.EnumField.superclass.constructor.call(this, config);
//	}
//});

Oce.deps.reg('eo.cqlix.Model');

})(); // closure