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
 * Adds a small toolbar on the right of the tab bar.
 *
 * @since 2013-07-25 06:25
 */
Ext.define('Eoze.Ext.tab.Bar.ToolsToolbar', {
	override: 'Ext.tab.Bar'

	,requires: [
		'Ext.toolbar.Toolbar',
		'Ext.toolbar.Fill'
	]

	/**
	 * Gets the tools toolbar.
	 *
	 * @returns {Ext.toolbar.Toolbar}
	 */
	,getToolsToolbar: function() {
		var toolbar = this.toolsToolbar;

		if (!toolbar) {
			this.add({xtype: 'tbfill'});
			toolbar = this.toolsToolbar = this.add({
				xtype: 'toolbar'
				,border: false
				,style: 'background: none transparent;'
				,maxHeight: 21
				,padding: 0
			});
		}

		return toolbar;
	}
});
})(window.Ext4 || Ext.getVersion && Ext);
