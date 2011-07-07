Oce.deps.wait("Oce.GridModule", function() {
	
var ColumnFiltersPlugin = Ext.extend(eo.Class, {
	
	constructor: function(gm) {
		gm.beforeCreateGrid = gm.beforeCreateGrid.createSequence(this.beforeCreateGrid, this);
		gm.afterCreateGrid = gm.afterCreateGrid.createSequence(this.afterCreateGrid, this);
		gm.openActionHandlers["gridFilters"] = this.openActionHandler;
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
		return new Ext.ux.grid.GridFilters({
			encode: true
			,local: false
			,paramPrefix: 'json_filters'
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
	
}); // deps