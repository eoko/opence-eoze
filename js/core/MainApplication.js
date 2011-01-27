(function() {

	var NS = Ext.ns("eo.root");

	NS.MainApplication = eo.Class({

		start: function() {

			// Loading mask
			var mask = new Ext.LoadMask(Ext.getBody(), {
				msg: "<h1><strong>Chargement de l'application</strong></h1>"
					+ "<p>Veuillez patientez quelques instants..."
			});
			mask.show();

			// Log infos
			var logInfo = Oce.mx.Security.getLoginInfos();
			if (!logInfo) throw new Error();

			// Init tasks
			this.initStateProvider();
			Ext.QuickTips.init();

			// Legacy
			this.loadConfiguration(function(config) {

				this.onConfigure(config);

				this.doStart(config);

				// Clear mask
				mask.hide();

				this.afterStart();
			}.createDelegate(this));
		}

		,onConfigure: function(config) {}

		,initStateProvider: function() {
			Ext.state.Manager.setProvider(new Ext.state.CookieProvider());
		}

		,loadConfiguration: function(callback) {
			var me = this;
			Oce.Ajax.request({
				params: {
					controller: 'root.application'
					,action: 'configure'
				}
				,onSuccess: function(data, response) {
					var cfg = me.initialConfig = response.config;
					callback(cfg);
				}.createDelegate(this)
			});
		}

		,doStart: function(config) {

			Ext.getBody().removeClass('bg');

			this.onStart(config);

			var items = [];

			Ext.each(["Center", "North", "East", "South", "West"], function(region) {
				var fn = this["create" + region];
				if (fn) {
					var item = fn.call(this, config);
					if (item) items.push(item);
				}
			}, this);

			var viewport = new Ext.Viewport({
				layout: 'border',
				items: items
			});
		}

		,afterStart: function() {
			// Open default modules
		}

		,onStart: function() {}

		,createCenter: function() {
			return new Ext.Container({
				region: "center"
				,id: "center-region"
				,layout: {
					type: "vbox"
					,align: "stretch"
				}
				,listeners: {
					afterrender: function(me) {
						(function() {Oce.mx.TaskBar.hide()}).defer(500);
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
				})]
			});
		}

		,createNorth: function() {
			return new Oce.AutoloadPanel({

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
	//					this.body.on('mousedown', doAction, null, {delegate:'a'});
					}
				}
			});
		}

		,createEast: function() {

		}

		,createSouth: function() {
			return false;
			return new Oce.AutoloadPanel({
				name: 'bottom',
				idPrefix: 'c_',
				region : 'south',
				height: 48,
				baseCls : 'bottom',
				collapsible: false
			});
		}

		,createWest: function() {

			var menuPanelOpts = {
				tools: [{id: "gear"}]
				,border: false
				,collapsible: true
				,titleCollapse: true
			};

			var menus = {
				bookmarks: new Ext.Panel(Ext.applyIf({
					title: "Favoris"
				}, menuPanelOpts))
				,general: new Ext.Panel(Ext.applyIf({
					title: "Général"
					,collapsed: true
				}, menuPanelOpts))
				,admin: new Ext.Panel(Ext.applyIf({
					title: "Administration"
				}, menuPanelOpts))
			};

			var names = [], items = [];
			Ext.iterate(menus, function(name, v) {
				names.push(name);
				items.push(v);
			});

			var menuPanel = this.createWestMenu(items);

			if (!menuPanel) return null;

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
			};

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

			return menuPanel;
		}

		,createWestMenu: function(items) {
			return {
				id: "action-panel"
				,region:'west'
				,title : 'Navigation'
				,split:true
				,collapsible: true
				,contentEl : 'west'
				,width:170
				,minSize: 170
				,maxSize: 400
				,border: true
				,autoScroll:false
				,items: items
			};
		}

	}); // NS.MainApplication

	// Static
	Ext.apply(NS.MainApplication, {

		controller: "root"

		,appClass: NS.MainApplication

		,start: function() {
			if (!this.app) this.app = this.appClass.create();
			this.app.start();
		}

		,create: function() {
			return new this();
		}

		,destroySessionData: function(id) {
			Oce.Ajax.request({
				params: {
					controller: this.controller
					,action: 'destroy_session_data'
					,id: id
				}
				,waitMsg: false
			});
		}
	});

})(); // closure