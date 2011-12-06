/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 17 nov. 2011
 */
(function() {
	
var NS = Ext.ns('Oce.Modules.GridModule');

NS.TabBuilder = Ext.extend(Object, {

	constructor: function(formConfig) {
		this.formConfig     = formConfig;
		this.addedItemNames = {};
	}
	
	// private
	,convertItem: function(index) {
		
		var formConfig     = this.formConfig,
			addedItemNames = this.addedItemNames;
		
		if (Ext.isNumber(index)) {
			var item = formConfig.originalIndexes[index];
			if (item) {
				addedItemNames[item.name] = true;
				return item;
			} else {
				if (item === null) {
					eo.warn('Item inactive for this action: ' + index);
				} else {
					throw new Error('Invalid item index: ' + index);
				}
				return undefined;
			}
		} else if (index.field) {
			var i = index.field;
			delete index.field;
			item = Ext.apply({}, index, formConfig.originalIndexes[i]);
			addedItemNames[item.name] = true;
			return item;
		} else {
			return false;
		}
	}
	
	// private
	,convertObjectItemIndexes: function(o) {
		Oce.walk(o, function(i,oo) {
			if (Ext.isArray(oo) || Ext.isObject(oo)) {
				this.convertObjectItemIndexes(oo);
			}
		}, this);
		if (o.items) {
			Oce.walk(o.items, function(i, item) {
				var cItem;
				if ((cItem = this.convertItem(item))) {
					o.items[i] = cItem;
				}
			}, this);
		}
	}
	
	// private
	,convertItemIndexes: function(items) {
		
		this.convertObjectItemIndexes(items);
		
		Oce.walk(items, function(i, item) {
			var cItem = this.convertItem(item);
			if (cItem) {
				items[i] = cItem;
			}
		}, this);

		return items;
	}
	
	/**
	 * Returns true if the item with the given name was added to
	 * one of the elements passed to makeFormTab (that is, if one
	 * of these elements included the given item by its index).
	 */
	,wasItemAdded: function(name) {
		return this.addedItemNames[name];
	}

	,makeFormTab: function(tabName, tabComponents) {
		return {
			title: tabName
			,tabName: tabName.toLowerCase()
			,autoScroll:true
			,defaults:{anchor:'100%'}
			,items: this.convertItemIndexes(tabComponents)
		};
	}
});
})(); // closure