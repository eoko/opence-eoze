// Prevent disturbing URL hints for anchor elements (eg. in tabs)
Ext.onReady(function() {
	setInterval(function() {
		Ext.query('[href=#]').forEach(function(a) {
			a.removeAttribute('href');
		});
	}, 500);
});
