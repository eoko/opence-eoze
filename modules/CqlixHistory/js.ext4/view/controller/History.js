(function(Ext) {
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
 * Controller for history entry list.
 *
 * @since 2013-07-25 06:20
 */
Ext.define('Eoze.CqlixHistory.view.controller.History', {
	extend: 'Deft.mvc.ViewController'

	,requires: [
		'Ext.tab.Panel'
	]

	/**
	 * @private
	 */
	,sortDirection: 'DESC'

	,init: function() {
		var view = this.getView(),
			owner = view.up();
		if (owner instanceof Ext.tab.Panel) {
			var toolbar = owner.getTabBar().getToolsToolbar(),
				sortDirection = this.sortDirection,
				tooltips = {
					// i18n
					ASC: "Les plus anciens d'abord &mdash; Cliquez pour inverser"
					// i18n
					,DESC: "Les plus récents d'abord &mdash; Cliquez pour inverser"
				};

			var toggleButton = toolbar.add({
				iconCls: 'oce-comment-ico ' + sortDirection
				,height: 18
				,width: 18
				,scope: this
				,handler: this.onDirectionToggle
				,tooltips: tooltips
				,tooltip: tooltips[sortDirection]
			});

			view.mon(owner, {
				tabchange: function(tabPanel, newCard) {
					toggleButton.setVisible(newCard === view);
				}
			});
		}
	}

	,getToolbarItems: function() {
		var sortDirection = this.sortDirection,
			tooltips = {
				ASC: "Les plus anciens d'abord &mdash; Cliquez pour inverser"
				,DESC: "Les plus récents d'abord &mdash; Cliquez pour inverser"
			};

		return ['->',{
			iconCls: 'oce-comment-ico ' + sortDirection
			,height: 18
			,width: 18
			,scope: this
			,handler: this.onDirectionToggle
			,tooltips: tooltips
			,tooltip: tooltips[sortDirection]
		}]
	}

	/**
	 * @param {Ext.Button} button
	 * @private
	 */
	,onDirectionToggle: function(button) {
		var store = this.getView().getStore(),
			sorters = store.sorters,
			sorter = sorters && sorters.get('date'),
			direction = Ext.String.toggle(this.sortDirection, 'ASC', 'DESC');

		this.sortDirection = direction;

		button.setIconCls('oce-comment-ico ' + direction);
		button.setTooltip(button.tooltips[direction]);

		if (sorter) {
			sorter.setDirection(direction);
			store.sort();
		} else {
			store.sort({
				property: 'date'
				,direction: direction
			});
		}
	}
});
})(window.Ext4 || Ext.getVersion && Ext);
