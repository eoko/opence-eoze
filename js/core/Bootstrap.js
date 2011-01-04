/**
 * @author Éric Ortéga
 */

Oce.Bootstrap = function() {

//	Oce.fx.mainApplication = Oce.functionality.MainApplication.get();
//	Oce.fx.securityManager = Oce.functionality.Security.get();
//	Oce.fx = {};

	Oce.mx = {};
	Ext.iterate(Oce.functionality, function(name, fn){
		Oce.mx[name] = fn.get();
		//console.log('mx['+name+']: '+ Oce.mx[name]);
	})

	var firstLogin = true;
	
	return {

		start: function() {

			Oce.mx.Security.addListener('logout', function(src, info) {
				Oce.mx.Security.requestLogin(true, info.text || info.message);
			});
//			Oce.mx.Security.addListener('logout', function(){
//				Oce.mx.application.close(true, function(accepted){
//					if (!accepted) throw new 'IllegalState';
//					Oce.mx.Security.requestLogin();
//				});
//			})

			Oce.mx.Security.addListener('login', function() {
				if (firstLogin) {
					Oce.mx.application.start();
					firstLogin = false;
				}
			});

			if (Oce.mx.Security.isIdentified()) {
				firstLogin = false;
				Oce.mx.application.start();
			} else {
				Oce.mx.Security.requestLogin(false);
			}
		}
	}
}

Ext.onReady(function() {
	new Oce.Bootstrap().start();
})