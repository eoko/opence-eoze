Oce.Security = function() {

//	var sessionPingInterval = 5*60*1000;
	var sessionPingInterval = 60000; // First check after 1 min
	var identified;
	var eventManager = new Oce.EventManager(this);
	var pingTimeout;
	var loginInfos;
	var appLoaded;

	function pingSession() {
		if (identified) {
			Oce.Ajax.request({
				params: {
					action: 'ping_session'
				}
				,waitMsg: false
				,onSuccess: function(data, obj) {
					if (!obj.pong) {
						setIdentified(false, obj);
					} else {
						setTimeout(pingSession, data.remainingTime * 1000)
					}
				}
			});

//			pingTimeout = setTimeout(pingSession, sessionPingInterval);
		}
	}

	function setIdentified(flag, args) {
		if (flag === identified) return;

		identified = flag;
		if (pingTimeout) {
			clearTimeout(pingTimeout);
		}

		if (identified) {
			appLoaded = true;
			loginInfos = args;
			eventManager.fire('login');
			setTimeout(pingSession, sessionPingInterval);
		} else {
			eventManager.fire('logout', args);
		}
	}

	this.notifyDisconnection = function(args) {
		setIdentified(false, args);
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
//		return; // TODO remove debug
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
			);
		}
	};

	this.logout = function() {
		setIdentified(false);
	};

	this.get = function() {
		Oce.Security = new Oce.Security();
		return Oce.Security;
	};

	appLoaded = Oce.Security.initIdentified;
	setIdentified(Oce.Security.initIdentified, Oce.Security.loginInfos);

	return this;
};

Oce.Functionality('Security', Oce.Security);
