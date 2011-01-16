//Oce.deps.wait('Oce.Bootstrap.start', function() {
//
//	var userId;
//	if (Oce.mx.Security.isIdentified()) {
//		userId = Oce.mx.Security.getLoginInfos().userId;
//		poll();
//	}
//	Oce.mx.Security.addListener('login', function(info) {
//		userId = info.userId;
//		poll();
//	});
//
//	function poll() {
//		Ext.Ajax.request({
//			url: "comet.php"
//			,params: {
//				id: userId
//			}
//			,success: function(response) {
//				var o = Ext.decode(response.responseText);
//				if (o.alert) {
//					alert(o.alert);
//				}
//				poll();
//			}
//			,failure: function() {
//				poll()
//			}
//		});
//	}
//});