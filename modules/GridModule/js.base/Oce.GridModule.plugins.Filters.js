Oce.deps.wait("Oce.GridModule", function() {
	
var ColumnFiltersPlugin = Ext.extend(eo.Class, {
	
	constructor: function(gm) {
		gm.beforeCreateGrid = gm.beforeCreateGrid.createSequence(this.beforeCreateGrid, this);
		gm.afterCreateGrid = gm.afterCreateGrid.createSequence(this.afterCreateGrid, this);
		gm.openActionHandlers["gridFilters"] = this.openActionHandler;
		
		var me = this,
			uper = gm.getLastGridLoadParams;
		gm.getLastGridLoadParams = function() {
			var p = uper.call(this),
				cf = me.gridFilters;
			return Ext.apply(p, cf.buildQuery(cf.getFilterData()));
		};
	}
	
	,beforeCreateGrid: function(config) {
		var p = this.createGridPlugin(),
			pt = config.pagingToolbar;
		config.plugins = eo.pushWrap(config.plugins, p);
		if (pt) {
			pt.plugins = eo.pushWrap(pt.plugins, p);
		}
	}
	
	,createGridPlugin: function() {
		return this.gridFilters = new Ext.ux.grid.GridFilters({
			encode: true
			,local: false
			,paramPrefix: 'json_columnFilters'
		});
	}
	
	,openActionHandler: function(filters) {
		var g = this.grid;
		g.filters.applyState(g, { filters: filters });
		if (this.firstLoad) {
			this.reload();
		}
	}
	
});

Oce.GridModule.plugins.ColumnFilters = ColumnFiltersPlugin;
Oce.GridModule.plugins.GridFilters = ColumnFiltersPlugin;
	
}); // deps