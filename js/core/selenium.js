(function() {

	// window := selenium.browserbot.getCurrentWindow().wrappedJSObject

	var NS = Ext.ns('eo.selenium');

	var requests = [];

	NS.startSession = function() {
		Ext.Ajax.on('beforerequest', function(conn, opts) {
			if (!opts.isDebug) {
				NS.lastRequest = opts;
				requests.push(opts);
			}
		});
	};

})();
