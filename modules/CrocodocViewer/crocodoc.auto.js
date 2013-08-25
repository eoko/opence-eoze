/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

(function() {

	var NS = Ext.ns('eo.doc');

	NS.view = function(doc) {
		Ext.Ajax.request({
			url: "index.php"
			,params: {
				filename: doc.data.filename
				,controller: "croco.json"
			}
			,success: function(response, opts) {
				var o = Ext.decode(response.responseText);

				var win = new Ext.Window({
					width: 640
					,height: 480
					,title: "Document Viewer"
					,html: String.format(
						'<iframe src="http://crocodoc.com/view/?sessionId={0}" width="100%" height = "100%" frameborder="0"></iframe>',
						o.sessionId
					)
				});
				win.show();
			}
		});
	}

})(); // closure
