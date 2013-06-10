/**
 * Copyright (C) 2013 Eoko
 *
 * This file is part of Opence.
 *
 * Opence is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Opence is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Opence. If not, see <http://www.gnu.org/licenses/gpl.txt>.
 *
 * @copyright Copyright (C) 2013 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

/**
 * Adds a method to a {@link Ext.grid.Panel}, that creates a show/hide column menu, with support for
 * column groups.
 *
 * @since 2013-06-12 15:09
 */
Ext4.define('Eoze.grid.mixin.GroupedColumnMenu', {

	requires: [
		'Eoze.Ext.menu.CheckItem.TriState'
	]

	/**
	 * Set to false by default.
	 *
	 * @inheritdoc
	 */
	,enableColumnHide: false

	/**
	 * Get show/hide column menu items.
	 *
	 * @return {Ext.menu.Items[]}
	 */
	,getColumnMenuItems: function() {
		var Ext = Ext4,
			me = this,
			headerContainer = this.headerCt,
			allItems = [],
			menuItems = [],
			i = 0,
			item,
			items = headerContainer.query('>gridcolumn[hideable]'),
			itemsLn = items.length,
			menuItem,
			group,
			groups = {};

		for (; i < itemsLn; i++) {
			item = items[i];
			menuItem = new Ext.menu.CheckItem({
				text: item.menuText || item.text,
				checked: !item.hidden,
				hideOnClick: false,
				headerId: item.id,
				menu: item.isGroupHeader ? headerContainer.getColumnMenu(item) : undefined,
				checkHandler: headerContainer.onColumnCheckChange,
				scope: headerContainer
			});

			group = item.group;
			(groups[group] = groups[group] || []).push(menuItem);
			allItems.push(menuItem);
			//menuItems.push(menuItem);

			// If the header is ever destroyed - for instance by dragging out the last remaining sub header,
			// then the associated menu item must also be destroyed.
			item.on({
				destroy: Ext.Function.bind(menuItem.destroy, menuItem)
			});
		}

		var ungroupedItems = groups['undefined'],
			groupsConfig = this.columnGroups;
		delete groups[undefined];

		if (Ext.Object.getKeys(groups).length > 0) {

			Ext.iterate(groups, function(id, items) {
				var parentItem = new Ext.menu.CheckItem({
					text: groupsConfig[id].text
					,menu: items
					,checkHandler: function(item, checked) {
						Ext.suspendLayouts();
						item.menu.items.each(function(child) {
							child.setChecked(checked);
						});
						Ext.resumeLayouts();
						me.updateLayout();
					}
					,updateCheckFromChildren: function() {
						var hasChecked = false,
							allChecked = true;
						this.menu.items.each(function(child) {
							if (child.checked) {
								hasChecked = true;
							} else {
								allChecked = false;
							}
						});
						this.setChecked(allChecked, true);
						this.setUndetermined(hasChecked && !allChecked);
					}
				});

				menuItems.push(parentItem);

				items.forEach(function(item) {
					item.on('checkchange', function(item, checked) {
						parentItem.updateCheckFromChildren();
					});
				});
				parentItem.updateCheckFromChildren();
			});

			if (!Ext.isEmpty(ungroupedItems)) {
				menuItems.push('-');
			}

			// All item
			var allMenuItem = new Ext.menu.CheckItem({
				text: "Toutes" // i18n
				,checkHandler: function(item, checked) {
					Ext.suspendLayouts();
					allItems.forEach(function(child) {
						child.setChecked(checked);
					});
					Ext.resumeLayouts();
					me.updateLayout();
				}
				,updateCheckFromChildren: function() {
					var hasChecked = false,
						allChecked = true;
					allItems.forEach(function(child) {
						if (child.checked) {
							hasChecked = true;
						} else {
							allChecked = false;
						}
					});
					this.setChecked(allChecked, true);
					this.setUndetermined(hasChecked && !allChecked);
				}
			});

			allItems.forEach(function(item) {
				item.on('checkchange', allMenuItem.updateCheckFromChildren, allMenuItem);
			});
			allMenuItem.updateCheckFromChildren();

			menuItems.unshift(allMenuItem, '-');
		}

		menuItems = menuItems.concat(ungroupedItems);

		return menuItems;
	}

});
