Ext.ns('Oce.Modules.AccessControl');
Oce.Modules.AccessControl.login = Ext.extend(Oce.Module, {

	namespace: 'Oce.Modules'
	,controller: 'AccessControl'
	,name: 'login'

	,constructor: function(config) {
		Oce.Modules.AccessControl.login.superclass.constructor.call(this, config);

		this.addEvents(
			/**
			 * @event login
			 * @param {Object} loginInfos
			 */
			'login'
		);
	}

	,createLoginWindow: function(config) {

		var win = Ext4.create('Eoze.AccessControl.view.LoginWindow', config),
			service = win.getController().getLoginService();

		// relay login event (legacy)
		service.on('login', function(service, loginInfos) {
			this.onLogin(loginInfos);
		}, this);

		return win;
	}
	
	/**
	 * @private
	 */
	,onLogin: function(loginInfos) {
		this.fireEvent('login', loginInfos);
	}

	,showHelp: function() {

		var win = new eoko.ext.IFrameWindow({
			title: "Aide: Login"
			,titlePrefix: "Aide : "
			,width: 400
			,height: 300
			,collapsible: true
			,url: "http://wiki.eoko-lab.fr"
			,whiteList: /[^.]\.eoko(?:-lab)?\.fr$/
			,panel: {
				showButtonsText: false
				,loadingText: "Chargement..."
				,proxy: "proxy.php?url="
				,prepareUrlForProxyRequest: function(uri) {
					uri.queryKey.min = 1;
				}
			}
			,history: {
				back: {iconCls: "fugico_arrow-180", text: ""}
				,forward: {iconCls: "fugico_arrow", text: ""}
				,reload: {iconCls: "arrow-circle-315", text: ""}
			}
			,baseUrl: "http://wiki.eoko-lab.fr"
		});
		win.show();
	}

	,start: function(modal, text) {
		Ext.getBody().addClass('bg');
		this.createLoginWindow({
			modal: modal
			,message: text
			,exitButton: false
		}).show();
	}
	
	,showLoginWindow: function(modal, text) {
		this.createLoginWindow({
			modal: modal
			,message: text
			,exitButton: true
		}).show();
	}

});


Oce.Modules.AccessControl.login = new Oce.Modules.AccessControl.login();

Oce.deps.reg('Oce.Modules.AccessControl.login');
