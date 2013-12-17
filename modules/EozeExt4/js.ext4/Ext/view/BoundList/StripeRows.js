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
 * Adds {@link #stripeRows} option to {@link Ext.view.BoundList}.
 *
 * @since 2013-09-04 16:13
 */
Ext.define('Eoze.Ext.view.BoundList.StripeRows', {
	override: 'Ext.view.BoundList'

	/**
	 * If true, each odd items will be applied an extra {@link #altRowCls CSS class}.
	 *
	 * @cfg {Boolean}
	 */
	,stripeRows: false

	/**
	 * CSS class for {@link #stripeRows striped rows}.
	 * @cfg {Boolean}
	 */
	,altRowCls: 'eo-boundlist-item-alt'

	,initComponent: function() {

		if (this.stripeRows && !this.tpl) {
			var me = this,
				itemCls = me.itemCls,
				altRowCls = this.altRowCls;

			me.tpl = new Ext.XTemplate(
				'<ul class="' + Ext.plainListCls + '"><tpl for=".">',
					'<li role="option" unselectable="on" class="' + itemCls + ' {[xindex % 2 === 0 ? "' + altRowCls + '" : ""]}">' + me.getInnerTpl(me.displayField) + '</li>',
				'</tpl></ul>'
			);
		}

		this.callParent(arguments);
	}
//	,getInnerTpl: function(displayField) {
//		if (this.stripeRows) {
//			var altRowCls = Ext.baseCSSPrefix + 'grid-row-alt';
//			return '<div cls="{[xindex % 2 === 0 ? "' + altRowCls + '" : ""]}">{' + displayField + '}</div>';
//		} else {
//			return this.callParent(arguments);
//		}
//	}
});
})(window.Ext4 || Ext.getVersion && Ext);
