Ext4.require('Ext.ux.ActivityMonitor');

Oce.Security = function() {

	var identified;
	var eventManager = new Oce.EventManager(this);
	var loginInfos;
	var appLoaded;

	function setIdentified(flag, args) {
		if (flag === identified) return;

		identified = flag;

		if (identified) {
			appLoaded = true;
			loginInfos = args;
			eventManager.fire('login');

			startIdleMonitor();
		} else {
			eventManager.fire('logout', args);
		}
	}

	function startIdleMonitor() {
		Ext4.onReady(function() {
			var am = Ext4.ux.ActivityMonitor;
			am.init({
				maxInactive: 1000*60*30
				,interval: 1000*60
				//,verbose: true
				,isInactive: function() {
					logout();
				}
			});
			am.start();
		});
	}

	function logout() {
		setIdentified(false);
		eo.Ajax.request({
			params: {
				controller: 'AccessControl'
				,action: 'logout'
			}
		});
	}

	this.notifyDisconnection = function() {
		setIdentified(false, {
			// i18n
			message: "Vous avez été déconnecté suite à une longue période d'inactivité. Veuillez entrer "
				+ "vos identifiants pour continuer votre travail."
		});
	};

	this.isIdentified = function() {
		return identified;
	};

	this.getLoginInfos = function() {
		return loginInfos;
	};
	
	this.whenIdentified = function(fn) {
		if (this.isIdentified()) {
			fn();
		} else {
			Oce.mx.Security.onOnce('login', fn);
		}
	};

	var loginModule = null;

	this.requestLogin = function(modal, text) {
		var loginFn = appLoaded ? "showLoginWindow" : "start";
		if (loginModule !== null) {
			loginModule[loginFn](modal, text);
		} else {
			Oce.ModuleManager.requireModuleByName('Oce.Modules.AccessControl.login',
				function(m) {
					loginModule = m;
					m.on('login', function(infos) {
						setIdentified(true, infos);
					});
					m[loginFn](modal, text);
				}
			)
		}
	}

	this.logout = function() {
		setIdentified(false);
	}

	this.get = function() {
		Oce.Security = new Oce.Security();
		return Oce.Security;
	}

	appLoaded = Oce.Security.initIdentified;
	setIdentified(Oce.Security.initIdentified, Oce.Security.loginInfos);

	return this;
}

Oce.Functionality('Security', Oce.Security);
