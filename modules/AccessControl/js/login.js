Oce.Modules.AccessControl.login = Ext.extend(Oce.Module, {

	namespace: 'Oce.Modules'
	,controller: 'AccessControl'
	,name: 'login'

	,constructor: function(config) {
		Oce.Modules.AccessControl.login.superclass.constructor.call(this, config);

		this.addEvents(
			'login'
		);
	}

	,createLoginWindow: function(modal, text) {
		this.loginForm = new Oce.DefaultFormPanel();
		this.loginWindow = new Oce.DefaultWin({
			 title: 'Identification'
			,closable: false
			,maximizable: false
			,width: 380
			,modal: modal
		});

		this.submitForm = function() {

			var login_form = this.loginForm.getForm();

			if (login_form.isValid()) {
				login_form.submit({

					params: {
						action: 'login'
						,controller: 'AccessControl.login'
					},

					waitMsg: 'Veuillez patienter' ,
					waitTitle : 'Interrogation du serveur',

					success: function(form, action) {
						var obj = Ext.util.JSON.decode(action.response.responseText);
						this.loginWindow.close();
						this.fireEvent('login', obj.loginInfos);
					}.createDelegate(this),

					failure: function(form, action) {

						if(action.failureType == 'server') {
							var obj = Ext.util.JSON.decode(action.response.responseText);
							Ext.Msg.alert('L\'identification a échoué!', obj.errors.reason);
						} else {
							Ext.Msg.alert('Attention!',
								'Le serveur d\'authentification est innaccessible. Raison : '
								+ action.response.responseText
								+ "<br/>Veuillez contacter l'administrateur système."
								);
						}
					}
				});
			} else {
				Ext.MessageBox.alert("Erreur","Les informations que vous avez fourni ne sont pas corrects.")
			}
		}

		this.loginForm.getForm().url = 'index.php';
		this.loginForm.add([
			{
				xtype: 'box',
				autoEl: {
					tag: 'div',
					html: '<div class="app-msg">'
					+ (text ? text : "<?php _jsString($text) ?>")
					+ '<br /><br />'
					+ '</div>'
				}
			}, {
				xtype: 'textfield',
				id: 'login-user',
				fieldLabel: 'Identifiant',
				allowBlank: false,
				minLength: 3,
				maxLength: 8,
				listeners: {
					specialkey: {
						fn: function(field, el) {
							if (el.getKey() == Ext.EventObject.ENTER) {
								this.submitForm();
							}
						}
						,scope: this
					}
				}
			}, {
				xtype: 'textfield',
				id: 'login-pwd',
				fieldLabel: 'Mot de passe',
				inputType: 'password',
				allowBlank: false,
				minLength: 6,
				maxLength: 32,
				listeners: {
					specialkey: {
						fn: function(field, el) {
							if (el.getKey() == Ext.EventObject.ENTER) this.submitForm();
						}
						,scope: this
					}
				}
			}
		]);

		this.loginWindow.add([this.loginForm]);
	//	w_login.width = 300;
		this.loginWindow.doLayout();

		this.loginWindow.addButton([
		{
			text: 'Ok'
			,handler: this.submitForm.createDelegate(this)
		}, {
			text: 'Annuler',
			handler: this.loginWindow.collapse.createDelegate(this.loginWindow)
//		}, {
//			text: 'Réinitialiser',
//			handler: this.loginForm.getForm().reset.createDelegate(this.loginForm)
		}
/*<?php if ($help): ?>*/
		, {
			iconCls: 'ico_help',
//			handler: function() {
//				Ext.ux.OnDemandLoadByAjax.load(w_help);
//			}.createDelegate(this)
			handler: this.showHelp
		}
/*<?php endif ?>*/
		]);

		return this.loginWindow;
	}

	,showHelp: function() {
//			var win = new Oce.w({
//				 items: new Oce.AutoloadPanel({
//					 controller: 'help'
//					,action: 'get_topic'
//					,name: 'login'
//					,collapsible: false
//					,titleCollapse: false
//				})
//				,title: "Aide: Login"
//				,width: 350
//				,height: 180
//	//					,layout: 'fit'
//				,collapsible: true
//			});

// --- wrapper.php ---
//<#php
//$url = $_REQUEST['url'];
//$baseUrl = 'http://wiki.eoko-lab.fr/';
//$html = file_get_contents($url);
//$html = preg_replace(
//	//'@(<img[^>]+src=(?:"|\'))(.+?)((?:"|\')[^>]*?)>@',
//	'@(<[^>]*src=("|\'))(.+?)(\2[^>]*?)>@',
//	//'$1caca$3',
//	"$1$baseUrl$3$4",
//	$html
//);
//$html = preg_replace(
//	'@(<[^>]*href=("|\'))(.+?)(\2[^>]*?)>@',
//	"$1$baseUrl$3$4",
//	$html
//);
//echo $html;
// --- end wrapper.php ---

//		var p;
//		var req = function(url) {
//			Ext.Ajax.request({
//				url: "http://localhost/wrapper.php"
//				,params: {
//					url: url
//				}
//				,success: function(response) {
//					p.update(response.responseText)
//				}
//			})
//		}
//		var win = new Ext.Window({
//			width: 320, height: 240
//			,items: p = new Ext.Panel({
//				tbar: {
//					items: [{
//						text: "Prev"
//					}, {
//						text: "Next"
//					}]
//				}
//				,listeners: {
//					afterrender: function() {
//						req('http://wiki.eoko-lab.fr')
//					}
//				}
//				,html: "Go on..."
//			})
//		});

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
//			,url: "proxy.php?url=http://www.google.fr"
			,baseUrl: "http://wiki.eoko-lab.fr"
		});
		win.show();
//		var win = new Ext.Window({
//			width: 250
//			,height: 110
//			,autoLoad: {
//				url: "http://www.google.com"
//			}
//		});
//
//		win.show();
	}

	,start: function(modal, text) {
//		if (isset(mx.MainApplication)) mx.MainApplication.shutdown();
		Ext.getBody().addClass('bg');
		this.createLoginWindow(modal, text).show();
	}

});


Oce.Modules.AccessControl.login = new Oce.Modules.AccessControl.login();

Oce.deps.reg('Oce.Modules.AccessControl.login');