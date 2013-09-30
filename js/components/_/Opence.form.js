
Ext.ns('eo.Locale').getDateFormat = function() {
	return 'd/m/Y';
};
eo.Locale.getDateTimeFormat = function() {
	return 'd/m/Y à H:i';
}

Oce.DefaultFormPanel = Ext.extend(Ext.FormPanel, {
//	initconf : [{
//		bodyStyle: 'padding:15px;background:transparent',
//		border: false
////		,
////		buttons : [
////			  {
////					 text:'Submit'
////					,formBind:true
////					,scope:this
////					,handler: this.reset
////				}, {
////				iconCls: 'ico_help',
////				handler: function() {
////					if (module_help) {
////						Ext.ux.OnDemandLoadByAjax.load(w_help);
////					}
////				}
////			}
////		]
//	}],
	constructor : function() {
		Ext.apply(this, {
			bodyStyle: 'padding:25px; background:transparent',
			border: false
		})
        Oce.DefaultFormPanel.superclass.constructor.apply(this,arguments);
	},
	onRender:function() {

        // call parent
        Oce.DefaultFormPanel.superclass.onRender.apply(this, arguments);

		// set wait message target
//		this.getForm().waitMsgTarget = this.getEl();
		this.getForm().waitMsgTarget =
//			this.ownerCt !== undefined ? this.ownerCt.getEl() :
			this.getEl();

		// loads form after initial layout
//		this.on('afterlayout', this.onLoadClick, this, {single:true});

    } // eo function onRender
,
	reset : function() {
		alert('rrr');
	}
});

Ext.ns('Oce.form.Action');

//Oce.form.Action.SubmitJson = Ext.extend(Ext.form.Action.Submit, {
//Ext.form.Action.ACTION_TYPES['jsonsubmit'] = Ext.extend(Ext.form.Action.Submit, {
//	run : function(){
//		var o = this.options,
//			method = this.getMethod(),
//			isGet = method == 'GET';
//			
//		if(o.clientValidation === false || this.form.isValid()){
//			if (o.submitEmptyText === false) {
//				var fields = this.form.items,
//				emptyFields = [];
//				fields.each(function(f) {
//					if (f.el.getValue() == f.emptyText) {
//						emptyFields.push(f);
//						f.el.dom.value = "";
//					}
//				});
//			}
//			
//			Oce.Ajax.request(Ext.apply(this.createCallback(o), {
//				form:this.form.el.dom,
//				jsonFormParam: o.jsonFormParam,
//				serializeForm: o.serializeForm,
//				url:this.getUrl(isGet),
//				method: method,
//				headers: o.headers,
//				params:!isGet ? this.getParams() : null,
//				isUpload: this.form.fileUpload
//			}));
//			
//			if (o.submitEmptyText === false) {
//				Ext.each(emptyFields, function(f) {
//					if (f.applyEmptyText) {
//						f.applyEmptyText();
//					}
//				});
//			}
//		}else if (o.clientValidation !== false){ // client validation failed
//			this.failureType = Ext.form.Action.CLIENT_INVALID;
//			this.form.afterAction(this, false);
//		}
//	}
//});

Oce.form.JsonForm = Ext.extend(Ext.form.BasicForm, {
    submit : function(options){
        options = options || {};
        if(this.standardSubmit){
            var v = options.clientValidation === false || this.isValid();
            if(v){
                var el = this.el.dom;
                if(this.url && Ext.isEmpty(el.action)){
                    el.action = this.url;
                }
                el.submit();
            }
            return v;
        }
//        var submitAction = String.format('{0}submit', this.api ? 'direct' : '');
//        this.doAction(submitAction, options);
        if (this.jsonFormParam) options.jsonFormParam = this.jsonFormParam;
		if (this.serializeForm) options.serializeForm = this.serializeForm;
		this.doAction('jsonsubmit', options);
        return this;
    }
});

Ext.ns('Oce.form');

/**
 * @param {Ext.form.BasicForm} form
 * @param {String} namePrefix
 */
Oce.form.getFormData = function(form, namePrefix) {
	
	// allow easy overriding of form data reading
	if (form.getData) {
		var d = form.getData(namePrefix);
		if (d) return d;
	}
	
	if (form instanceof Ext.form.BasicForm) form = form.el;
	
	var fElements = form.elements || (document.forms[form] || Ext.getDom(form)).elements,
		hasSubmit = false,
		element,
		name,
		data = {},
		type;

	Ext.each(fElements, function(element) {
		name = element.name;
		type = element.type;

		if (!element.disabled && name){
			
			if (namePrefix) name = namePrefix + name;

//			if (/^.+\[.+\]$/.test(name)) {
//				var parts = /^(.+)\[(.+)\]$/.exec(name);
//				if (!data[parts[1]]) data[parts[1]] = {};
//				data[parts[1]][parts[2]] = (
//					(opt.hasAttribute ?
//						opt.hasAttribute('value') : opt.getAttribute('value') !== null
//					) ? opt.value : opt.text
//				)
//			} else if(/select-(one|multiple)/i.test(type)) {
			if(/select-(one|multiple)/i.test(type)) {
				Ext.each(element.options, function(opt) {
					if (opt.selected) {
						data[name] = ((opt.hasAttribute ? opt.hasAttribute('value') : opt.getAttribute('value') !== null) ? opt.value : opt.text);
					}
				});
			} else if(!/file|undefined|reset|button/i.test(type)) {
				if (/checkbox/i.test(type)) {
					data[name] = element.checked ? 1 : 0;
				} else if (/radio/i.test(type)) {
					if (element.checked) {
						if (data[name]) throw new Error('Malformed Form: ');
						data[name] = element.value;
					}
				} else if (!(type == 'submit' && hasSubmit)) {
					data[name] = element.value;
					hasSubmit = /submit/i.test(type);
				}
			}
		}
	});

	return data;
}

Ext.ns('Oce.form');

Oce.form.LoadLatch = function(config) {

	config = config || {};

	this.loadLatchCount = this.loadLatches = config.loadLatches || 0;
	this.firstLoadLatchCount = config.firstLoadLatches || 0;
};

Oce.form.LoadLatch.prototype = {

	canLoad: function() {
		if (this.firstLoadLatchCount > 0) {
			this.firstLoadLatchCount--;
			return false;
		} else if (this.loadLatchCount > 0) {
			this.loadLatchCount--;
			return false;
		} else {
			this.loadLatchCount = this.loadLatches;
			return true;
		}
	}

	,add: function() {
		this.loadLatches++;
		this.loadLatchCount++;
	}

	,addFirst: function() {
		this.firstLoadLatchCount++;
	}
};

Oce.form.LoadLatch.getFrom = function(field) {
	if (field instanceof Ext.form.Field === false) {
		throw new Error('getFrom must be used to retrieve the LoadLatch Manager from'
			+ " a constructed object!")
	}

	if (field.loadLatchManager) {
		return field.loadLatchManager;
	} else {
		return field.loadLatchManager = new Oce.form.LoadLatch(field.loadLatches);
	}
};

Oce.form.LoadLatch.addTo = function(field) {
	// If we are passed a constructed object, we can add a manager to it. If we
	// are passed a config object however, we don't want to put a stateful
	// object in it; what we want is to store/alter initialization informations
	// for this object to be constructed later.
	if (field instanceof Ext.form.Field) {
		Oce.form.LoadLatch.getFrom(field).add();
	} else {
		var ll = field.loadLatches = field.loadLatches || {
			loadLatches: 0
			,firstLoadLatches: 0
		};
		ll.loadLatches++;
	}
};

Oce.form.LoadLatch.addFirstTo = function(field) {
	// If we are passed a constructed object, we can add a manager to it. If we
	// are passed a config object however, we don't want to put a stateful
	// object in it; what we want is to store/alter initialization informations
	// for this object to be constructed later.
	if (field instanceof Ext.form.Field) {
		Oce.form.LoadLatch.getFrom(field).addFirst();
	} else {
		var ll = field.loadLatches = field.loadLatches || {
			loadLatches: 0
			,firstLoadLatches: 0
		};
		ll.firstLoadLatches++;
	}
}