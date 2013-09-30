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
 *
 * @since 2013-02-13 13:18
 */
(function () {

	var spp = Ext.form.ComboBox.prototype,
		uber = spp.initList;

	/**
	 * @class Ext.form.Combo
	 */
	Ext.apply(spp, {

		/**
		 * @cfg {Boolean}
		 * True to automatically hide the {@link #pageSize page toolbar} when there is only
		 * one page of items.
		 */
		autoHidePageToolbar: true

		// Adds hook for autoHidePageToolbar
		,initList: function () {
			uber.apply(this, arguments);
			var tb = this.pageTb;
			if (this.autoHidePageToolbar && tb) {
				this.footer.setDisplayed(false);
				tb.on('change', function (tb) {
					this.setFooterVisible(tb.getPageData().pages > 1);
				}, this);
			}
		}

		/**
		 * Show or hide the list's footer, and adjust the list height accordingly.
		 *
		 * @param {Boolean} visible
		 * @private
		 */
		,setFooterVisible:function (visible) {
			var footer = this.footer;
			if (footer) {
				footer.setDisplayed(!!visible);
				// Update list height
				if(this.title || this.pageSize){
					this.assetHeight = 0;
					if(this.title){
						this.assetHeight += this.header.getHeight();
					}
					if(this.pageSize){
						this.assetHeight += this.footer.getHeight();
					}
				}
				this.restrictHeight();
			}
		}
	});

})(); // closure
