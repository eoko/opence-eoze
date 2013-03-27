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
 * A mixin that handles {@link Eoze.modules.AjaxRouter.AjaxRouter ajax routing} for tabbed views.
 *
 * The view component associated to the controller must expose a {@link Ext4.tab.Panel#getActiveTab
 * getActiveTab} method, that returns the currently active tab's component, and a
 * {@link Ext4.tab.Panel#tabchange} event.
 *
 * @since 2013-03-27 10:29
 */
Ext4.define('Eoze.mvc.controller.mixin.HasAjaxRouterTabs', {
	extend: 'Eoze.mvc.ControllerMixin'

	,requires: ['Eoze.modules.AjaxRouter.AjaxRouter']

	/**
	 * @inheritdoc
	 */
	,init: function(controller) {
		this.callParent(arguments);

		var view = controller.getView();

		if (!view.getActiveTab) {
			throw new Error(
				'View must expose getActiveTab method, and tabchange event to be compatible with ControllerMixin.'
			);
		}

		// Register event
		view.on('tabchange', this.onTabChange, this);
		// Init
		this.onTabChange(view, view.getActiveTab());
	}

	/**
	 * Handler for tab change event.
	 *
	 * @param {Ext4.Component} panel
	 * @param {Ext4.Component} activeTab
	 *
	 * @private
	 */
	,onTabChange: function(panel, activeTab) {
		var win = this.getController().getWindow(),
			slug = activeTab.slug;
		if (win) {
			if (slug === false) {
				delete win.hrefRoute.tab;
			} else {
				win.hrefRoute.tab = slug;
			}
			Eoze.modules.AjaxRouter.AjaxRouter.setActivePage(win);
		}
	}

});
