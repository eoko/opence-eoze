// vim: ts=4:sw=4:nu:fdc=4:nospell
/**
 * Search plugin for Ext.grid.GridPanel, Ext.grid.EditorGrid ver. 2.x or subclasses of them
 *
 * @author    Ing. Jozef Sakalos
 * @copyright (c) 2008, by Ing. Jozef Sakalos
 * @date      17. January 2008
 * @version   $Id: Ext.ux.grid.Search.js 220 2008-04-29 21:46:51Z jozo $
 *
 * @license Ext.ux.grid.Search is licensed under the terms of
 * the Open Source LGPL 3.0 license.  Commercial use is permitted to the extent
 * that the code/component(s) do NOT become part of another Open Source or Commercially
 * licensed development library or toolkit without explicit permission.
 * 
 * License details: http://www.gnu.org/licenses/lgpl.html
 */

/*global Ext */

Ext.ns('Ext.ux.grid');

/**
 * @class Ext.ux.grid.Search
 * @extends Ext.util.Observable
 * @param {Object} config configuration object
 * @constructor
 */
Ext.ux.grid.Search = function(config) {
	Ext.apply(this, config);
	Ext.ux.grid.Search.superclass.constructor.call(this);
}; // eo constructor

Ext.extend(Ext.ux.grid.Search, Ext.util.Observable, {
	/**
	 * cfg {Boolean} autoFocus true to try to focus the input field on each store load (defaults to undefined)
	 */

	/**
	 * @cfg {String} searchText Text to display on menu button
	 */
	 searchText:'Rechercher'

	 ,applyFieldChangeText:'Mettre à jour'
	 ,applyFieldChangeIconcls : 'refresh'

	/**
	 * @cfg {String} searchTipText Text to display as input tooltip. Set to '' for no tooltip
	 */ 
	,searchTipText:'Tapez un texte à rechercher, puis Entrer.'

	/**
	 * @cfg {String} selectAllText Text to display on menu item that selects all fields
	 */
	,selectAllText:'Tout sélectionner'

	/**
	 * @cfg {String} position Where to display the search controls. Valid values are top and bottom (defaults to bottom)
	 * Corresponding toolbar has to exist at least with mimimum configuration tbar:[] for position:top or bbar:[]
	 * for position bottom. Plugin does NOT create any toolbar.
	 */
	,position:'bottom'

	/**
	 * @cfg {String} iconCls Icon class for menu button (defaults to icon-magnifier)
	 */
	,iconCls:'icon-magnifier'

	/**
	 * @cfg {String/Array} checkIndexes Which indexes to check by default. Can be either 'all' for all indexes
	 * or array of dataIndex names, e.g. ['persFirstName', 'persLastName']
	 */
	,checkIndexes:'all'

	/**
	 * @cfg {Array} disableIndexes Array of index names to disable (not show in the menu), e.g. ['persTitle', 'persTitle2']
	 */
	,disableIndexes:[]

	,enableIndexes:[]

	/**
	 * @cfg {String} dateFormat how to format date values. If undefined (the default) 
	 * date is formatted as configured in colummn model
	 */
	,dateFormat:undefined

	/**
	 * @cfg {Boolean} showSelectAll Select All item is shown in menu if true (defaults to true)
	 */
	,showSelectAll:true

	/**
	 * @cfg {String} menuStyle Valid values are 'checkbox' and 'radio'. If menuStyle is radio
	 * then only one field can be searched at a time and selectAll is automatically switched off.
	 */
	,menuStyle:'checkbox'

	/**
	 * @cfg {Number} minChars minimum characters to type before the request is made. If undefined (the default)
	 * the trigger field shows magnifier icon and you need to click it or press enter for search to start. If it
	 * is defined and greater than 0 then maginfier is not shown and search starts after minChars are typed.
	 */

	/**
	 * @cfg {String} minCharsTipText Tooltip to display if minChars is > 0
	 */
	,minCharsTipText:'Tapez au moins {0} caractères'

	/**
	 * @cfg {String} mode Use 'remote' for remote stores or 'local' for local stores. If mode is local
	 * no data requests are sent to server the grid's store is filtered instead (defaults to 'remote')
	 */
	,mode:'remote'

	/**
	 * @cfg {Array} readonlyIndexes Array of index names to disable (show in menu disabled), e.g. ['persTitle', 'persTitle2']
	 */

	/**
	 * @cfg {Number} width Width of input field in pixels (defaults to 100)
	 */
	,width:100

	/**
	 * @cfg {String} xtype xtype is usually not used to instantiate this plugin but you have a chance to identify it
	 */
	,xtype:'gridsearch'

	/**
	 * @cfg {Object} paramNames Params name map (defaults to {fields:'fields', query:'query'}
	 */
	,paramNames: {
		 fields:'searchFields'
		,query:'query'
	}

	/**
	 * @cfg {String} shortcutKey Key to fucus the input field (defaults to r = Sea_r_ch). Empty string disables shortcut
	 */
	,shortcutKey:'r'

	/**
	 * @cfg {String} shortcutModifier Modifier for shortcutKey. Valid values: alt, ctrl, shift (defaults to alt)
	 */
	,shortcutModifier:'alt'

	/**
	 * @cfg {String} align 'left' or 'right' (defaults to 'left')
	 */

	/**
	 * @cfg {Number} minLength force user to type this many character before he can make a search
	 */

	/**
	 * @cfg {Ext.Panel/String} toolbarContainer Panel (or id of the panel) which contains toolbar we want to render
	 * search controls to (defaults to this.grid, the grid this plugin is plugged-in into)
	 */
	
	// {{{
	/**
	 * private
	 * @param {Ext.grid.GridPanel/Ext.grid.EditorGrid} grid reference to grid this plugin is used for
	 */
	,init:function(grid) {
		this.grid = grid;

		// setup toolbar container if id was given
		if('string' === typeof this.toolbarContainer) {
			this.toolbarContainer = Ext.getCmp(this.toolbarContainer);
		}

		// do our processing after grid render and reconfigure
		grid.onRender = Ext.Function.createSequence(grid.onRender, this.onRender, this);
		grid.reconfigure = Ext.Function.createSequence(grid.reconfigure, this.reconfigure, this);
	} // eo function init
	// }}}

	// {{{
	/**
	 * private add plugin controls to <b>existing</b> toolbar and calls reconfigure
	 */
	,onRender:function() {
		var panel = this.toolbarContainer || this.grid;
		var tb = 'bottom' === this.position ? panel.bottomToolbar : panel.topToolbar;

		// add menu
		this.menu = new Ext.menu.Menu();

		// rx -- add auto update on field menu selection close
		this.menu.on('beforehide', function() {
			var changed = false;
			Ext.each(this.menu.items.items, function(item) {
				if (item instanceof Ext.menu.CheckItem) {
					changed = changed || this.lastSearchChecked[item.id] != item.checked;
				}
			}, this)
			if (changed) {
				this.onTriggerSearch();
			}
		}.createDelegate(this));
		// end rx

		// handle position
		if('right' === this.align) {
			tb.addFill();
		}
		else {
			if(0 < tb.items.getCount()) {
				tb.addSeparator();
			}
		}

		// add menu button
		if (this.enableIndexes.length > 1) {
			tb.add({
				 text:this.searchText
				,menu:this.menu
				,iconCls:this.iconCls
			});
		} else {
			tb.add({
				xtype: 'tbtext'
				,text:this.searchText
//				,cls:this.iconCls
			});
			this.checkIndexes = this.enableIndexes;
		}

		// add input field (TwinTriggerField in fact)
		this.field = new Ext.form.TwinTriggerField({
			 width:this.width
			,selectOnFocus:undefined === this.selectOnFocus ? true : this.selectOnFocus
			,trigger1Class:'x-form-clear-trigger'
			,trigger2Class:this.minChars ? 'x-hidden' : 'x-form-search-trigger'
//			,onTrigger1Click:this.minChars ? Ext.emptyFn : this.onTriggerClear.createDelegate(this)
			,onTrigger1Click:this.onTriggerClear.createDelegate(this)
			,onTrigger2Click:this.onTriggerSearch.createDelegate(this)
			,minLength:this.minLength
		});

		// install event handlers on input field
		this.field.on('render', function() {
// rx-			this.field.el.dom.qtip = this.minChars ? String.format(this.minCharsTipText, this.minChars) : this.searchTipText;

			if(this.minChars) {
				this.field.el.on({scope:this, buffer:300, keyup:this.onKeyUp});
			}

			// install key map
			var map = new Ext.KeyMap(this.field.el, [{
				 key:Ext.EventObject.ENTER
				,scope:this
				,fn:this.onTriggerSearch
			},{
				 key:Ext.EventObject.ESC
				,scope:this
				,fn:this.onTriggerClear
			}]);
			map.stopEvent = true;
		}, this, {single:true});

		tb.add(this.field);

		// reconfigure
		this.reconfigure();

		// update checked state
		this.updateLastSearchCheckIndexes();

		// keyMap
		if(this.shortcutKey && this.shortcutModifier) {
			var shortcutEl = this.grid.getEl();
			var shortcutCfg = [{
				 key:this.shortcutKey
				,scope:this
				,stopEvent:true
				,fn:function() {
					this.field.focus();
				}
			}];
			shortcutCfg[0][this.shortcutModifier] = true;
			this.keymap = new Ext.KeyMap(shortcutEl, shortcutCfg);
		}

		if(true === this.autoFocus) {
			this.grid.store.on({scope:this, load:function(){this.field.focus();}});
		}
	} // eo function onRender
	// }}}
	// {{{
	/**
	 * field el keypup event handler. Triggers the search
	 * @private
	 */
	,onKeyUp:function() {
		var length = this.field.getValue().toString().length;
		if(0 === length || this.minChars <= length) {
			this.onTriggerSearch();
		}
	} // eo function onKeyUp
	// }}}
	// {{{
	/**
	 * private Clear Trigger click handler
	 */
	,onTriggerClear:function() {
		if(this.field.getValue()) {
			this.field.setValue('');
			this.field.focus();
			this.onTriggerSearch();
		}
	} // eo function onTriggerClear
	// }}}

	,updateLastSearchCheckIndexes: function() {

		if (!this.lastSearchChecked) this.lastSearchChecked = {};

		this.menu.items.each(function(item) {
			if (item instanceof Ext.menu.CheckItem) {
				this.lastSearchChecked[item.id] = item.checked;
			}
		}, this);
	}

	// {{{
	/**
	 * private Search Trigger click handler (executes the search, local or remote)
	 */
	,onTriggerSearch:function() {
		if(!this.field.isValid()) {
			return;
		}
		var val = this.field.getValue();
		var store = this.grid.store;

		// grid's store filter
		if('local' === this.mode) {
			store.clearFilter();
			if(val) {
				store.filterBy(function(r) {
					var retval = false;
					this.menu.items.each(function(item) {
						if(!item.checked || retval) {
							return;
						}
						var rv = r.get(item.dataIndex);
						rv = rv instanceof Date ? rv.format(this.dateFormat || r.fields.get(item.dataIndex).dateFormat) : rv;
						var re = new RegExp(val, 'gi');
						retval = re.test(rv);
					}, this);
					if(retval) {
						return true;
					}
					return retval;
				}, this);
			}
			else {
			}
		}
		// ask server to filter records
		else {
			// clear start (necessary if we have paging)
			if(store.lastOptions && store.lastOptions.params) {
				store.lastOptions.params[store.paramNames.start] = 0;
			}

			this.updateLastSearchCheckIndexes(); // rx+

			// get fields to search array
			var fields = [];
			var walk = function(items) {
				items.each(function(item) {
					if (item.menu) {
						walk(item.menu.items);
					} else if (item instanceof Ext.menu.CheckItem) {
						if(item.checked) {
							fields.push(item.dataIndex);
						}
					}
				});
			}
			walk(this.menu.items);
//			this.menu.;

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

	} // eo function onTriggerSearch
	// }}}
	// {{{
	/**
	 * @param {Boolean} true to disable search (TwinTriggerField), false to enable
	 */
	,setDisabled:function() {
		this.field.setDisabled.apply(this.field, arguments);
	} // eo function setDisabled
	// }}}
	// {{{
	/**
	 * Enable search (TwinTriggerField)
	 */
	,enable:function() {
		this.setDisabled(false);
	} // eo function enable
	// }}}
	// {{{
	/**
	 * Enable search (TwinTriggerField)
	 */
	,disable:function() {
		this.setDisabled(true);
	} // eo function disable
	// }}}
	// {{{
	/**
	 * private (re)configures the plugin, creates menu items from column model
	 */
	,reconfigure:function() {

		// {{{
		// remove old items
		var menu = this.menu;
		menu.removeAll();

		// add Select All item plus separator
		if(this.showSelectAll && 'radio' !== this.menuStyle) {
			menu.add(new Ext.menu.CheckItem({
				 text:this.selectAllText
				,checked:!(this.checkIndexes instanceof Array)
				,hideOnClick:false
				,handler:function(item) {
					var checked = ! item.checked;
					item.parentMenu.items.each(function(i) {
						if(item !== i && i.setChecked && !i.disabled) {
							i.setChecked(checked);
						}
					});
				}
			}),'-');
		}

		// }}}

		// Column Groups
		var groups = {};
		if (this.columnGroups) {
			Ext.iterate(this.columnGroups.items, function(title,items) {
				var subMenu = new Ext.menu.Menu();

				menu.add(new Ext.menu.Item({
					hideOnClick: false
					,text: title
					,menu: subMenu
//					,checkHandler: function(cb, check) {
					,setChecked: function(check) {
						//if (!cb.initiated) return;
						if (subMenu.items) {
							subMenu.items.each(function(item) {
								item.setChecked(check);
							});
						}
					}
				}));

				Ext.each(items, function(item) {
					groups[item] = subMenu;
				});
			});
		}

		// {{{
		// add new items
		var cm = this.grid.colModel;
		var group = undefined;
		if('radio' === this.menuStyle) {
			group = 'g' + (new Date).getTime();
		}
		Ext.each(cm.config, function(config) {
			var disable = false;
			var enable = true;
			if(config.header && config.dataIndex) {
				if (this.disableIndexes.length > 0) {
					Ext.each(this.disableIndexes, function(item) {
						disable = disable ? disable : item === config.dataIndex;
					});
				}
				if (this.enableIndexes.length > 0) {
					enable = false;
					Ext.each(this.enableIndexes, function(item) {
						enable = enable || item === config.dataIndex;
					});
				}
				if(enable && !disable) {
					(groups[config.dataIndex] || menu).add(new Ext.menu.CheckItem({
						 text:config.header
						,hideOnClick:false
						,group:group
						,checked:'all' === this.checkIndexes
						,dataIndex:config.dataIndex
					}));
				}
			}
		}, this);
		// }}}
		// {{{
		// check items
		if(this.checkIndexes instanceof Array) {
			var find = function(items, di) {
				var found;
				var item = items && items.each(function(item) {
					if (item.dataIndex === di) {
						found = item;
						return false;
					}
					if (item.menu) {
						found = find(item.menu.items, di);
						if (found) return false;
					}
				});
				return found;
			};
			Ext.each(this.checkIndexes, function(di) {
//				var item = menu.items.find(function(itm) {
//					return itm.dataIndex === di;
//				});
				var item = find(menu.items, di);
				if(item) {
					item.setChecked(true, true);
				}
			}, this);
		}
		// }}}
		// {{{
		// disable items
		if(this.readonlyIndexes instanceof Array) {
			Ext.each(this.readonlyIndexes, function(di) {
				var item = menu.items.find(function(itm) {
					return itm.dataIndex === di;
				});
				if(item) {
					item.disable();
				}
			}, this);
		}
		// }}}

		// rx --- "Apply" item
		if(this.showSelectAll && 'radio' !== this.menuStyle) {

			menu.add(new Ext.menu.Separator())

			menu.add(new Ext.menu.Item({
				 text:this.applyFieldChangeText
				,iconCls : this.applyFieldChangeIconcls
				,hideOnClick:false
				,handler: function() {
					this.onTriggerSearch();
					this.menu.hide();
				}.createDelegate(this)
//					function(item) {
//					var checked = ! item.checked;
//					item.parentMenu.items.each(function(i) {
//						if(item !== i && i.setChecked && !i.disabled) {
//							i.setChecked(checked);
//						}
//					});
//				}
			}));
		}
		// --- end rx

	} // eo function reconfigure
	// }}}

}); // eo extend

// eof
