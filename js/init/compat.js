/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 19 sept. 2012
 */
if (Ext.Compat) {
//	Ext.Compat.silent = true;
}

else if (console && console.warn) {

console.warn('Dead code');

Ext.ns('Ext.toolbar');
Ext.toolbar.TextItem = Ext.Toolbar.TextItem;
Ext.grid.View = Ext.grid.GridView;


(function() {

	var extend = Ext.extend,
		noArgs = [];

	Ext.extend = function() {

		var c = extend.apply(this, arguments);

		Ext.iterate(c.prototype, function(k, e) {
			if (Ext.isFunction(e)) {
				e.$owner = c;
				// This test is needed to support aliasing of functions as a class member
				// 
				// Like in:
				// 
				// MyClass = Ext.extend(AnotherClass, {
				// 
				//   AliasedClass: com.demo.AliasedClass
				// 
				// });
				// 
				// Without the test, the $name for com.demo.AliasedClass will be changed
				// to AliasedClass (instead of expected 'constructor').
				//
				if (!e.$name) {
					e.$name = k;
				}
			}
		});

		c.prototype.callParent = function(args) {
			var m = this.callParent.caller;
			return m.$owner.superclass[m.$name].apply(this, args || noArgs);
		};

		return c;
	};

})();

Ext.ns('Ext.Function');
Ext.Function.createSequence = function (originalFn, newFn, scope) {
	if (!newFn) {
		return originalFn;
	}
	else {
		return function() {
			var result = originalFn.apply(this, arguments);
			newFn.apply(scope || this, arguments);
			return result;
		};
	}
};

(function() {
var reg = Ext.reg;
var resolve = function(name) {
	if (!Ext.isString(name)) {
		return name;
	} else {
		var o = window;
		Ext.each(name.split('.'), function(sub) {
			o = o[sub];
		});
		return o;
	}
};
Ext.reg = function(xtype, cls) {
	return reg(xtype, resolve(cls));
};
})(); // closure

}

Ext.widget = Ext.create;
