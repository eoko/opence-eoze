Oce.deps.wait("Oce.GridModule", function() {

/**
 * GridModule search plugin.
 * 
 * Configuration
 * -------------
 * 
 * Set the module configuration `extra.search` to `true` to enable the search plugin.
 * 
 * Set the columns configuration `extra.search.enabled` to make a column searchable,
 * and `extra.search.selected` to have the column selected by default in the search
 * menu.
 * 
 * **Warning:** if no column is made searchable, then the search field will **not**
 * be displayed.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 6 janv. 2012
 */
Oce.GridModule.plugins.Search = Ext.extend(Object, {
    
    selectOnFocus: true
    ,minChars: undefined
    ,minLength: undefined
    
    /**
     * @cfg {Integer} width
     * The width of the {@ling Ext.Toolbar.ButtonGroup} that will be added to
     * the ribbon of the module.
     */
    ,width: 150
    
    /**
     * @cfg {Integer} delayAfterSelect 
     * Delay to wait before triggering a search query, after the searched field 
     * selection has been changed by the user. If the selection changes again 
     * before the delay is elasped, it will be reset to this value.
     */
    ,delayAfterSelect: 982
    
    /**
     * @cfg {Oce.GridModule} gridModule required
     */
    ,gridModule: undefined
    
    /**
     * {Array} lastSearchFields
     * @private
     */
    ,lastSearchFields: undefined

    /**
     * @cfg {Object} paramNames Params name map (defaults to {fields:'fields', query:'query'}
     */
    ,paramNames: {
         fields:'searchFields'
        ,query:'query'
    }
    
    ,constructor: function(gm, config) {
        
        if (config === false) {
            return false;
        }
        
        this.gridModule = gm;
        
        if (Ext.isObject(config)) {
            Ext.apply(this, config);
        }
        
        gm.beforeCreateToolbar =
				Ext.Function.createSequence(gm.beforeCreateToolbar, this.beforeCreateToolbar, this);
    }
    
    // private
    ,beforeCreateToolbar: function(items) {
        // Search the last index before the right aligned items
        for (var i=0, l=items.length; i<l; i++) {
            if (items[i] === '->') {
                break;
            }
        }

        var searchField = this.field = new Ext.form.TwinTriggerField({

            selectOnFocus: this.selectOnFocus
            ,trigger1Class: 'x-form-clear-trigger'
            ,trigger2Class: this.minChars ? 'x-hidden' : 'x-form-search-trigger'
            ,minLength: this.minLength

            ,listeners: {
                single: true
                ,scope: this
                ,render: function() {

                    if(this.minChars) {
                        this.field.el.on({
                            scope: this, 
                            buffer: 300, 
                            keyup: this.onKeyUp
                        });
                    }

                    // install key map
                    var map = new Ext.KeyMap(this.field.el, [{
                         key: Ext.EventObject.ENTER
                        ,scope: this
                        ,fn: this.onTriggerSearch
                    },{
                         key: Ext.EventObject.ESC
                        ,scope: this
                        ,fn: this.onTriggerClear
                    }]);
                    map.stopEvent = true;
                }
            }

//                ,onTrigger1Click: this.minChars ? Ext.emptyFn : this.onTriggerClear.createDelegate(this)
            ,onTrigger1Click: this.onTriggerClear.createDelegate(this)
            ,onTrigger2Click: this.onTriggerSearch.createDelegate(this)
        });
        
        // store mainSearchField
        this.gridModule.mainSearchField = searchField;
        
        // initialize menu
        var menu = this.fieldsMenu = new Ext.menu.Menu({
            listeners: {
                scope: this
                ,beforeshow: this.onBeforeShowFieldMenu
            }
        });
        
        if (false === this.onBeforeShowFieldMenu(menu)) {
            menu.destroy();
            return;
        }

        items.splice(i, 0, {
            xtype: 'buttongroup'
            ,height: 78
            ,width: this.width
            ,padding: 5
            ,title: 'Recherche'
            ,layout: {
                type: 'vbox'
                ,align: 'stretch'
            }
            ,items: [
                searchField
                ,{
                    text: 'Champs'
                    ,align: 'stretch'
                    ,flex: 1
                    ,menu: menu
                }
            ]
        });
    }
    
    ,onBeforeShowFieldMenu: function(colMenu) {
        
        var gm = this.gridModule,
            cm = gm.grid.getColumnModel(),
            colCount = cm.getColumnCount();
        
        colMenu.removeAll();
        
        // Check all item
        var checkAllItem = new Ext.menu.CheckItem({
             text: 'Tous'
//            ,checked: !(this.checkIndexes instanceof Array)
            ,hideOnClick: false
            ,handler: function(item) {
                var checked = ! item.checked;
                item.parentMenu.items.each(function(i) {
                    if(item !== i && i.setChecked && !i.disabled) {
                        i.setChecked(checked);
                    }
                });
            }
        });
        colMenu.add(checkAllItem,'-');

        // --- Items ---
        var anySelected = false,
            checkAllSelected = true,
            hasItems = false,
            leafItems = [];

        var cg = gm.extra.columnGroups,
            groups = {},
            groupMenus;
        if (cg) {
            groupMenus = [];
            Ext.iterate(cg.items, function(title, items) {
                var menu = new Ext.menu.Menu();

                groupMenus.push(menu);

                menu.parentItem = colMenu.add(new Ext.menu.CheckItem({
                    hideOnClick: false
                    ,text: title
                    ,menu: menu
                    ,checkHandler: function(cb, check) {
		                if (menu.items) {
			                menu.items.each(function(item) {
				                item.setChecked(check, true);
			                });
		                }
	                }
                }));

                Ext.each(items, function(item) {
                    groups[item] = menu;
                });
            });
            var groupSep = new Ext.menu.Separator({
                hidden: true
            });
            colMenu.add(groupSep);
        }
        
        var refreshSearch = new Ext.util.DelayedTask(function() {
	        this.doSearch(this.getSelectedFields());
        }, this);

		var updateParentItem = function(menu) {
			var pi = menu.parentItem;
			if (pi) {
				var checked = true;
				pi.menu.items.each(function(item) {
					if (item.setChecked && !item.checked) {
						checked = false;
						return false;
					}
				});
				if (checked !== pi.checked) {
					pi.setChecked(checked, true);
				}
			}
		};

        var first = !this.lastSearchFields,
            lsf = this.lastSearchFields = this.lastSearchFields || [];
        
        for (var i = 0; i < colCount; i++) {
            var colConfig = cm.config[i],
                cfg = colConfig.search !== false 
                    && colConfig.extra 
                    && colConfig.extra.search;
            if (cfg && cfg.enabled !== false) {
                
                hasItems = true;

                var di = colConfig.dataIndex,
                    select = cfg.select || cfg.selected;
                
                if (first) {
                    if (select) {
                        lsf.push(di);
                    }
                } else {
                    select = lsf.indexOf(di) !== -1;
                }
                
                if (!select) {
                    checkAllSelected = false;
                } else {
                    anySelected = true;
                }
                
                var dest = (groups[colConfig.dataIndex] || colMenu);
                
                if (groupSep && dest === colMenu) {
                    groupSep.show();
                }
                
                var item = new Ext.menu.CheckItem({
                    text: cm.getColumnHeader(i)
                    ,dataIndex: di
                    ,checked: select
                    ,hideOnClick: false
                    ,scope: this
                    ,checkHandler: function(item) {
                        refreshSearch.delay(this.delayAfterSelect);
		                updateParentItem(item.ownerCt);
                    }
                });

	            leafItems.push(item);
                dest.add(item);
            }
        }
        
        // if none selected, check all
        if (!anySelected) {
            Ext.each(leafItems, function(item) {
                item.setChecked(true);
            });
        }

        checkAllItem.setChecked(checkAllSelected);

        // Finish group menus -- determine checked statut from items
        if (groupMenus) {
            Ext.each(groupMenus, updateParentItem);
        }
        
        return hasItems;
    }
    
    /**
     * Gets the selected fields in the menu.
     * @return {Array}
     * @private
     */
    ,getSelectedFields: function() {
        var fields = [],
            items = this.fieldsMenu.items;
        var walk = function(items) {
            items.each(function(item) {
                if (item.menu) {
                    var mi = item.menu.items;
                    if (mi) {
                        walk(mi);
                    }
                } else if (item instanceof Ext.menu.CheckItem) {
                    if(item.checked) {
                        fields.push(item.dataIndex);
                    }
                }
            });
        }
        if (items) {
            walk(items);
        }
        return fields;
    }
    
    ,onKeyUp: function() {
        var length = this.field.getValue().toString().length;
        if (0 === length || this.minChars <= length) {
            this.onTriggerSearch();
        }
    }
    
    ,onTriggerClear: function() {
        if (this.field.getValue()) {
            this.field.setValue('');
            this.field.focus();
            this.onTriggerSearch();
        }
    }
    
    ,doSearch: function(fields) {
        
        var store = this.gridModule.store,
            val = this.field.getValue(),
	        lastFields = this.lastSearchFields;

		// Save fields before is needed because the method will return if there was no search
		// and there is still no search
		this.lastSearchFields = fields;

        if (this.lastSearchQuery === val
	            && (String(lastFields) === String(fields) || val === '')) {
            return;
        }

        this.lastSearchQuery = val;
        
        // clear start (necessary if we have paging)
        if(store.lastOptions && store.lastOptions.params) {
            store.lastOptions.params[store.paramNames.start] = 0;
        }
        
        // add fields and query to baseParams of store
        delete(store.baseParams[this.paramNames.fields]);
        delete(store.baseParams[this.paramNames.query]);
        if (store.lastOptions && store.lastOptions.params) {
            delete(store.lastOptions.params[this.paramNames.fields]);
            delete(store.lastOptions.params[this.paramNames.query]);
        }
        
        if(fields.length) {
            store.baseParams[this.paramNames.fields] = Ext.encode(fields);
            store.baseParams[this.paramNames.query] = val;
        }

        // reload store
        store.reload();
    }
    
    ,onTriggerSearch: function() {
        this.doSearch(this.getSelectedFields());
    }
    
});

// TODO shortcut
var map = new Ext.KeyMap(document, {
    key: Ext.EventObject.F
    ,ctrl: true
    ,fn: function() {
        var m = Oce.getFrontModule(),
            f = m && m.mainSearchField;
        if (f) {
            f.focus();
        }
    }
    ,stopEvent: true
});

}); // deps
