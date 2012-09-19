/**
 * @author Éric Ortéga <eric@planysphere.fr>
 */
Oce.deps.wait('eo.modules.TabModule', function() {
	
//var ModuleContainer = Ext.extend(Ext.Container, {
var ModuleContainer = Ext.extend(Ext.Panel, {
	
	initComponent: function() {
		
		Ext.apply(this, {
			layout: "fit"
		});
		
		ModuleContainer.superclass.initComponent.call(this);
		
		this.on({
			scope: this
			,add: function(ct, cp) {
				this.component = cp;
				if (cp.ownerCt === this) { // the add event bubbles...
					this.doLayout();
				}
			}
		});
		
		this.on({
			scope: this
			,single: true
			,show: function() {
				this.cmd(function(m) {
					m.open(this, {
						header: false
						,border: false
					});
				}.createDelegate(this));
			}
		});
	}
	
	// private
	,onClose: function() {
		var c = this.component;
		if (c) c.fireEvent("close", c);
	}
});

/**
 * Base class for ModuleGroupModule modules.
 */
eo.modules.ModuleGroupModule = Ext.extend(eo.modules.TabModule, {
	
	/**
	 * @cfg {Oce.Module[]} modules
	 */
	
	constructor: function() {
		
		this.callParent(arguments);
		
		var latch = this.modules.length,
			me = this;
		
		var releaseLatch = function() {
			if (--latch === 0) {
				me.ready = true;
				if (me.whenReady) {
					me.whenReady();
					delete me.whenReady();
				}
			}
		};
		
		Ext.each(this.modules, function(m) {
			var cmd = m.cmd;
			if (Ext.isString(cmd)) {
				cmd = m.cmd = Oce.cmd(cmd);
			}
			// preload module code
			cmd(function(module) {
				
				// replace module open method
				// TODO this works but only once the group module has been opened once >_<
				var open = module.open;
				module.open = function(destination) {
					if (destination instanceof ModuleContainer) {
						open.apply(module, arguments);
					} else {
						me.open(destination, module.name);
					}
				};

				// iconCls
				if (module.getIconCls) {
					m.iconCls = module.getIconCls();
				}
				
				// latch
				releaseLatch();
			});
		});
	}
	
	,open: function(destination, child) {
		var args = arguments;
		if (this.ready) {
			if (child) {
				var childIndex = Ext.each(this.modules, function(m) { 
					if (m.name === child) return false;
				});
				if (childIndex !== undefined) {
					this.activeTab = childIndex;
					if (this.tab) {
						this.on({
							single: true
							,scope: this
							,open: function() {
								this.tab.setActiveTab(childIndex);
							}
						});
					}
				}
			}
			this.callParent(arguments);
		} else {
			this.whenReady = this.open.createDelegate(this, args);
		}
	}
	
	,createTab: function() {
		var tab = this.callParent(arguments);

		tab.on({
			scope: this
			,tabchange: this.onTabChange
		});
        
        tab.getActiveModule = function() {
            var at = tab && tab.activeTab,
                c = at && at.component;
            return c && c.module;
        };
		
		return tab;
	}
	
	// private
	,onTabChange: function(tabPanel, tab) {
		this.activeTab = tabPanel.items.indexOf(tab);
	}
	
	,createTabConfig: function() {
		var config = Ext.apply(this.callParent(arguments), this.config.tab),
			items = [];
			
		Ext.each(this.modules, function(m) {
			items.push(new ModuleContainer(m));
		});
		
		var activeTab = this.activeTab;
		if (activeTab === undefined) {
			activeTab = items.length ? 0 : undefined;
		}
		
		return Ext.apply(config, {
			xtype: "tabpanel"
			,activeTab: activeTab
			,items: items
			,tabPosition: "bottom"
		});
	}
	
	,onClose: function() {
		var tab = this.tab;
		if (tab) tab.items.each(function(c) {
			c.onClose();
		});
		this.callParent(arguments);
	}
});

Oce.deps.reg('eo.modules.ModuleGroupModule');
	
}); // deps