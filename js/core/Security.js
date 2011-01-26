Oce.Security = function() {

//	var sessionPingInterval = 5*60*1000;
	var sessionPingInterval = 60000; // First check after 1 min
	var identified;
	var eventManager = new Oce.EventManager(this);
	var pingTimeout;
	var loginInfos;

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
			eventManager.fire('login');
			loginInfos = args;
			setTimeout(pingSession, sessionPingInterval);
		} else {
			eventManager.fire('logout', args);
		}
	}

	this.notifyDisconnection = function(args) {
		setIdentified(false, args);
	}

	this.isIdentified = function() {
		return identified;
	}

	this.getLoginInfos = function() {
		return loginInfos;
	}

	var loginModule = null;

	this.requestLogin = function(modal, text) {
		if (loginModule !== null) {
			loginModule.start(modal, text);
		} else {
			Oce.ModuleManager.requireModuleByName('Oce.Modules.root.login',
				function(loginModule) {
					loginModule.on('login', function(infos) {
						setIdentified(true, infos);
					});
					loginModule.start(modal, text);
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

	setIdentified(Oce.Security.initIdentified, Oce.Security.loginInfos);

	return this;
}

Oce.Functionality('Security', Oce.Security);
