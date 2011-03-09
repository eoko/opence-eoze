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

				// catches primary key
				if (field.primaryKey) {
					this.primaryKeyField = field;
					this.primaryKeyName = field.name;
				}

				fields.push(field);
				nameLookup[field.name] = field;
				if (field.alias && field.alias !== field.name) {
					//fields[field.alias] = field;
					aliasLookup[field.alias] = field;
				}
			}, this);
		}

		Ext.apply(this, config);

		// fields functions
		fields.each = function(cb, scope) {
			if (!scope) scope = this;
			for (var i=0, l=fields.length; i<l; i++) {
				cb.call(scope, fields[i]);
			}
		};

		fields.findBy = this.findFieldsBy.createDelegate(this);

		// Instance vars
		this.fields = fields;
		this.nameLookup = nameLookup;
		this.aliasLookup = aliasLookup;
		
		if (NS.Model.aspects) Ext.iterate(NS.Model.aspects, function(n, a) {
			this[n] = new a(this);
		}, this);

		// Fields specials etc
		fields.each(function(field) {
			if (field.onModelCreate) field.onModelCreate(this);
		}, this);
	}

	,getRelationModel: function(name) {
//		var baseRel = this.baseRel ? this.baseRel + '->' : '';
//		name = baseRel + name;
		if (!this.relations || !this.relations[name]) {
			debugger
			throw new Error();
		}
		else return this.relations[name];
//		if (!this.relations || !this.relations[name]) {
//			debugger
//			throw new Error();
//		} else {
//			var r = this.relations[name];
//			var br = name + "->", brlen = br.length;
//			var foreignRel = {};
//			Ext.iterate(this.relations, function(name, rel) {
//				if (name.substr(0, brlen) === br) {
//					// push relation in returned model
//					foreignRel[name.substr(brlen)] = rel;
//				}
//			});
//			function F() {
//				this.relations = foreignRel;
//			}
//			F.prototype = r;
//			return new F();
//		}
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

		// if ModelField is passed directly, returns it unchanged
		// (but what does that happen ???)
		if (Ext.isObject(name)) {
			if (name instanceof NS.ModelField) {
				return name;
			} else if (name.name) {
				// what is this case ???
				debugger
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
				// propagate baseRel to the returned field
				function F() {
					this.name = baseRel + '->' + field.name;
				}
				F.prototype = field;
				return new F();
			}
		} else {
			// try to find a relation or a relation field
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

	/**
	 * Retrieves an array of fields.
	 * @param {String|Array|RegExp} field
	 */
	,getFields: function(fields) {

		if (Ext.isString(fields)) {
			return this.getField(fields);
		} else {
			var r = [];

			if (Ext.isArray(fields)) {
				Ext.each(fields, function(field) {
					r.push(this.getFields(field));
				});
			} else if (eo.isRegex(fields)) {
				var h = {};
				Ext.iterate(this.nameLookup, function(n, f) {
					if (fields.test(n)) h[n] = f;
				});
				Ext.iterate(this.aliasLookup, function(n, f) {
					if (fields.test(n)) h[f.name] = f;
				});
				r = eo.hashToArray(h);
			} else {
				throw new Error("Illegal Argument: " + (typeof fields));
			}

			return r;
		}
	}
	
	// Get a specified version of this model, as one of its subclass
	// experimental, not tested, etc... do not use!!!
	,as: function(subclass) {
		throw new Error();
		// Get the subclass which is necessarilly a hasOne relation
		var subClassModel = this.getRelationModel(subclass);

		var al = this.aliasLookup;
		function F() {
			var myAl = this.aliasLookup = Ext.apply({}, this.al);
			subClassModel.fields.each(function(field) {
				myAl[field.name] = field;
			});
		}

		return new F();
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

	// this allow for hooking by plugins
	,fieldCreateGridColumnMethodName: "createGridColumn"

	,createColumnModel: function(config) {
		if (!config) config = {}

		var cols = [];
		var override = Ext.apply({
			editable: config.editable
		}, config.override);

		var cgcFn = this.fieldCreateGridColumnMethodName;
		var fields = config.fields;
		if (!fields) {
			Ext.each(this.fields, function(f) {
				var c = f[cgcFn](override);
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
				var c = f[cgcFn](cfg);
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

/**
 * Adds an aspect to the Model. The aspect constructor will be called when a
 * Model is instanciated, with the model instance as only argument. The 
 * constructed object will be appended to the Model as its name member.
 * @param {String} name This is the dude!
 * @param {Function} constructor
 * @author Éric Ortéga <eric@planysphere.fr>
 * @since 09/03/11 01:31
 */
NS.Model.addAspect = function(name, constructor) {
	NS.Model.aspects = (NS.Model.aspects || {})[name] = constructor;
};

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

//NS.ModelField = eo.Object.create({
NS.ModelField = eo.Class({

	name: undefined
	,label: undefined
	,xtypeReadOnly: "displayfield"

	,constructor: function(config) {

		if (!config.name) {
			throw new Error();
		}

		Ext.apply(this, config);

		if (!this.alias) this.alias = this.name;

		if (this.special) NS.SpecialFields[this.special](this);
	}
	
	/**
	 * Called when the model owning this field is constructed, to provide an
	 * opportunity to configure the model...
	 */
	,onModelCreate: function(model) {}
	
	/**
	 * Tests whether the given string or regex matches this field's name or
	 * alias.
	 * @param {String|Regex} name
	 * @return Boolean TRUE if the name arugment is a String and it equals this 
	 * field's name or alias, or if name is a RegExp and it matches this field's
	 * name or alias; else return FALSE.
	 */
	,testName: function(name) {
		if (eo.isRegex(name)) {
			return name.test(this.name) || name.test(this.alias);
		} else if (Ext.isString(name)) {
			return name === this.name || name === this.alias;
		} else {
			throw new Error("Illegal Argument");
		}
	}

	,isPrimaryKey: function() {
		return this.primaryKey;
	}

	/**
	 * protected
	 * Converts the given value to the natural internal representation for this
	 * type of field.
	 */
	,extractValue: function(value) {
		return value;
	}

	/**
	 * protected
	 * Converts the given value to the natural representation for the end user
	 * for this type of field. The passed value is expected to be in the
	 * internal (ie. storage) type for this kind of field.
	 */
	,extractDisplayValue: function(value) {
		return this.extractValue(value);
	}

	/**
	 * protected
	 * Tests whether the passed testedVar equals the passed value. The tested
	 * variable type is expected to be in the internal (storage) type of this
	 * field, while the value passed for comparison is expected to be in the
	 * working type for this field (ie. the type used between the coder and the
	 * code -- eg. for an enum, the internal type would most often be an integer,
	 * while the working type would be a string representing the constant code).
	 */
	,testValue: function(testedVar, value, strict) {
		if (strict) {
			return testedVar === value;
		} else {
			return testedVar == value;
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

		} else {
			if (config.readOnly
					// if config.readOnly === false, we want to override this.readOnly value
					|| (this.readOnly && (config.readOnly !== false))) {

				return this.createReadOnlyField(config);

			} else {

				return this.doCreateField.call(this, config);
			}
		}
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
//		if (this.internal === true) return null;
		if (this.internal === true) return this.doCreateGridColumn(Ext.apply({internal: true}, config));
		else return this.doCreateGridColumn(config);
	}

	,doCreateGridColumn: function(config) {

		config = config || {};
		
		var r = Ext.apply({
			dataIndex: this.name
			,id: this.name
			,header: this.getLabel(['grid','column','abbrev'])
		}, config);

		if (config.editable) r.editor = this.createGridColumnEditor(config.editor);

		return r;
	}

	,createGridColumnEditor: function(config) {
		return this.createField(config);
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

	,doCreateGridColumn: function(config) {
		return Ext.applyIf(NS.StringField.superclass.doCreateGridColumn.call(this, config), {
			// TODO this is a GridField specific field, but it should propably
			// tried to be used somewhat, when creating a standard grid store...
			storeFieldConfig: {
				convert: function(v) {
					if (!v) return "";
					else return v;
				}
			}
		});
	}
//	createField: function(config) {
//		return NS.DateField.superclass.createField({
//			xtype: "textfield"
//		});
//	}
});

NS.TextField = Ext.extend(NS.StringField, {
	xtype: "textarea"
	,constructor: function(config) {
		if (config.format === "html" && !(this instanceof NS.HtmlField)) {
			return new NS.HtmlField(config);
		} else {
			return NS.TextField.superclass.constructor.call(this, config);
		}
	}
	,doCreateField: function(config) {
		return Ext.apply({
			height: 40
		}, NS.TextField.superclass.doCreateField.call(this, config));
	}
});

NS.HtmlField = Ext.extend(NS.TextField, {
	xtype: "htmleditor"
	,doCreateField: function(config) {
		return Ext.apply(NS.HtmlField.superclass.doCreateField.call(this, config), {
			height: 120
			,anchor: "100%"
		});
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
			// Format is set at a global level
			//,format: this.format
		}, config));
	}
	
	,doCreateGridColumn: function(config) {
		return Ext.apply(NS.DateField.superclass.doCreateGridColumn.call(this, config), {
			xtype: "datecolumn"
			,format: eo.locale.dateFormat
			,storeFieldConfig: {
				type: "date"
			}
		});
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

NS.RelationOneField = Ext.extend(NS.StringField, {
	xtype: "oce.foreigncombo"
	,doCreateField: function(config) {
		var controller = config.controller || this.controller;
		if (!controller) {
			//throw new Error("Cannot create field without knowing the controller");
			if (console && console.warn) {
				console.warn("Cannot create field without knowing the controller for: " + this.name);
			}
			return null;
		}
		return Ext.apply({
			column: this.name
			,controller: controller
			,autoComplete: this.autoCompleteParam || this.name
			,editable: this.editable === true
			,forceSelection: true
		}, NS.RelationOneField.superclass.doCreateField.call(this, config));
	}
});

NS.RelationManyField = Ext.extend(NS.ModelField, {
	xtype: "gridfield"
	,doCreateField: function(config) {
		
	}
})

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
	// Relations
	,'hasOne': NS.RelationOneField
	,'hasMany': NS.RelationManyField
};

NS.ModelField.create = function(config) {

	var type = config.fieldType || config.type;
	var constructor;
	if (!type) {
		//throw new Error('Missing type in config');
		constructor = NS.ModelField.constructors.field;
	} else {
		constructor = NS.ModelField.constructors[type];
	}

	if (!constructor) {
		throw new Error('Missing constructor for type: ' + (type || 'DEFAULT'));
	}

	return new constructor(config);
};

//NS.EnumField = eo.Object.extend(NS.Field, {
//	constructor: function(config) {
//
//		NS.EnumField.superclass.constructor.call(this, config);
//	}
//});

/**
 * Known dependencies:
 * - GridField.CqlixPlugin overrides eo.cqlix.Model & eo.cqlix.ModelField on this
 * - eo.cqlix.Model.form
 */
Oce.deps.reg('eo.cqlix.Model');

NS.SpecialFields = {

	orderable: function(field) {
		field.internal = true;
		field.onModelCreate = field.onModelCreate.createSequence(function(model) {
			if (model.orderField) throw new Exception("Model can have only one order field");
			model.orderable = true;
			model.orderField = this;
		})
	}

	,main: function(field) {
		field.onModelCreate = field.onModelCreate.createSequence(function(model) {
			if (!model.mainField) model.mainField = this;
		});
	}
};

})(); // closure