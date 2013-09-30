(function() {
	
var sp = Ext.util.Observable,
	spp = sp.prototype;
	
Ext.ns("Oce");

Oce.BaseModule = Ext.extend(sp, {
	
	moduleActions: []
	
	,constructor: function() {
		this.initModuleActions();
		return spp.constructor.apply(this, arguments);
	}
	
	// private
	,initModuleActions: function() {
		var ma = this.moduleActions,
			bma = this.baseModuleActions || {};
		
		// merge base module actions from prototypes
		var me = this.prototype;
		while (me) {
			var mbma = me.baseModuleActions;
			if (mbma && mbma !== bma) {
				Ext.iterate(mbma, function(name, fn) {
					if (bma[name] === undefined) {
						bma[name] = mbma[name];
					}
				});
			}
			me = me.prototype;
		}
			
		if (ma) {
			if (Ext.isArray(ma)) {
				var nma = this.moduleActions = {};
				Ext.each(ma, function(name) {
					nma[name] = bma[name];
				})
			} else {
				Ext.iterate(ma, function(name, fn) {
					if (!Ext.isFunction(fn)) {
						var a = bma[fn];
						if (a) ma[name] = a;
					}
				}, this);
			}
		}
	}
	
	,executeAction: function(name, callback, scope, args) {
		if (Ext.isObject(name)) {
			callback = name.callback || name.fn;
			scope = name.scope || this;
			args = name.args;
			name = name.action || name.name;
		}
		return this.moduleActions[name].call(this, callback, scope, args);
	}
	
});

})(); // closure