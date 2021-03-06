/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 27 sept. 2012
 */

/**
 * TreeMenu panel.
 *
 * @xtype applicationmenu
 */
Ext.define('eo.ui.menu.tree.Menu', {

	extend: 'Ext.tree.TreePanel'
	,alias: ['widget.applicationmenu']
	,requires: [
		'eo.ui.menu.tree.Node'
	]
	,uses: [
//		'eo',
//		'Ext.Msg',
		// Ext
		'Ext.menu.Menu',
		'Ext.data.JsonReader',
		'Ext.ux.data.PagingStore',
		'Ext.Toolbar',
		'Ext.tree.TreeFilter',
		'Ext.Toolbar.Separator',
		'Ext.data.JsonStore',
		'Ext.DomHelper',
		// Oce
		'Oce.FormPanel',
		'Oce.FormWindow'
	]

	/**
	 * @cfg {Boolean}
	 * If `true`, the menu nodes can be added, moved, removed and edited (name,
	 * icon, action). If the menu is editable, it will also provides a gear
	 * menu to reset the menu to the default.
	 */
	,editable: false

	/**
	 * @cfg {String} controller
	 * Name of the server side controller.
	 */

	/**
	 * @cfg {Boolean}
	 * Adds a toolbar to filter menu items.
	 */
	,filterable: true

	,initEvents: function() {
		this.addEvents(['actionsloaded', 'ready']);
		this.callParent(arguments);
	}

	,initComponent: function() {

		this.sjax = new eo.Sjax;

		// customizing the prototype's TreeNode class with this menu's config
		this.TreeNode = this.createTreeNodeClass();

		Ext.apply(this, {
			title: "Navigation" // i18n
			,border: false

			,autoScroll: true

			,enableDD: true
			,useArrows: true
			,animate: true
			,containerScroll: true

			,rootVisible: false

			,root: new this.TreeNode({
				text: 'root'
				,id: 'root'
			})

			,iconStore: new Ext.ux.data.PagingStore({
				url: 'api'
				,autoLoad: false
				,baseParams: {
					controller: this.controller
					,action: 'listIcons'
				}

				,reader: new Ext.data.JsonReader({
					fields: ['id', 'class', 'label']
					,root: 'data'
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

		// gear menu
		if (this.editable) {
			Ext.apply(this, {
				tools: [{
					id: 'gear'
					,handler: function(e, btn) {
						this.gearMenu.show(btn);
					}
					,scope: this
					,menu: 'xx'
				}]
				,gearMenu: new Ext.menu.Menu({
					items: [{
						text: "Recharger le menu par défaut" // i18n
						,handler: this.resetFactoryDefaults.createDelegate(this)
					}]
				})
			});
		}

		// context menu
		if (this.editable) {
			this.contextMenu = new Ext.menu.Menu({
				items: [{
					text: "Propriétés..." // i18n
					,iconCls: 'ico application_form_edit'
					,handler: this.onNodeEdit
					,scope: this
				},'-',{
					text: "Ajouter..." // i18n
					,iconCls: 'ico add'
					,handler: this.onNodeAdd
					,scope: this
				},{
					text: "Supprimer" // i18n
					,iconCls: 'ico delete'
					,handler: this.onNodeRemove
					,scope: this
				}]
			});
		}

		// search
		if (this.filterable) {
			this.tbar = new Ext.Toolbar({
				layout: {
					type: "hbox" // i18n
					,align: 'middle'
				}
				,items: [{
					xtype: 'eo.search'
					,flex: 1
					,emptyText: "Filtrer" // i18n
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
					iconCls: 'menu icon-expand-all'
					,tooltip: "Développer tout" // i18n
					,handler: function() {this.root.expand(true)}
					,scope: this
				},{
					iconCls: 'menu icon-collapse-all'
					,tooltip: "Réduire tout" // i18n
					,handler: function() {this.root.collapse(true)}
					,scope: this
				}]
			})
		}

		this.callParent(arguments);

		this.addClass('eozeTreeMenu');

		if (this.editable) {
			this.on({
				scope: this
				,contextmenu: this.onNodeRightClick
				,containercontextmenu: this.onNodeRightClick
				,movenode: this.onNodeDrop
			});
		}

		if (!Ext.isEmpty(this.userId)) {
			this.load(this.executeDefaultCommands, this);
		}
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

		var promises = [];

		function walk(node) {
			if (node.data.open) {
				promises.push(node.run());
			}
			node.eachChild(walk);
		}

		walk(this.getRootNode());

		Deft.Promise.all(promises).always(function() {
			// all default commands have been run
			Eoze.AjaxRouter.Router.initialRoute();
		});
	}

	// private
	,createTreeNodeClass: function() {
		var me = this;
		return Ext.define(null, {
			extend: 'eo.ui.menu.tree.Node'

			,ownerTreeMenu: this

			,sjax: this.sjax

			// Node in fixed menu are not draggable
			,draggable: this.editable

			,getFamilyStore: function(cb) {
				if (!me.actionFamiliesStore) {
					var waitingList = me.waitingFamiliesStore = me.waitingFamiliesStore || [];
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
			"Réinitialisation", // i18n
			// i18n
			"Cette action effacera toutes les personnalisations que vous avez"
			+ " effectuées sur ce menu et rechargera le menu par défaut. Êtes-"
			+ "vous sûr de vouloir continuer ?",
			function(btn) {
				if (btn === 'yes') {
					me.sjax.request({
						params: {
							controller: me.controller
							,action: 'resetFactoryDefaults'
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
			fields: ['id', 'label', 'color', 'iconCls', 'command', 'expanded', 'open']
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
				,anchor: '0'
			}
			,items: [{
				xtype: 'compositefield'
				,fieldLabel: "Action" // i18n
				,items: [{
					xtype: 'combo'
					,store: this.actionFamiliesStore
					,mode: 'local'
					,name: 'action_family'
					,displayField: 'label'
					,valueField: 'id'
					,triggerAction: 'all'
					,minChars: 1
					,value: data.action_family
					,selectOnFocus: true
					,flex: 1
					,listeners: {
						select: function(combo, familyRecord) {
							var actions = eo.hashToArray(familyRecord.get('actions'));
							actionStore.loadData(actions);
							formPanel.form.findField('action').setValue();
						}
					}
				},{
					xtype: 'combo'
					,name: 'action'
					,flex: 1
					,triggerAction: 'all'
					,store: actionStore
					,minChars: 1
					,mode: 'local'
					,displayField: 'label'
					,valueField: 'id'
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
					xtype: 'button'
					,iconCls: 'ico cross'
					,handler: function() {
						var f = formPanel.form;
						f.findField('action_family').setValue();
						f.findField('action').setValue();
						f.findField('command').setValue();
					}
				}]
			},{
				xtype: 'textfield'
				,name: 'label'
				,fieldLabel: "Label" // i18n
				,allowBlank: false
				,value: data.label
			},{
				xtype: 'hidden'
				,name: 'command'
				,fieldLabel: 'command'
				,value: data.command
			},{
				xtype: 'compositefield'
				,fieldLabel: "Icône" // i18n
				,items: [{
					xtype: 'iconcombo'
					,name: 'iconCls'
					,triggerAction: 'all'
					,store: this.iconStore
					,mode: 'local'
					,displayField: 'label'
					,valueField: 'class'
					//,value: data.iconCls
					,pageSize: 10
					,iconClsField: 'class'
					,flex: 1
					,pagingToolbarXtype: 'ux.paging'
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
										i = s.find('class', data.iconCls),
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
					xtype: 'button'
					,iconCls: 'ico cross'
					,handler: function() {
						var f = formPanel.form;
						f.findField('iconCls').setValue();
					}
				}]
			},{
				xtype: 'colorpicker'
				,name: 'color'
				,fieldLabel: "Couleur" // i18n
				,value: data.color || this.TreeNode.prototype.defaultColor
				,defaultColor: data.color || this.TreeNode.prototype.defaultColor
			},{
				xtype: 'container'
				,layout: 'form'
				,labelWidth: 135
				,items: [{
					xtype: 'checkbox'
					,name: 'expanded'
					,fieldLabel: "Développé par défaut" // i18n
					,checked: !!data.expanded
				},{
					xtype: 'checkbox'
					,name: 'open'
					,fieldLabel: "Exécuter au démarrage" // i18n
					,checked: !!data.open
				}]
			}]
		});

		var win = new Oce.FormWindow(Ext.apply({
			formPanel: formPanel
			,width: 640

			,maximizable: false
			,minimizable: false

			,submitButton: 0

			,buttons: [{
				text: "Ok" // i18n
				,handler: function() {
					var form = formPanel.form;
					if (form.isValid()) {
						node.update(Ext.apply(form.getFieldValues(), {
							action_family: form.findField('action_family').getValue()
							,action: form.findField('action').getValue()
							,iconCls: form.findField('iconCls').getValue() || null
						}));
						node.save();
						win.close();
					}
				}
			}, {
				text: "Annuler" // i18n
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
			,title: "Édition du menu" // i18n
			,iconCls: 'ico application_form_edit'
		});
		// if an error has occured, no win is returned
		if (win) win.show();
	}

	// private
	,onNodeAdd: function() {
		var win = this.createEditWindow({
			modal: true
			,title: "Nouvel élément de menu" // i18n
			,iconCls: 'ico add'
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
		this.callParent(arguments);
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
						tag: 'div'
						,cls: 'loading-indicator'
						,html: "Chargement du menu..." // i18n
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
			this.on('ready', fn, scope, {single: true});
		}
	}

	,setReady: function() {
		if (!this.ready) {
			this.ready = true;
			this.fireEvent('ready');
		}
	}

	,load: function(callback, scope) {

		this.addLoading();

		this.sjax.request({
			params: {
				controller: this.controller
				,action: 'loadUserMenu'
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
					tag: 'a'
					,html: "Réessayer" // i18n
				}));
				var div = Ext.get(Ext.DomHelper.createDom({
					tag: 'div'
					,html: "<p>Une erreur a empêché le chargement de ce menu.</p>" // i18n
					,cls: 'menu-message error'
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

		eo.getApplication().onStarted(function() {
			me.sjax.request({
				params: {
					controller: me.controller
					,action: 'getAvailableActions'
				}
				,onSuccess: function(data) {
					me.availableActions = data.families;
					me.createActionStores();
					me.removeLoading();
					me.fireEvent('actionsloaded', me, me.availableActions);
				}
				,onFaillure: function() {
					me.removeLoading();
					delete me.availableActions;
				}
			});
		});
	}

	// private
	,createActionStores: function() {

		if (this.actionFamiliesStore) {
			this.actionFamiliesStore.loadData(eo.hashToArray(this.availableActions));
		} else {
			this.actionFamiliesStore = new Ext.data.JsonStore({
				fields: ['id','label','actions','iconCls']
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
