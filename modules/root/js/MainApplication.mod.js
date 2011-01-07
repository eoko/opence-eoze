Ext.namespace('Oce.root')

Oce.deps.wait('Oce.GridModule.YearCombo', function() {

	Oce.root.application = function() {

		var init = function(config) {
			/* Tool panel ***************/
			Oce.mx.application.YearManager = new Oce.YearCombo({
				year: config.year
				,width: 100
			});

			var loginInfos = Oce.mx.Security.getLoginInfos();
			if (!loginInfos) throw new Error();

			var m_favoris, m_general, m_admin;
			var menuPanelOpts = {
				tools: [{id: "gear"}]
				,border: false
				,collapsible: true
				,titleCollapse: true
			};
			var menus = {
				bookmarks: m_favoris = new Ext.Panel(Ext.applyIf({
					title: "Favoris"
				}, menuPanelOpts))
				,general: m_general = new Ext.Panel(Ext.applyIf({
					title: "Général"
					,collapsed: true
				}, menuPanelOpts))
				,admin: m_admin = new Ext.Panel(Ext.applyIf({
					title: "Administration"
				}, menuPanelOpts))
			};

			if (!loginInfos.restricted) {
				(function() {
					var names = [];
					Ext.iterate(menus, function(name,v) {names.push(name);});

					var updatePanel = function(panel, content) {
						if (panel.el) {
							panel.update(content);
						} else {
							var l = function() {
								panel.update(content);
								panel.un('afterrender', l);
							};
							panel.on('afterrender', l);
						}
					}

					Oce.Ajax.request({
						params: {
							controller: "root.menu"
							,action: "bunchGet"
							,json_names: Ext.encode(names)
						}
						,onSuccess: function(obj) {
							Ext.iterate(menus, function(name, panel) {
								updatePanel(panel, obj.content[name]);
							});
						}
					});
				})();
			}

			var actionPanel = new Ext.Panel({
				id:'action-panel',
				region:'west',
				title : 'Navigation',
				split:true,
				collapsible: true,
				contentEl : 'west',
				width:170,
				minSize: 170,
				maxSize: 400,
				border: true,
				autoScroll:false
				,tbar : (function(){
					if (loginInfos.restricted) {
						return [{
							xtype: "button"
							,text: "Quitter"
							,handler: function() {
								Oce.Ajax.request({
									waitMsg: true,
									params: {action: 'logout'},
									onSuccess: function() {
										window.location = 'index.php'
									}
								});
							}
						}]
					} else {
//						return undefined;
//						return [{
//							iconCls: 'refresh',
//							tooltip: 'rafraichir',
//							handler: function() {
//								Ext.getCmp('m_menu/bookmarks').refresh();
//								Ext.getCmp('m_menu/general').refresh();
//								Ext.getCmp('m_menu/admin').refresh();
//							}
//						}];
					}
				})()
			});
				actionPanel.add(new Ext.Toolbar({
					items: [{
							xtype: "tbtext"
							,text: "Exercice :"
						}, " ",
						Oce.mx.application.YearManager
					]
				}));

				if (loginInfos.restricted) {
					actionPanel.add(new Oce.AutoloadPanel ({
						 name: 'membre_menu'
						,title: 'Actions'
						,controller: 'root.html'
						,action: 'page'
						,collapsed: false
						,tools: [{
//							id:'gear'
			//				,handler: dummy_alert
						}]
					}));
				} else {
					actionPanel.add([m_favoris,m_general,m_admin]);
				}

				actionPanel.doLayout();


			// Header & Bottom componement
			var c_top = new Oce.AutoloadPanel({

				name: 'top',
				controller: 'root.html',
				action: 'page',

				params: {
					page: 'top'
				},

				idPrefix: 'c_',
				region: 'north',
				height: 64,
				contentEl: 'north',
				baseCls : 'top_header',
				collapsible: false
				,listeners: {
					afterRender: function() {
						c_top.body.on('mousedown', doAction, null, {delegate:'a'});
					}
				}
			});

//			var c_bottom = new Oce.AutoloadPanel({
//				name: 'bottom',
//				idPrefix: 'c_',
//				region : 'south',
//				height: 48,
//				baseCls : 'bottom',
//				collapsible: false
//			});

			// Center default

			var items = [], b_center;

			if (!loginInfos.restricted) {
				items.push(c_top);

//				b_center = new Ext.TabPanel({
//					id:'center',
//		//			autoDestroy: false,
//					region: 'center',
//					deferredRender: false,
//					activeTab: 0
//		//			,scrollanle : false
////					,bbar: [{
////						height: 24
////						,id: "windowDock"
////					}]
//				});

				b_center = new Ext.Container({
					region: "center"
					,id: "center-region"
					,layout: {
						type: "vbox"
						,align: "stretch"
					}
					,listeners: {
						afterrender: function(me) {
							(function() {Oce.mx.TaskBar.hide()}).defer(500);
//							Oce.TaskBarToolbar.superclass.hide.call(Oce.mx.TaskBar);
//							Oce.TaskBarToolbar.displayed = false;
						}
					}
					,items: [{
						xtype: "tabpanel"
						,activeTab: 0
						,deferredRender: false
						,id:'main-destination'
						,flex: 1
					}, Oce.mx.TaskBar = new Oce.TaskBarToolbar({
						height: 26
						,id:"window-dock"
//						,hidden: true
//						,items: [{
//							text: "Salut !!!"
//							,handler: function() {
//								testDock();
//							}
//						},{
//							text: "Salut !!!"
//						}]
					})]
//					,items: [{
//						xtype: "container"
//						,layout: {
//							type: "hbox"
//							,align: "stretch"
//						}
//						,items: [{
//							xtype: "tabpanel"
//							,activeTab: 0
//							,deferredRender: false
//							,id:'center'
//							,flex: 1
//						},{
//							xtype: "panel"
//							,height: 24
//							,id: "windowDock"
//							,html: "Hey hey hey"
//	//						,items: [{
//	//							text: "Salut"
//	//						},{
//	//							text: "Fuck U ."
//	//						}]
//						}]
//					}]
				});

			} else {
				Ext.getBody().addClass('bg');
				b_center = new Ext.Container({
					id: "center"
					,region: 'center'
					,frame: true
//					,bodyStyle: "background: transparent"
				});
			}

			items.push(actionPanel, b_center /*,c_bottom*/);
//			items.push(actionPanel, b_center, c_bottom);

			var viewport = new Ext.Viewport({
				layout: 'border',
				items: items
			});

	/*Action menu */
	/* ------------Action param */
			var actions = {
				'l_know_more': function(){
					var win_know_more = Ext.getCmp('win_know_more');
					if (!win_know_more) {
						win_know_more = new Oce.w({
							id: 'win_know_more',
							title: 'En savoir plus',
							autoLoad: 'pages/know_more.php'
						});
						win_know_more.show();
					}
			},
				'l_help': function(){
					if (!win_help) {
						var win_help = new Oce.w({
							id: 'win_help',
							title: 'Aidez moi',
							autoLoad: 'pages/help.php'
						});
					}

					win_help.show();
			},
			'l_contact': function(){
				var form = new Ext.form.FormPanel({
						baseCls: 'x-plain',
						layout:'absolute',
						url:'save-form.php',
						defaultType: 'textfield',
						items: [{
							x: 0,
							y: 5,
							xtype:'label',
							text: 'Send To:'

						},{
							x: 60,
							y: 0,
							name: 'to',
							anchor:'100%',
							blankText: 'admin@eoko.fr'
						},{
							x: 0,
							y: 35,
							xtype:'label',
							text: 'Subject:'
						},{
							x: 60,
							y: 30,
							name: 'subject',
							anchor: '100%'  // anchor width by percentage
						},{
							x:0,
							y: 60,
							xtype: 'htmleditor',
							name: 'msg', autoScroll: true, maxLength: 300,
							anchor: '100% 100%'  // anchor width and height
						}]
					});
					var win_contact = new Oce.w({
						title: 'Envoyer un mail',
						id :'win_contact',
						buttonAlign:'center',
						items: form,
						buttons: [{
							text: 'Envoyer'
						},{
							text: 'Annuler'
						}]
					});
				win_contact.show();
			},
			'l_disco': function(){
				Oce.Ajax.request({
					waitMsg: true,
					params: {
						action: 'logout',
						controller: 'root.login'
					},
					onSuccess: function() {
						window.location = 'index.php'
					}
				});
			},
			'test': function(){
				var tab = new Ext.getCmp('center').add({
					id : 'prout',
					title : 'title',
					html : 'my tab',
					closable : true
				});
				tab.show();
			},
				'm_members': function(){
					load_members();
			},
				'm_tourisme': function(){
					load_test();
			},
				'm_biblio': function(){
					load_tab();
			},
				'm_colo': function(){
					load_tab();
			},
			'm_users': function() {Oce.Application.loadModule('users')},
			'm_countries': function(){Oce.Application.loadModule('users')},
				'm_agences': function(){
					load_agences();
			},
				'm_contacts': function(){
					load_contacts();
			},
				'm_levels': function(){
					load_levels();
			}
			}

	/*------------Do action */
	   function doAction(e, t){
			e.stopEvent();
			actions[t.id]();
		}

		}

		return {

			start: function() {

				var doInit = function(config) {

					// Loading mask
//					var mask = new Ext.LoadMask(Ext.getBody(), {
//						msg:"Chargement de la page en cours<br/>Veuillez Patientez quelques instants..."
//					});
//					mask.show();

					this.initialConfig = config;

					// Init tasks
					this.initStateProvider();
					Ext.QuickTips.init();

					// Legacy
					init.call(this, config);

					// Clear mask
//					mask.hide();

					var logInfo = Oce.mx.Security.getLoginInfos();
					if (!logInfo) throw new Error();
					if (logInfo.restricted) {
//						Oce.mx.application.getModuleInstance('Oce.Modules.contacts.contacts', function(module) {
//							module.editRowById(logInfo.contactId);
//						});
//						Oce.mx.application.getModuleInstance('Oce.Modules.membres.membres', function(module) {
//							module.editRowById(logInfo.membreId);
//						});

						(new Oce.DefaultWin({
							width: 500
							,height: 400
							,layout: "fit"
							,title: "Espace adhérent"
							,items: [{
								xtype: "tabpanel"
								,activeTab: 0
								,defaults: {
									xtype: "container"
								}
								,items: [{
									title: "News",
									html:"<h1>Ouverture du nouveau site</h1><p>pien at nunc pulvinar tincidunt. Proin nulla augue, pretium nec ultrices non, vulputate et justo. Donec dui est, aliquet vulputate vestibulum sit amet, sollicitudin in magna. Nam libero purus, auctor et convallis eget, accumsan vel dui.Mauris cursus lorem rutrum sapien pellentesque ultricies. Suspendisse at ipsum ut arcu sagittis tempus. Vestibulum aliquet, arcu id mollis rhoncus, ipsum sapien lacinia eros, at</p>"
								},{
									title : "Le cie",
									html:"Le CIE c'est..."
								},{
									title: "horaire d\'ouverture",
									html:"<h1>Ouverture le matin et l'apres midi</h1>"
								}]
							}]

						})).show()
					}

					// Open default modules
//					Oce.mx.application.open('Oce.Modules.accueil.accueil');
//					Oce.mx.application.open('Oce.Modules.users.users');
//					Oce.mx.application.open('Oce.Modules.notes.notes');
//					Oce.mx.application.open('Oce.Modules.countries.countries');
//					Oce.mx.application.open('Oce.Modules.contacts.contacts');
//					Oce.cmd('Oce.Modules.contacts.contacts', 'addRecord')();
//					Oce.mx.application.open('Oce.Modules.membres.membres');
//					Oce.mx.application.open('Oce.Modules.facturations.facturations');
//					Oce.cmd('Oce.Modules.facturations.facturations#editRowById(2)')();
//					Oce.cmd('Oce.Modules.facturations.facturations', 'addRecord')();
//					Oce.cmd('Oce.Modules.tickets_cinema.tickets_cinema', 'open')();
//					Oce.cmd('Oce.Modules.SMManager.SMManager#editRowById(15)')()
//					Oce.cmd('Oce.Modules.sm_child_47.sm_child_47', 'open')();
//					Oce.cmd('Oce.Modules.SMManager.SMManager', 'open')();
//					Oce.cmd('Oce.Modules.SMManager.SMManager#addRecord')();
//					Oce.mx.application.open('Oce.Modules.backups.backups');
//					Oce.mx.application.open('Oce.Modules.tranche_groupes.tranche_groupes');
//					Oce.mx.application.open('Oce.Modules.tranches.tranches');
//					Oce.mx.application.open('Oce.Modules.agences.agences');
//					Oce.mx.application.open('Oce.Modules.years.years');
//					Oce.mx.application.open('Oce.Modules.modules.modules');
//					Oce.cmd('Oce.Modules.loisirs.loisirs', 'addRecord')();
//					Oce.cmd('Oce.Modules.loisirs_saisons.loisirs_saisons#addRecord')();
//					testWizard();
//					testMediaWindow();

				}.createDelegate(this)

				Oce.Ajax.request({
					params: {
						controller: 'root.application'
						,action: 'configure'
//						,module: 'application'
					}
					,onSuccess: function(data, response) {
						doInit(response.config);
					}
				})
			}

			,initStateProvider: function() {
				Ext.state.Manager.setProvider(new Ext.state.CookieProvider());
			}

			,destroySessionData: function(id) {
				Oce.Ajax.request({
					params: {
						controller: 'root'
						,action: 'destroy_session_data'
						,id: id
					}
					,waitMsg: false
				});
			}
		}
	}()

	Ext.onReady(Oce.root.application.start.createDelegate(Oce.root.application))

}); // <= deps
