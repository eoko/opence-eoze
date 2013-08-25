Ext.ns('Oce');

Oce.Environments = {

	jstest: function() {
		var target = Oce.Context.target;
		if (!target) {
			alert("Missing jstest target: " + target);
		} else {
			target = eval(target + ".test");
			if (Ext.isFunction(target)) {
				target();
			} else {
				debugger
			}
		}
	}
};