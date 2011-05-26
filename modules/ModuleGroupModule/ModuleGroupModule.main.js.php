/**
 * @author Éric Ortéga <eric@planysphere.fr>
 * 
 * This file is intended to be included in a closure by the JS executor of
 * TabModule.
 */
//<script type="text/javascript">

(function() {
//Oce.deps.wait([ns + ".Slot", ns + ".SlotPortlet"], function() {
	
var ns = "eo.module.ModuleGroupModule",
	NS = Ext.ns(ns);
	
var ModuleContainer = Ext.extend(Ext.Container, {
	
	initComponent: function() {
		
		Ext.apply(this, {
			layout: "fit"
		});
		
		ModuleContainer.superclass.initComponent.call(this);
		
		this.on({
			scope: this
			,add: function(ct, cp) {
				this.component = cp;
				this.doLayout();
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

var o = {
	
	modules: [
<?php $comma = ''; foreach ($modules as $module): ?>
		<?php echo "$comma$module" ?>
<?php $comma = ','; endforeach ?>
	]
	
	,doConstruct: function() {
		Ext.each(this.modules, function(m) {
			var cmd = m.cmd;
			if (Ext.isString(cmd)) {
				cmd = m.cmd = Oce.cmd(cmd);
			}
			// preload module code
			cmd();
		});
	}
	
//	,createTab: function() {
//		var r = spp.createTab.apply(this, arguments);
//		
//		// load if not already done
//		this.load(false);
//		
//		return r;
//	}
//	
	,createTabConfig: function() {
		var config = Ext.apply(spp.createTabConfig.call(this), this.config.tab),
			me = this,
			items = [];
			
		Ext.each(this.modules, function(m) {
			items.push(new ModuleContainer(m));
		})
		
		return Ext.apply(config, {
			xtype: "tabpanel"
			,items: items
		});
	}
	
	,onClose: function() {
		var tab = this.tab;
		if (tab) tab.items.each(function(c) {
			c.onClose();
		});
		spp.onClose.apply(this, arguments);
	}
};
	
MODULE.override(o);

//}); // deps
})(); // closure

//</script>