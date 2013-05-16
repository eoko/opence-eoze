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
(function() {
	var Ext = Ext4;
/**
 * This override adds support for hiding tabs or groups to {@link Ext.ux.GroupTabPanel}.
 *
 * @since 2013-05-07 17:36
 */
Ext4.define('Eoze.Ext.ux.GroupTabPanel', {
	override: 'Ext.ux.GroupTabPanel'

	/**
	 * Overridden to:
	 * - append renderer for hidden nodes to the (single) column's renderer.
	 * - adjust groups & tabs initial visibility according to the items configuration
	 *
	 * @inheritdoc
	 */
	,initComponent: function() {

		this.addEvents(
			/**
			 * @event groupvisibilitychange
			 *
			 * Fires when the visibility of a group changes (activated by {@link #setGroupHidden}.
			 *
			 * @param {Ext.ux.GroupTabPanel} groupTabPanel The GroupTabPanel
			 * @param {Ext.Component} group The root group item
			 * @param {Boolean} visible True if the group is now visible, false if it is now hidden
			 */
			'groupvisibilitychange',
			/**
			 * @event tabvisibilitychange
			 *
			 * Fires when the visibility of a tab changes (activated by {@link #setTabHidden}.
			 *
			 * @param {Ext.ux.GroupTabPanel} groupTabPanel The GroupTabPanel
			 * @param {Ext.Component} card The item of which the visibility has changed
			 * @param {Boolean} visible True if the group is now visible, false if it is now hidden
			 */
			'tabvisibilitychange'
		);

		// Keep a reference to the group config (that is lost in parent method)
		Ext.each(this.items, function(groupItem) {
			var items = groupItem.items,
				rootItem = items && items[0];
			if (rootItem) {
				rootItem.groupItemConfig = groupItem;
			}
		});

		// Super
		this.callParent(arguments);

		// Append our own renderer to the default one
		var column = this.down('treecolumn');
		column.renderer = Ext.Function.createSequence(
			column.renderer,
			this.treeRendererForHiddenNodes
		);

		// Initialize node visibility according the associated card's hidden property
		Ext.each(this.cards, function(card) {

			// Adjust tab visibility
			this.setTabHidden(card, card.isHidden(), true);

			// Adjust group visibility
			var groupConfig = card.groupItemConfig; // only the root leaf (sic) will have it
			if (groupConfig) {
				this.setGroupHidden(card, groupConfig.hidden, true);

				// We could try to fix the bug 'group expanded param is ignored' here,
				// that will trigger a bug in rendering further on...
			}
		}, this);
	}

	/**
	 * @private
	 */
	,treeRendererForHiddenNodes: function(value, metaData, record /*, rowIdx, colIdx, store, view */) {
		if (record.get('hidden')) {
			metaData.style = metaData.style || '';
			metaData.style += 'display: none;';
		}
		if (record.get('hiddenGroup')) {
			metaData.tdCls += metaData.tdCls || '';
			metaData.tdCls += ' ' + Ext.baseCSSPrefix + 'hidden';
		}
	}

	/**
	 * Overridden to add fields 'hidden' and 'hiddenGroup' to the tree nodes model.
	 *
	 * @inheritDoc
	 */
	,createTreeStore: function() {
		var store = this.callParent(arguments),
			model = store.model,
			fields = model.getFields();

		fields.push('hidden', 'hiddenGroup');
		model.setFields(fields);

		return store;
	}

	/**
	 * Hide or show the specified tab.
	 *
	 * @param {Ext.Component} component The component associated with the tab to affect.
	 * @param {Boolean} hidden
	 * @param {Boolean} [suppressEvent=false] True to prevent {@link #tabvisibilitychange} event from firing
	 */
	,setTabHidden: function(component, hidden, suppressEvent) {
		var node = this.store.getNodeById(component.id);
		if (node) {
			node.set('hidden', !!hidden);

			// fire event
			if (!suppressEvent) {
				this.fireEvent('tabvisibilitychange', this, component, !hidden);
			}
		}
	}

	/**
	 * Hide or show the given group.
	 *
	 * @param {Ext.Component} component The root component associated with the group to affect.
	 * @param {Boolean} hidden
	 * @param {Boolean} [suppressEvent=false] True to prevent {@link #groupvisibilitychange} event from firing
	 */
	,setGroupHidden: function(component, hidden, suppressEvent) {
		var node = this.store.getNodeById(component.id);
		if (node) {
			// cast to bool
			hidden = !!hidden;
			node.set('hiddenGroup', hidden);
			node.eachChild(function(childNode) {
				childNode.set('hiddenGroup', hidden);
			});

			// fire event
			if (!suppressEvent) {
				this.fireEvent('groupvisibilitychange', this, component, !hidden);
			}
		}
	}

	/**
	 * Makes the given tab (or group tab) active, **and** updates the component UI, as opposed
	 * to {@link #setActiveTab}, which does not.
	 *
	 * @param {Ext.Component} component The component associated with the tab make active.
	 *
	 * @class {Ext.ux.GroupTabPanel}
	 */
	,selectTab: function(component) {
		var node = this.store.getNodeById(component.id),
			selModel = this.down('treeview').getSelectionModel();
		if (node) {
			//noinspection JSUnresolvedFunction
			this.onNodeSelect(selModel, node);
		}
	}
});

})();
