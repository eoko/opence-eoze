(function() {

Ext.ns('eo.ui.modules');

var sp = Ext.tree.TreePanel,
	spp = sp.prototype;

eo.ui.TreeMenu = sp.extend({
	
	controller: "menu"
	,filter: true
	
	,initEvents: function() {
		this.addEvents(["actionsloaded", "ready"]);
		spp.initEvents.apply(this, arguments);
	}
	
	,initComponent: function() {
		
		var me = this;

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
			
//			,root: new Ext.tree.TreeNode({
//				text: 'root'
//				,id: 'root'
//			})
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
				}]
			})
		});

		// search
		if (this.filter) {
			this.tbar = new Ext.Toolbar({
				items: [{
//					xtype: "textfield"
					xtype: "trigger"
					,enableKeyEvents: true
					,emptyText: "Filtrer"
					,width: 110
					,triggerClass : 'x-form-clear-trigger'
					,onTriggerClick: function() {
						this.setValue();
						me.filterTree(this);
						me.resetCollapse();
					}
					,listeners: {
						keydown: {
							fn: this.filterTree
							,buffer: 350
							,scope: this
						}
						,render: function() {
							this.filter = new Ext.tree.TreeFilter(this, {
								clearBlank: true
								,autoClear: true
							})
						}
						,scope: this
					}
				},"->",{
					iconCls: "menu icon-expand-all"
					,tooltip: "Développer tout"
					,handler: function() { this.root.expand(true) }
					,scope: this
				},"-",{
					iconCls: "menu icon-collapse-all"
					,tooltip: "Réduire tout"
					,handler: function() { this.root.collapse(true) }
					,scope: this
				}]
			})
		}
		
		spp.initComponent.call(this);
		
		this.on({
			scope: this
			,contextmenu: this.onNodeRightClick
			,movenode: this.onNodeDrop
		});
		
		if (this.userId) this.load();
	}

	// private
	,createTreeNodeClass: function() {
		var me = this;
		return eo.ui.TreeMenu.prototype.TreeNode.extend({
			ownerTreeMenu: this

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

		var expandIf = function(n) {
			if (n.data.expanded && !!parseInt(n.data.expanded)) {
				n.expand();
				if (n.hasChildNodes()) {
					n.eachChild(expandIf);
				}
			}
		};

		return function() {
			this.collapseAll();
			this.root.eachChild(expandIf);
		}
	}()
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
					Oce.Ajax.request({
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
		// TODO account for root node not being savable!!!
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
			fields: ["id", "label", "color", "command", "expanded"]
		});
		
		var iconStore = new Ext.data.JsonStore({
			fields: ["id", "class", "label"]
			,url: "index.php"
			,root: "data"
			,baseParams: {
				controller: this.controller
				,action: "listIcons"
			}
		});
		
		if (!!data.action_family) {
			var fam = this.availableActions[data.action_family];
			if (fam) {
				actionStore.loadData(eo.hashToArray(fam.actions));
			} else {
				Ext.Msg.alert("Le module utilisé par cette action n'existe pas (ou plus).");
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
					,value: data.action_family
					,flex: 1
					,listeners: {
						select: function(combo, familyRecord) {
							var actions = eo.hashToArray(familyRecord.get("actions"));
							actionStore.loadData(actions);
						}
					}
				},{
					xtype: "combo"
					,name: "action"
					,flex: 1
					,triggerAction: "all"
					,store: actionStore
					,mode: "local"
					,displayField: "label"
					,valueField: "id"
					,value: data.action
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
//			},{
//				xtype: "oce.foreigncombo"
//				,fieldLabel: "Icône"
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
					,store: iconStore
					,mode: "remote"
					,displayField: "label"
					,valueField: "class"
					,value: data.iconCls
					,pageSize: 10
					,iconClsField: "class"
					,flex: 1
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
				xtype: "checkbox"
				,name: "expanded"
				,fieldLabel: "Développé par défaut"
				,checked: !!data.expanded
			}]
		});
		
		var win = new Oce.FormWindow(Ext.apply({
			formPanel: formPanel
			,width: 400
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
		win.show();
	}
	
	// private
	,onNodeAdd: function() {
		var win = this.createEditWindow({
			modal: true
			,title: "Nouvel élément de menu"
			,iconCls: "ico add"
			,parentNode: this.contextMenu.node
		});
		win.show();
	}
	
	// private
	,onNodeRightClick: function(node, e) {
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
			this.on("ready", fn, scope, { single: true });
		}
	}
	
	,setReady: function() {
		if (!this.ready) {
			this.ready = true;
			this.fireEvent("ready");
		}
	}
	
	,load: function() {

		this.addLoading();
		
		Oce.Ajax.request({
			params: {
				controller: this.controller
				,action: "loadUserMenu"
			}
			,onSuccess: function(data) {
				this.removeLoading();
				this.whenReady(function() {
					this.loadNodeData(data);
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
	
	,loadActions: function(cb) {
		
		if (this.availableActions === false) return;
		
		this.availableActions = false;
		
		var me = this;
		
		this.addLoading();
		Oce.Ajax.request({
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
		this.actionFamiliesStore = new Ext.data.JsonStore({
			fields: ["id","label","actions","iconCls"]
			,data: eo.hashToArray(this.availableActions)
		});

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
	}
});

eo.ui.TreeMenu.prototype.TreeNode = Ext.tree.TreeNode.extend({
	
	spp: Ext.tree.TreeNode.prototype
	
	,defaultColor: "#000"
	,PARENT_NODE_ID: "parent__menu_nodes_id"
	
	,constructor: function(config) {
		
		var d = this.data = config && config.data || {};
		
		d.expanded = !!parseInt(d.expanded);
		
		var cfg = {
			text: d.label
			,id: d.id
			,expanded: d.expanded
			,draggable: true
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
	
	,onClick: function() {
		if (this.cmd) {
			this.cmd();
		} else {
			this.toggle();
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
			if (wl) Ext.each(wl, function(cb) { cb() });

			return fs.getById(this.data.action_family);
		}
		return undefined;
	}

	,configureIconCls: function(myic) {
		var fam = this.getActionFamily(arguments.callee.createDelegate(this)),
			fic = myic || fam && fam.get("iconCls");
		if (fic) {
			var ic = ic = this.iconCls || "" + " "
				+ fic.replace('%action%', this.data.action || "");
			if (this.setIconCls && this.attributes) {
				this.setIconCls(ic);
			} else {
				this.iconCls = ic;
			}
		}
	}
	
	,setCommand: function(command, force) {
		if (command !== this.data.command || force) {
			this.data.command = command;
			if (command) {
				this.cmd = Oce.cmd(command);
				this.setCls("x-tree-node-leaf");
			} else {
				delete this.cmd;
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
		if (iconCls !== this.data.iconCls) {
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
		this.setLabel(data.label);
		this.setColor(data.color);
		this.setCommand(data.command);
		this.setItemIconCls(data.iconCls);
		Ext.apply(this.data, {
			action_family: data.action_family
			,action: data.action
			,expanded: !!data.expanded
		});
	}

	,isNew: function() {
		return !!this.futureParentNode;
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
		Oce.Ajax.request({
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
				for (var i=0,l=childNodes.length; i<l; i++) {
					childNodes[i].setId(o.childrenIds[i]);
				}
				if (cb) cb();
			}
		});
	}
	
	,save: function() {

		var newParent = this.futureParentNode;
		if (newParent) {
			newParent.appendChild(this);
			newParent.expand();
			newParent.saveFullNode();
			delete this.futureParentNode;
		} else {
			Oce.Ajax.request({
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
			});
		}
	}
});

})(); // closure
