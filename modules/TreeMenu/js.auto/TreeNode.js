/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 27 sept. 2012
 */

/**
 * @todo uses
 */
Ext.define('eo.ui.menu.tree.Node', {
	
	extend: Ext.tree.TreeNode
	
	,defaultColor: "#000"
	,PARENT_NODE_ID: "parent__menu_nodes_id"
	
	,draggable: true
	
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
			,draggable: this.draggable
			,cls: d.cssClass
		};
		
		this.configureIconCls(d.iconCls);
		cfg.iconCls = this.iconCls;
		
		this.callParent([Ext.apply(cfg, config)]);

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
		
		this.on('click', this.onClick);
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
			var fa = fam && this.getFamilyAction(fam),
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
		this.callParent(arguments);
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

		var children = [];
		if (this.hasChildNodes()) {
			var i=0;
			this.eachChild(function(n) {
				childNodes.push(n);
				var data = Ext.apply(Ext.apply({}, n.data), {
					order: i++
					,'new': n.isNew()
					,full: false
					,root: !this.getDepth()
				});
				data[this.PARENT_NODE_ID] = pId;
				delete data.children;
				children.push(data);
			});
		}
		data.children = children;

		Ext.apply(data, {
			'new': this.isNew()
			,full: true
			,root: !this.getDepth()
		});

		var me = this;
		this.sjax.request({
			jsonData: {
				controller: this.ownerTreeMenu.controller
				,action: 'saveNode'
				,data: data
			}
			,onSuccess: function(o) {
				if (childNodes.length !== o.childrenIds.length) {
					throw new Error('Out of sync :(');
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
		this.callParent(arguments);
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
				jsonData: {
					controller: this.ownerTreeMenu.controller
					,action: 'saveNode'
					,data: Ext.apply(Ext.apply({}, this.data), {
						'new': this.isNew()
						,root: !this.getDepth()
						,full: false
					})
				}
				,onSuccess: function() {
					delete this.phantom;
				}.createDelegate(this)
			});
		}
	}
	
	,remove: function() {
		this.callParent(arguments);
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