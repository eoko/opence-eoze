Oce.deps.wait([
	"Ext.ux.menu.ListMenu"
	,"Ext.ux.grid.GridFilters"
	,"Ext.ux.grid.filter.BooleanFilter"
	,"Ext.ux.grid.filter.DateFilter"
	,"Ext.ux.grid.filter.ListFilter"
	,"Ext.ux.grid.filter.NumericFilter"
	,"Ext.ux.grid.filter.StringFilter"
], function() {
	
Ext.override(Ext.ux.grid.GridFilters, {
    menuFilterText : 'Filtres'
});
Ext.override(Ext.ux.menu.ListMenu, {
    loadingText : 'Chargement...'
});
Ext.override(Ext.ux.grid.filter.BooleanFilter, {
	yesText : 'Oui',
	noText : 'Non'
});
Ext.override(Ext.ux.grid.filter.DateFilter, {
    afterText : 'Avant',
    beforeText : 'Après',
    dateFormat : 'd/m/Y',
    onText : 'Le'
});
Ext.override(Ext.ux.grid.filter.NumericFilter, {
    menuItemCfgs : {
        emptyText: 'Entrez une valeur...',
        selectOnFocus: true,
        width: 125
    }
});
Ext.override(Ext.ux.grid.filter.StringFilter, {
    emptyText: 'Texte à chercher...'
});

}); // deps