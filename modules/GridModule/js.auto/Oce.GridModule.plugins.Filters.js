Oce.deps.wait("Oce.GridModule", function() {
	
var ColumnFiltersPlugin = Ext.extend(eo.Class, {
	
	constructor: function(gm) {
		gm.beforeCreateGrid = gm.beforeCreateGrid.createSequence(this.beforeCreateGrid, this);
	}
	
	,beforeCreateGrid: function(config) {
		var p = this.createGridPlugin(),
			cp = config.plugins;
		if (cp) {
			if (Ext.isArray(cp)) {
				cp.push(p);
			} else {
				config.plugins = [cp, p];
			}
		} else {
			config.plugins = [p];
		}
	}
	
	,createGridPlugin: function() {
		return new Ext.ux.grid.GridFilters({
			encode: true
			,local: false
		});
	}
	
});

Oce.GridModule.plugins.ColumnFilters = ColumnFiltersPlugin;
	
}); // deps