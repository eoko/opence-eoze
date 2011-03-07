Oce.deps.wait("eo.cqlix.Model", function() {
	
	eo.cqlix.Model.form = eo.Class({
		
		constructor: function(model) {
			this.model = model;
		}

		/**
		 * Creates an {@link Ext.form.CheckboxGroup} from the specified fields.
		 * The model fields matching the fields argument specification are 
		 * expected to be able to create checkbox fields.
		 * 
		 * The fields can be specified:
		 * - by a string matching the name or the alias of the model field
		 * - by a regex matching the name or alias of the desired model fields
		 * - by an array that can contains strings and/or regexes as specified
		 * above
		 * 
		 * The fields specification can be passed directly as the first argument
		 * to the method, or the first argument can be a config object (replacing
		 * the second argument of the method), in which case, the fields
		 * specification must be included in this object as the member fields.
		 * 
		 * The fields specification is mandatory.
		 */
		,createCheckboxGroup: function (fields, config, itemConfig) {
			
			if (!eo.isRegex(fields) && !Ext.isArray(fields) 
				&& Ext.isObject(fields)) {
				
				config = Ext.apply(Ext.apply({}, fields), config);
				fields = config.fields;
				delete config.fields;
				
				if (!fields) throw new Error("Illegal Arguments");
			}
			
			if (config && !itemConfig && config.checkboxConfig) {
				itemConfig = config.checkboxConfig;
			}
			
			var items = config && config.items ? 
					config : this.createCheckboxGroupItems(fields, itemConfig);
				
			return Ext.apply({
				xtype: "checkboxgroup"
				,items: items
			}, config);
		}
		
		// private
		,createCheckboxGroupItems: function (fields, config) {
			
//			if (!config && Ext.isObject(fields) && fields.fields) {
//				config = Ext.apply({}, fields);
//				delete config.fields;
//				fields = fields.fields;
//			}
			
			var r = [];
			Ext.each(this.model.getFields(fields), function (field) {
				r.push(field.createCheckbox(config));
			});

			return r;
		}
	});
	
});