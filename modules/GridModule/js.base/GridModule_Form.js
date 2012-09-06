Ext.apply(Oce.GridModule, {

	createForm: function(name) {
		var config;
		if ((config = this.forms[name]) === undefined) {
			throw new Exception('Missing form: ' + name);
		}
		return new Oce.FormPanel(config);
	}
})