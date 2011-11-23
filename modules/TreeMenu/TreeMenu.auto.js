(function() {

Ext.ns('eo.ui.modules');

var sp = Ext.tree.TreePanel,
	spp = sp.prototype;
	
eo.ui.TreeMenu = Ext.extend(sp, {
	
	controller: "menu"
	,filter: true
	
	,initEvents: function() {
		this.addEvents(["actionsloaded", "ready"]);
		spp.initEvents.apply(this, arguments);
	}
	
	,initComponent: function() {
		
		this.sjax = new eo.Sjax;

		// customizing the prototype's TreeNode class with this menu's config
		this.TreeNode = this.createTreeNodeClass();
		
		Ext.apply(this, {
			title: "Navigation"
			,border: false
			
			,autoScroll: true

			,tools: [{
				id: "gear"
				,handler: function(e, btn) {
					this.gearMenu.show(btn);
				}
				,scope: this
				,menu: "xx"
			}]
			,gearMenu: new Ext.menu.Menu({
				items: [{
					text: "Recharger le menu par défaut"
					,handler: this.resetFactoryDefaults.createDelegate(this)
				}]
			})

			,enableDD: true
			,useArrows: true
			,animate: true
			,containerScroll: true
		
			,rootVisible: false
			
			,root: new this.TreeNode({
				text: 'root'
				,id: 'root'
			})
			
			,contextMenu: new Ext.menu.Menu({
				items: [{
					text: "Propriétés..."
					,iconCls: "ico application_form_edit"
					,handler: this.onNodeEdit
					,scope: this
				},"-",{
					text: "Ajouter..."
					,iconCls: "ico add"
					,handler: this.onNodeAdd
					,scope: this
				},{
					text: "Supprimer"
					,iconCls: "ico delete"
					,handler: this.onNodeRemove
					,scope: this
				}]
			})
			
			,iconStore: new Ext.ux.data.PagingStore({
				url: "index.php"
				,autoLoad: false
				,baseParams: {
					controller: this.controller
					,action: "listIcons"
				}
				
				,reader: new Ext.data.JsonReader({
					fields: ["id", "class", "label"]
					,root: "data"
				})
				
				,whenLoaded: function(cb, scope) {
					if (this.firstLoadDone) {
						if (cb) cb.call(scope || this, this);
					} else {
						if (cb) this.addLoadingWaiter(cb, scope);
						
						if (!this.loading) {
							this.loading = true
							this.on({
								load: {
									fn: function(store) {
										store.firstLoadDone = true;
										store.onFirstLoad();
									}
									,single: true
								}
							});
							this.load();
						}
					}
				}
				
				// private
				,addLoadingWaiter: function(cb, scope) {
					var list = this.loadingWaiters = this.loadingWaiters || [];
					if (scope) cb = cb.createDelegate(scope);
					list.push(cb);
				}
				
				// private
				,onFirstLoad: function() {
					this.loading = false;
					this.firstLoadDone = true;
					var wl = this.loadingWaiters; // waiting list
					if (wl) {
						Ext.each(wl, function(w) {
							w.call(this, this);
						}, this);
					}
				}
			})
		});

		// search
		if (this.filter) {
			this.tbar = new Ext.Toolbar({
				layout: {
					type: "hbox"
					,align: "middle"
				}
				,items: [{
					xtype: "eo.search"
					,flex: 1
					,emptyText: "Filtrer"
					,listeners: {
						scope: this
						,search: function(f) {
							this.filterTree(f);
						}
						,clearsearch: function() {
							this.resetCollapse();
						}
						,render: function() {
							this.filter = new Ext.tree.TreeFilter(this, {
								clearBlank: true
								,autoClear: true
							});
						}
					}
				}
				,new Ext.Toolbar.Separator({
					width: 10
				}), {
					iconCls: "menu icon-expand-all"
					,tooltip: "Développer tout"
					,handler: function() {this.root.expand(true)}
					,scope: this
				},{
					iconCls: "menu icon-collapse-all"
					,tooltip: "Réduire tout"
					,handler: function() {this.root.collapse(true)}
					,scope: this
				}]
			})
		}
		
		spp.initComponent.call(this);
		
		this.addClass("eozeTreeMenu");
		
		this.on({
			scope: this
			,contextmenu: this.onNodeRightClick
			,containercontextmenu: this.onNodeRightClick
			,movenode: this.onNodeDrop
		});
		
		if (this.userId) this.load(this.executeDefaultCommands, this);
	} // initComponent
	
//	// private
//	,clearCache: function() {
//		Oce.Ajax.request({
//			params: {
//				controller: this.config.controller
//				,action: "clearCache"
//			}
//			,onFailure: function() {
//				Ext.Msg.alert("La requête a échoué.", "Échec");
//			}
//		});
//	}
	
	// private
	,executeDefaultCommands: function() {
		
		var walk = function(node) {
			if (node.data.open) {
				node.run();
			}
			node.eachChild(walk);
		};
		
		return function() {
			walk(this.getRootNode());
		};
	}()

	// private
	,createTreeNodeClass: function() {
		var me = this;
		return eo.ui.TreeMenu.prototype.TreeNode.extend({
			ownerTreeMenu: this
			
			,sjax: this.sjax

			,getFamilyStore: function(cb) {
				if (!me.actionFamiliesStore) {
					var waitingList =
							me.waitingFamiliesStore = me.waitingFamiliesStore || [];
					waitingList.push(cb);
				} else {
					return me.actionFamiliesStore;
				}
				return undefined;
			}
		});
	}

	// private
	,resetCollapse: function() {
		this.getRootNode().resetCollapse();
	}

	// private
	,filterTree: function(t) {

		var testNode = function(node, re) {
			var data = node.data;
			return re.test(data.label)
				|| data.action_family && re.test(data.action_family)
				|| hasVisibleChildren(node, re);
		};

		var hasVisibleChildren = function(node, re) {
			if (!node.hasChildNodes()) {
				return false;
			} else {
				var r = false;
				node.eachChild(function(n) {
					if (testNode(n, re)) {
						r = true;
					}
				});
				return r;
			}
		};

		return function(t) {

			var text = t.getValue(),
				f = this.filter;
			if (!text) {
				f.clear();
			}
			this.expandAll();

			var re = new RegExp('^' + Ext.escapeRe(text), 'i');
			f.filterBy(function(n) {
				return testNode(n, re);
			});
		}
	}()

	,resetFactoryDefaults: function() {
		var me = this;
		Ext.Msg.confirm(
			"Réinitialisation",
			"Cette action effacera toutes les personnalisations que vous avez"
			+ " effectuées sur ce menu et rechargera le menu par défaut. Êtes-"
			+ "vous sûr de vouloir continuer ?",
			function(btn) {
				if (btn === 'yes') {
					me.sjax.request({
						params: {
							controller: me.controller
							,action: "resetFactoryDefaults"
						}
						,onSuccess: function() {
							me.setLoadingMask(true);
							me.removeAll();
							me.load();
						}
					})
				}
			}
		);
	}
	
	// private
	,onNodeClick: function(node, e) {}
	
	,onNodeDrop: function(tree, node, oldParent, newParent) {
		if (oldParent !== newParent) {
			oldParent.saveFullNode(function() {
				newParent.saveFullNode();
			});
		} else {
			newParent.saveFullNode();
		}
	}
	
	,createEditWindow: function(config) {

		var node = config && config.node || new this.TreeNode(),
			data = node.data;

		// parentNode is set only when it is an add window
		if (config.parentNode) {
			node.setCreationParent(config.parentNode)
		}
		
		var actionStore = new Ext.data.JsonStore({
			fields: ["id", "label", "color", "iconCls", "command", "expanded", "open"]
		});
		
		if (!!data.action_family) {
			var fam = this.availableActions[data.action_family];
			if (fam) {
				actionStore.loadData(eo.hashToArray(fam.actions));
			} else {
				Ext.Msg.alert("Le module utilisé par cette action n'existe pas (ou plus).");
				return null;
			}
		}
		
		var formPanel = new Oce.FormPanel({
			defaults: {
				allowBlank: true
				,anchor: "0"
			}
			,items: [{
				xtype: "compositefield"
				,fieldLabel: "Action"
				,items: [{
					xtype: "combo"
					,store: this.actionFamiliesStore
					,mode: "local"
					,name: "action_family"
					,displayField: "label"
					,valueField: "id"
					,triggerAction: "all"
					,minChars: 1
					,value: data.action_family
					,selectOnFocus: true
					,flex: 1
					,listeners: {
						select: function(combo, familyRecord) {
							var actions = eo.hashToArray(familyRecord.get("actions"));
							actionStore.loadData(actions);
							formPanel.form.findField("action").setValue();
						}
					}
				},{
					xtype: "combo"
					,name: "action"
					,flex: 1
					,triggerAction: "all"
					,store: actionStore
					,minChars: 1
					,mode: "local"
					,displayField: "label"
					,valueField: "id"
					,value: data.action
					,selectOnFocus: true
					,listeners: {
						select: function(combo, record) {
							var form = formPanel.getForm();
							record.fields.each(function(field) {
								var f = form.findField(field.name);
								if (f) f.setValue(record.get(field.name));
							});
						}
					}
				},{
					xtype: "button"
					,iconCls: "ico cross"
					,handler: function() {
						var f = formPanel.form;
						f.findField("action_family").setValue();
						f.findField("action").setValue();
						f.findField("command").setValue();
					}
				}]
			},{
				xtype: "textfield"
				,name: "label"
				,fieldLabel: "Label"
				,allowBlank: false
				,value: data.label
			},{
				xtype: "hidden"
				,name: "command"
				,fieldLabel: "command"
				,value: data.command
			},{
				xtype: "compositefield"
				,fieldLabel: "Icône"
				,items: [{
					xtype: "iconcombo"
					,name: "iconCls"
					,triggerAction: "all"
					,store: this.iconStore
					,mode: "local"
					,displayField: "label"
					,valueField: "class"
					//,value: data.iconCls
					,pageSize: 10
					,iconClsField: "class"
					,flex: 1
					,pagingToolbarXtype: "ux.paging"
					,minListWidth: 235
					// we want the paging toolbar (hence the list) to be created
					// before the afterrender event
					,lazyInit: false
					,listeners: {
						afterrender: function(f) {
							this.iconStore.whenLoaded(function() {
								var tb = f.pageTb;
								if (data.iconCls) {
									var s = f.store,
										i = s.find("class", data.iconCls),
										p = i / tb.pageSize + (i % tb.pageSize > 0 ? 1 : 0);
									f.setValue(data.iconCls);
									tb.changePage(p + 1); // counting from 1...
								} else {
									tb.doRefresh();
								}
							});
						}
						,scope: this
					}
 
				},{
					xtype: "button"
					,iconCls: "ico cross"
					,handler: function() {
						var f = formPanel.form;
						f.findField("iconCls").setValue();
					}
				}]
			},{
				xtype: "colorpicker"
				,name: "color"
				,fieldLabel: "Couleur"
				,value: data.color || this.TreeNode.prototype.defaultColor
				,defaultColor: data.color || this.TreeNode.prototype.defaultColor
			},{
				xtype: "container"
				,layout: "form"
				,labelWidth: 135
				,items: [{
					xtype: "checkbox"
					,name: "expanded"
					,fieldLabel: "Développé par défaut"
					,checked: !!data.expanded
				},{
					xtype: "checkbox"
					,name: "open"
					,fieldLabel: "Exécuter au démarrage"
					,checked: !!data.open
				}]
			}]
		});
		
		var win = new Oce.FormWindow(Ext.apply({
			formPanel: formPanel
			,width: 400
			
			,maximizable: false
			,minimizable: false
			
			,submitButton: 0
			
			,buttons: [{
				text: "Ok"
				,handler: function() {
					var form = formPanel.form;
					if (form.isValid()) {
						node.update(Ext.apply(form.getFieldValues(), {
							action_family: form.findField("action_family").getValue()
							,action: form.findField("action").getValue()
							,iconCls: form.findField("iconCls").getValue() || null
						}));
						node.save();
						win.close();
					}
				}
			}, {
				text: "Annuler"
				,handler: function() {win.close()}
			}]
		}, config));
		
		return win;
	}
	
	// private
	,onNodeEdit: function() {
		var win = this.createEditWindow({
			node: this.contextMenu.node
			,modal: true
			,title: "Édition du menu"
			,iconCls: "ico application_form_edit"
		});
		// if an error has occured, no win is returned
		if (win) win.show();
	}
	
	// private
	,onNodeAdd: function() {
		var win = this.createEditWindow({
			modal: true
			,title: "Nouvel élément de menu"
			,iconCls: "ico add"
			,parentNode: this.contextMenu.node
		});
		// if an error has occured, no win is returned
		if (win) win.show();
	}
	
	// private
	,onNodeRemove: function() {
		var n = this.contextMenu.node;
		if (n) {
			n.remove();
			this.contextMenu.node = null;
		}
	}
	
	// private
	,onNodeRightClick: function(node, e) {
		if (node instanceof Ext.tree.TreePanel) {
			node = node.getRootNode();
		}
		node.select();
		var menu = node.getOwnerTree().contextMenu;
		menu.node = node;
		menu.showAt(e.getXY());
	}
	
	// private
	,onRender: function() {
		spp.onRender.apply(this, arguments);
		if (this.hasLoadingMask) this.setLoadingMask(true);
	}
	
	,loadingLatch: 0
	
	,addLoading: function() {
		if (++this.loadingLatch) {
			this.setLoadingMask(true);
		}
	}
	
	,removeLoading: function() {
		if (!--this.loadingLatch) {
			this.setLoadingMask(false);
			this.setReady();
		}
	}
	
	,setLoadingMask: function(set) {
		var el = this.body;
		if (set) {
			if (el) {
				if (!this.loadingEl) {
					this.loadingEl = Ext.get(Ext.DomHelper.createDom({
						tag: "div"
						,cls: "loading-indicator"
						,html: "Chargement du menu..."
					}));
					el.appendChild(this.loadingEl);
				}
			} else {
				this.hasLoadingMask = true;
			}
		} else {
			if (this.loadingEl) {
				this.loadingEl.remove();
				delete this.loadingEl;
			}
			this.hasLoadingMask = false;
		}
	}
	
	,whenReady: function(fn, scope) {
		if (this.ready) {
			fn.call(scope || this);
		} else {
			this.on("ready", fn, scope, {single: true});
		}
	}
	
	,setReady: function() {
		if (!this.ready) {
			this.ready = true;
			this.fireEvent("ready");
		}
	}
	
	,load: function(callback, scope) {

		this.addLoading();
		
		this.sjax.request({
			params: {
				controller: this.controller
				,action: "loadUserMenu"
			}
			,onSuccess: function(data) {
				this.removeLoading();
				this.whenReady(function() {
					this.loadNodeData(data);
					if (callback) callback.call(scope || this);
				});
			}.createDelegate(this)
			,onFailure: function() {
				this.removeLoading();
				var a = Ext.get(Ext.DomHelper.createDom({
					tag: "a"
					,html: "Réessayer"
				}));
				var div = Ext.get(Ext.DomHelper.createDom({
					tag: "div"
					,html: "<p>Une erreur a empêché le chargement de ce menu.</p>"
					,cls: "menu-message error"
				}));
				div.appendChild(a);
				var retry = this.load.createDelegate(this);
				a.addListener("click", function() {
					div.remove();
					retry();
				});
				this.body.appendChild(div);
				
				return false;
			}.createDelegate(this)
		});
		
		if (!this.availableActions) {
			this.loadActions();
		}
	}
	
	// TODO this is probably slightly buggy...
	,reloadActions: function(delay) {
		if (delay) {
			var me = this;
			this.getRootNode().removeAll();
			this.addLoading();
			setTimeout(function() {
				me.reloadActions(false);
				me.removeLoading();
			}, delay);
		} else {
			delete this.availableActions;
			this.getRootNode().removeAll();
			this.load();
		}
	}
	
	,loadActions: function(cb) {
		
		if (this.availableActions === false) return;
		
		this.availableActions = false;
		
		var me = this;
		
		this.addLoading();
		this.sjax.request({
			params: {
				controller: this.controller
				,action: "getAvailableActions"
			}
			,onSuccess: function(data) {
				me.availableActions = data.families;
				me.createActionStores();
				me.removeLoading();
				me.fireEvent("actionsloaded", me, me.availableActions);
			}
			,onFaillure: function() {
				me.removeLoading();
				delete me.availableActions;
			}
		});
	}
	
	// private
	,createActionStores: function() {
		
		if (this.actionFamiliesStore) {
			this.actionFamiliesStore.loadData(eo.hashToArray(this.availableActions));
		} else {
			this.actionFamiliesStore = new Ext.data.JsonStore({
				fields: ["id","label","actions","iconCls"]
				,data: eo.hashToArray(this.availableActions)
			});
		}

		var waitingList = this.waitingFamiliesStore;
		if (waitingList) {
			delete this.waitingFamiliesStore;
			Ext.each(waitingList, function(cb) {
				cb();
			});
		}
	}
	
	,loadNodeData: function(data) {
		
		var root = this.getRootNode(),
			listeners = {click: this.onNodeClick, scope : this};
			
		root.removeAll();
			
		Ext.each(data, function(nodeData) {
			root.appendChild(
				new this.TreeNode({
					data: nodeData
					,listeners: listeners
				})
			);
		}, this);
		
		this.resetCollapse();
	}
});

eo.ui.TreeMenu.prototype.TreeNode = Ext.extend(Ext.tree.TreeNode, {
	
	spp: Ext.tree.TreeNode.prototype
	
	,defaultColor: "#000"
	,PARENT_NODE_ID: "parent__menu_nodes_id"
	
	,constructor: function(config) {
		
		var d = this.data = config && config.data || {};
		
		d.expanded = eo.bool(d.expanded);
		d.open = eo.bool(d.open);
		
		var cfg = {
			text: d.label
			,id: d.id
			// We want all nodes expanded at creation time, so that they are
			// all rendered
			//,expanded: d.expanded
			,expanded: true
			,open: d.open
			,draggable: true
			,cls: d.cssClass
		};
		
		this.configureIconCls(d.iconCls);
		cfg.iconCls = this.iconCls;
		
		this.spp.constructor.call(this, Ext.apply(cfg, config));

		if (d.command) {
			this.setCommand(d.command, true);
		}

		Ext.each(d.children, function(nodeData) {
			if (!nodeData[this.PARENT_NODE_ID]) {
				nodeData[this.PARENT_NODE_ID] = this.id;
			}
			this.appendChild(
				new this.constructor({
					data: nodeData
					,listeners: this.listeners
				})
			);
		}, this);
		
		this.on("click", this.onClick);
	}
	
	,resetCollapse: function() {
		if (this.data.expanded) {
			this.expand();
		} else {
			this.collapse();
		}
		this.eachChild(function(child) {
			child.resetCollapse();
		});
	}
	
	,onClick: function() {
		if (this.cmd || this.action) {
			this.run();
		} else {
			this.toggle();
		}
	}
	
	,run: function() {
		if (this.action) {
			this.runAction();
		} else if (this.cmd) {
			this.runCmd();
		}
	}
	
	// private
	,runAction: function() {
		Oce.Module.executeAction(this.action, {
			scope: this
			,before: this.setLoading.createDelegate(this, [true])
			,after: function(success) {
				this.setLoading(false);
				if (!success) {
					Ext.Msg.alert(
						"Échec de l'action", 
						"Le module visé pas l'action n'est pas accessible."
					);
				}
			}
		})
//		this.setLoading(true);
//		var me = this,
//			action = this.action,
//			module = this.action.module;
//		if (module.substr(0,12) !== "Oce.Modules.") {
//			module = String.format("Oce.Modules.{0}.{0}", this.action.module);
//		}
//		Oce.mx.application.getModuleInstance(
//			module,
//			function(module) {
//				module.executeAction({
//					action: action.method
//					,args: action.args
//					,callback: function() { this.setLoading(false); }
//					,scope: me
//				});
//			},
//			function() {
//				me.setLoading(false);
//				Ext.Msg.alert("Échec de l'action", 
//						"Le module visé pas l'action n'est pas accessible.");
//			}
//		);
	}
	
	// private
	,runCmd: function() {
		this.setLoading();
		this.cmd();
		this.setLoading(false);
	}
	
	,setLoading: function(loading) {
		if (loading !== false) {
			this.getUI().addClass("loading");
		} else {
			this.getUI().removeClass("loading");
		}
	}

	,getActionFamily: function(cb) {
		var fs = this.getFamilyStore(arguments.callee.createDelegate(this)),
			af = this.data.action_family,
			wl;
		if (!fs) {
			wl = this.getActionFamily_waitingList
					 = this.getActionFamily_waitingList || [];
			wl.push(cb);
			return undefined;
		}
		if (af) {
			wl = this.getActionFamily_waitingList;
			delete this.getActionFamily_waitingList;
			if (wl) Ext.each(wl, function(cb) {cb()});

			return fs.getById(this.data.action_family);
		}
		return undefined;
	}
	
	,getFamilyAction: function(familyRecord) {
		var r;
		Ext.each(familyRecord.get("actions"), function(a) {
			if (a.id === this.data.action) {
				r = a;
				return false;
			}
		}, this);
		return r;
	}
	
	,configureIconCls: function(myic) {
		var fam = this.getActionFamily(arguments.callee.createDelegate(this)),
			fic = myic || fam && fam.get("iconCls");
		if (fic) {
//			fam.get("actions")[0].baseIconCls
			var fa = this.getFamilyAction(fam),
				bic = fa && fa.baseIconCls;
			if (bic) {
				fic = fa.baseIconCls;
			}
			var ic = ic = this.iconCls || "" + " "
				+ fic.replace('%action%', this.data.action || "");
			if (this.setIconCls && this.attributes) {
				this.setIconCls(ic);
			} else {
				this.iconCls = ic;
			}
		}
	}
	
	,parseActionRegex: /^@(.+)#(.+?)(?:\((.+)\))?$/
	
	,setCommand: function(command, force) {
		if (command !== this.data.command || force) {
			this.data.command = command;
			
			delete this.cmd;
			delete this.action;
			
			if (command) {
				if (command.substr(0,1) === '@') {
					var parts = this.parseActionRegex.exec(command);
					parts.shift();
					var module = parts.shift(),
						method = parts.shift(),
						args = parts.shift();
					if (!method) {
						this.cmd = Ext.emptyFn;
						return;
					}
					if (args) {
						args = args.split(',');
						for (var i=0,l=args.length; i<l; i++) {
							args[i] = args[i].trim();
						}
					}
					this.action = {
						module: module
						,method: method
						,args: args
					};
				} else {
					this.cmd = Oce.cmd(command);
				}
				this.setCls("x-tree-node-leaf");
			} else {
				if (this.el) this.el.removeClass("x-tree-node-leaf");
			}
		}
	}
	
	,setCreationParent: function(node) {

		Ext.apply(this.data, {
			action_family: node.data.action_family
			,parent__menu_nodes_id: node.id
		});

		this.futureParentNode = node;
		this.phantom = true;
	}
	
	,render: function() {
		this.spp.render.apply(this, arguments);
		var color = this.data.color;
		delete this.data.color;
		this.setColor(color);
	}
	
	,setColor: function(color) {
		if (color !== this.data.color) {
			this.data.color = color;
			if (this.rendered) {
				Ext.get(this.getUI().getTextEl()).setStyle("color", color);
			}
		}
	}
	
	,setLabel: function(text) {
		if (text !== this.data.label) {
			this.data.label = text;
			this.setText(text);
		}
	}
	
	,setItemIconCls: function(iconCls) {
		if (iconCls !== this.data.iconCls || this.isNew()) {
			// Remove class in the node element
			var el = Ext.get(this.getUI().getIconEl());
			Ext.each([this.data.iconCls, this.iconCls], function(previous) {
				if (previous && el) el.removeClass(previous.split(" "));
			}, this);
			// Update with the new iconCls. If a blank value is submitted, then
			// we try to restore the default action icon.
			this.data.iconCls = iconCls || null;
			if (iconCls === undefined || iconCls === null) {
				delete this.iconCls;
				this.configureIconCls();
			} else {
				this.setIconCls(iconCls);
			}
		}
	}
	
	,update: function(data) {
		Ext.apply(this.data, {
			action_family: data.action_family
			,action: data.action
			,expanded: !!data.expanded
			,open: !!data.open
		});
		this.setLabel(data.label);
		this.setColor(data.color);
		this.setCommand(data.command);
		// action family is used to set iconCls, so the next call must be
		// done after having set data.action_family
		this.setItemIconCls(data.iconCls);
	}

	,isNew: function() {
		return !!this.phantom;
	}

	/**
	 * Saves this node and all its descendants, recomputing order field.
	 */
	,saveFullNode: function(cb) {
		var data = Ext.apply({}, this.data),
			childNodes = [],
			pId = !this.getDepth() ? null : this.id;

		if (this.isNew()) {
			throw new Error();
		}

		if (this.hasChildNodes()) {
			var i=0, children = [];
			this.eachChild(function(n) {
				childNodes.push(n);
				var data = Ext.apply(Ext.apply({}, n.data), {
					order: i++
					,"new": n.isNew()
					,full: false
					,root: !this.getDepth()
				});
				data[this.PARENT_NODE_ID] = pId;
				delete data.children;
				children.push(data);
			});
			data.children = children;
		}

		Ext.apply(data, {
			"new": this.isNew()
			,full: true
			,root: !this.getDepth()
		});

		var me = this;
		this.sjax.request({
			params: {
				controller: this.ownerTreeMenu.controller
				,action: "saveNode"
				,json_data: Ext.encode(data)
			}
			,onSuccess: function(o) {
				if (childNodes.length !== o.childrenIds.length) {
					throw new Error("Desynchro :(");
				}
				me.setId(o.id);
				delete me.phantom;
				for (var i=0,l=childNodes.length; i<l; i++) {
					var cn = childNodes[i]
					cn.setId(o.childrenIds[i]);
					delete cn.phantom;
				}
				if (cb) cb();
			}
		});
	}
	
	,setId: function(id) {
		this.spp.setId.call(this, id);
		this.data.id = id;
	}
	
	,save: function() {

		var newParent = this.futureParentNode;
		if (newParent) {
			newParent.appendChild(this);
			newParent.expand();
			newParent.saveFullNode();
			delete this.futureParentNode;
		} else {
			this.sjax.request({
				params: {
					controller: this.ownerTreeMenu.controller
					,action: "saveNode"
					,json_data: Ext.encode(
						Ext.apply(Ext.apply({}, this.data), {
							"new": this.isNew()
							,root: !this.getDepth()
							,full: false
						})
					)
				}
				,onSuccess: function() {
					delete this.phantom;
				}.createDelegate(this)
			});
		}
	}
	
	,remove: function() {
		this.spp.remove.apply(this, arguments);
		// to server
		if (!this.isNew()) {
			this.sjax.request({
				params: {
					controller: this.ownerTreeMenu.controller
					,action: "deleteNode"
					,nodeId: this.data.id
				}
			})
		}
	}
});

})(); // closure
