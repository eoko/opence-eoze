(function() {
	
var NS = Ext.ns('Oce.Modules.GridModule');

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 17 nov. 2011
 */
NS.FormConfig = Ext.extend(Object, {

	formLayout: undefined
	,winLayout: "auto"

	,constructor: function(gm, action, defaults) {

		this.gm = gm;
		var columns = gm.columns;
		this.action = action;
		this.defaults = defaults;

		this.fields = [];
		this.originalIndexes = []; // used to create tabs

		this.stickingTop = []; // tmp
		this.stickingBottom = []; // tmp

		Ext.each(columns, function(col) {
			this.processCol(col);
		}, this);

		// Flush sticking fields
		for (var i = this.stickingTop.length-1; i>=0; i--)
			this.fields.unshift(this.stickingTop[i]);

		Ext.each(this.stickingBottom, function(field) {
			this.fields.push(field);
		}, this);

		// Vertical flex
		this.processVerticalFlexFields();

		delete this.stickingTop;
		delete this.stickingBottom;
	}

	,onConfigureForm: function(formConfig) {
		if (Ext.isDefined(this.formLayout)) {
			formConfig.layout = this.formLayout;
		}
		if (this.gm.extra && this.gm.extra.labelAlign) {
			formConfig.labelAlign = this.gm.extra.labelAlign;
		}
		return formConfig;
	}

	// private
	,processVerticalFlexFields: function() {
		var verticalFlexFields = [],
			previousFields = [];

		Ext.each(this.fields, function(fieldConfig) {
			if (Ext.isDefined(fieldConfig.verticalFlex)) {
				// previous
				if (previousFields.length) {
					verticalFlexFields.push({
						xtype: "container"
						,layout: "form"
						,items: previousFields
						,defaults: this.defaults
					});
					previousFields = [];
				}
				// flexed field
				var fsp = Ext.ComponentMgr.types[fieldConfig.xtype],
					fspp = fsp.prototype;
				if (fsp === Oce.form.GridField
						|| fspp instanceof Oce.form.GridField
						|| fsp === Ext.Panel
						|| fspp instanceof Ext.Panel) {
					Ext.apply(fieldConfig, {
						flex: fieldConfig.verticalFlex
						,cls: (fieldConfig.cls || "") + " eo-gm-form-has-label-header"
						,padding: "0 0 3px 0"
					});
					if (fieldConfig.labelHeader) {
						Ext.apply(fieldConfig, {
							title: fieldConfig.fieldLabel 
									+ Ext.layout.FormLayout.prototype.labelSeparator
						});
					}
				} else {
					var lht = fieldConfig.labelHeader ? fieldConfig.fieldLabel : false;
					fieldConfig = {
						xtype: "panel"
						,cls: "eo-gm-form-has-label-header"
						,border: false
						,layout: "fit"
						,items: fieldConfig
						,flex: fieldConfig.verticalFlex
						,padding: "0 0 3px 0"
					};
					if (lht) {
						Ext.apply(fieldConfig, {
							title: lht + Ext.layout.FormLayout.prototype.labelSeparator
						});
					}
				}
				verticalFlexFields.push(fieldConfig);
			} else {
				previousFields.push(fieldConfig);
			}
		}, this);

		if (verticalFlexFields.length) {
			if (previousFields.length) {
				verticalFlexFields.push({
					xtype: "container"
					,layout: "form"
					,items: previousFields
					,defaults: this.defaults
				})
			}
			this.fields = {
				xtype: "container"
				,layout: {
					type: "vbox"
					,align: "stretch"
				}
				,items: verticalFlexFields
			};
			this.winLayout = this.formLayout = "fit";
		}
	}

	// private
	,inherit: function(a, b) {
		Ext.iterate(b, function(k, v) {
			if (a[k] === undefined) {
				a[k] = v;
			} else if (Ext.isObject(a[k])) {
				a[k] = this.inherit(a[k], v);
			}
		}, this);
		return a;
	}

	// private
	,inheritNode: function(obj, src, name) {
		if (name in src) {
			if (name in obj) {
				obj[name] = this.inherit(obj[name], src[name]);
			} else {
				obj[name] = src[name];
			}
		}
	}

	// private
	,applyProp: function(name, obj, src, defaultSrc) {
		if (obj.name !== undefined) return;
		else if (src.name !== undefined) obj[name] = src[name];
		else if (defaultSrc.name !== undefined) obj[name] = defaultSrc[name];
	}

	// private
	,applyProps: function(obj, src, defaultSrc, props) {
		// TODO: investigate what name should be, bellow (instead of an
		// undeclared var ...)
		Ext.each(props, function(prop) {
			// (see TODO upper) my guess:
			this.applyProp(prop, obj, src, defaultSrc);
			//applyProp(name, obj, src, defaultSrc);
		}, this);
		return obj;
	}

	// private
	,processCol: function(col) {

		if (col.form === false || col.internal === true) {
			this.originalIndexes.push(null);
			return;
		}

		var action = this.action,
			defaults = this.defaults;

		var colForm = col.form || {},
			fieldsThatUseOtherFields = [];

		// --- Inherit
		var config = {}

		if (Ext.isObject(col.form)) {
			this.inherit(config, col.form);
		}

		if (action in col) {
			if (col[action] === false) {
				this.originalIndexes.push(null);
				return;
			}
			Ext.apply(config, col[action]);
		}

		this.inheritNode(config, col, 'formField');
		
		this.inherit(config, {
			name: col.name,
			fieldLabel: colForm.fieldLabel || col.header,
			'allowBlank': col.allowBlank !== undefined ? col.allowBlank : defaults.allowBlank
		});
		
		if (!config.xtype && !config.readOnly) {
			config.xtype = col.type !== undefined ? col.type : defaults.type;
		}

		// --- Process
		if ('formField' in config) {
			config = Ext.apply({
				fieldLabel: col.header
				,allowBlank: col.allowBlank
			}, config.formField);
			if (col.useFields) {
				fieldsThatUseOtherFields.push(config);
			}
		} else {
			if ('hidden' in config) {
				config.xtype = 'hidden';
				delete config.hidden;
			} else if ('readOnly' in config) {
				if (!config.xtype) {
					config.xtype = (config.submit === false || config.submitValue === false) ?
						'displayfield' : 'oce.submitdisplayfield';
				} else if (config.xtype === 'datefield') {
					config.xtype = 'datedisplayfield';
				}
				delete config.readOnly;
				delete config.submit;
			}

			if (col.primary) config.itemId = '*id*';

			this.applyProps(config, col, defaults, [
				'maxLength', 'minLength','inputType'
			]);
		}

		// RegExp
		var regex = config.regex;
		if (regex) {
			if (Ext.isString(regex)) {
				config.regex = new RegExp(regex.replace(/\\/g, '\\\\'));
			}
		}

		// --- Store result
		this.originalIndexes.push(config);

		// Sticking fields
		var stick = config.stick;
		if (stick) {
			if (stick === 'top') {
				this.stickingTop.push(config);
			} else if (stick === 'bottom') {
				this.stickingBottom.push(config);
			} else {
				throw new Error('Invalid stick config (must be top|bottom): ' + stick);
			}
			delete config.stick;
		} else {
			this.fields.push(config);
		}
		
		// --- Second pass to process fields that use other fields
		// That is, fields that have 'useField' config option to TRUE.
		// That means that some items of their 'formField' config can
		// contain indexes that must be replaced by actual field config.
		Ext.each(fieldsThatUseOtherFields, function(field) {
			var tabBuilder = new NS.TabBuilder(this);
			if (field.items) {
				tabBuilder.convertItemIndexes(field.items);
			}
		}, this);
	}

});
})(); // closure