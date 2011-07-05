Oce.form.AutoTextField = Ext.extend(Ext.form.TextArea, {

	constructor: function(config) {
		config.enableKeyEvents = true;
		Oce.form.AutoTextField.superclass.constructor.call(this, config);
	}

	,defaultAutoCreate : {tag: 'input', type: 'text', autocomplete: 'off'}

	,initComponent: function() {

		Oce.form.AutoTextField.superclass.initComponent.apply(this, arguments);

		var dataIndexes = [];
		Ext.iterate(this.fields, function(di, config) {
			dataIndexes.push(di);

			if (Ext.isString(config)) {
				colConfig = {
					header: config
					,dataIndex: di
				};
			}
		});

		this.store = new Ext.data.JsonStore({
			url: 'index.php',
			baseParams: Ext.apply({
				controller: this.controller
				,action: this.action || 'load_subset'
			}, this.baseParams || {}),
			root: 'data',
			fields: dataIndexes
			,autoload: true
		});
		if (this.rowId) this.store.baseParams.rowId = this.rowId;
		if (this.subset) this.store.baseParams.subset = this.subset;

		this.on('keypress', this.autocomplete.createDelegate(this), this, {buffer:1});
	}

//	,onKeyDown: function() {
//		Oce.form.AutoTextField.superclass.onKeyDown.apply(this, arguments);
//		this.autocomplete();
//	}

//	,onKeyPress: function(e) {
//		Oce.form.AutoTextField.superclass.onKeyPress.apply(this, arguments);
//		this.autocomplete();
////		this.autocomplete(e.keyCode);
//	}

	,lastValue: ''

	,autocomplete: function(t) {
		var v = t.getValue(), vp = this.lastValue;
		var cp = this.lastValue.length;
		for (var i=0,l=v.length; i<l; i++) {
			if (v[i] != vp[i]) {
				cp = i;
				break;
			}
		}
		this.lastValue = v;
		console.log(cp + ' ; v:' + v + ' ; vp:' + vp);
	}

	,onRender: function() {
		Oce.form.AutoTextField.superclass.onRender.apply(this, arguments);

	}

	,setBaseParam: function(name, value, reload) {
		if (this.store) {
			this.store.setBaseParam(name, value);
			if (reload) this.load();
//			this.clear();
		}
	}

	,load: function(opts) {
		if (this.store) this.store.load(opts);
	}

})

Ext.reg('oce.autotextfield', Oce.form.AutoTextField);