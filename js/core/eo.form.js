Ext.ns('eo.form');

eo.form.createModelFormItems = function(opts) {

	if (!opts) throw new Error();
	var model = opts.model;
	if (!model) {
		model = opts;
		opts = {};
	}
	if (false == model instanceof eo.cqlix.Model) {
		throw new Error('eo.form.createModelFormItems');
	}

	var fields = [];
	Ext.each(
		model.fields.findBy(function(f) { return !f.internal || f.isPrimaryKey() })
		,function (f) {
			var ff = f.createField(opts);
			if (ff) fields.push(ff);
		}
	);

	return fields;
};

eo.form.createModelForm = function(opts) {

	if (!opts) throw new Error();
	var model = opts.model;
	if (!model) {
		model = opts;
		opts = {};
	}
	if (false == model instanceof eo.cqlix.Model) {
		throw new Error('eo.form.createModelForm (1)');
	}

	var fields = [];
	Ext.each(
		model.fields.findBy(function(f) { return !f.internal || f.isPrimaryKey() })
		,function (f) {
			fields.push(f.createField());
		}
	);

	if (opts.items) throw new Error('eo.form.createModelFormItems (2)');

	if (opts.formClass === false) {
		return Ext.apply({
			items: fields
		}, opts.formConfig);
	}

	var cls = opts.formClass || Oce.FormPanel || Ext.FormPanel;

	return new cls(Ext.apply({
		items: fields
	}, opts.formConfig));
};