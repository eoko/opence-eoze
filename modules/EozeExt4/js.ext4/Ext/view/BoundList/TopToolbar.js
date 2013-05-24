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
 * This override is part of {@link Eoze.Ext.form.field.ComboBox.TopToolbar}.
 *
 * @since 2013-05-28 16:39
 */
Ext4.define('Eoze.Ext.view.BoundList.TopToolbar', {
	override: 'Ext.view.BoundList'

	/**
	 * @property {Ext.toolbar.Toolbar} [toolbar=undefined]
	 */

	,resizable: true

	/**
	 * @inheritdoc
	 */
	,initComponent: function() {
		var toolbar = this.toolbar;
		delete this.toolbar;

//		if (toolbar) {
//			toolbar.ownerCt = this;
//			toolbar.ownerLayout = this.getComponentLayout();
//
//			Ext4.applyIf(toolbar, {
//				height: 24
//				,border: false
//			});
//		}
//		this.toolbar = Ext4.create('Opence.Contact.view.TypeFilterToolbar', {
//		this.toolbar = Ext4.create('Ext.toolbar.Toolbar', {
//			id: this.id + '-paging-toolbar',
//			border: false,
//			ownerCt: this,
//			ownerLayout: this.getComponentLayout()
//		});
		this.toolbar = this.createPagingToolbar();

		this.callParent(arguments);
	}

	/**
	 * @inheritdoc
	 */
	,finishRenderChildren: function() {
		var toolbar = this.toolbar;

		if (toolbar) {
			toolbar.finishRender();
		}

		this.callParent(arguments);
	}

	/**
	 * @inheritdoc
	 */
	,getRefItems: function() {
		var toolbar = this.toolbar,
			items = this.callParent(arguments);

		if (toolbar) {
			items.push(toolbar);
		}

		return toolbar;
	}

	/**
	 * @inheritdoc
	 */
	,refresh: function() {
		var toolbar = this.toolbar,
			rendered = this.rendered;

		this.callParent(arguments);

		if (rendered && toolbar && toolbar.rendered && !this.preserveScrollOnRefresh) {
//			this.el.insertFirst(toolbar.el);
			this.el.appendChild(toolbar.el);
		}
	}

	/**
	 * @inheritdoc
	 */
	,onDestroy: function() {
		Ext4.destroyMembers(this, 'toolbar');
		this.callParent(arguments);
	}

}, function() {

	var Ext = Ext4,
		proto = this.prototype,
		tpl = proto.renderTpl;

//	tpl.unshift(
//		'{%',
//			'var me=values.$comp, toolbar=me.toolbar;',
//			'if (toolbar) {',
//				'toolbar.ownerLayout = me.componentLayout;',
//				'Ext.DomHelper.generateMarkup(toolbar.getRenderTree(), out);',
//			'}',
//		'%}'
//	);

	proto.renderTpl = [
		'<div id="{id}-listEl" class="{baseCls}-list-ct ', Ext.dom.Element.unselectableCls, '" style="overflow:auto"></div>',
        '{%',
            'var me=values.$comp, toolbar=me.toolbar;',
            'if (toolbar) {',
//				'alert(1);',
                'toolbar.ownerLayout = me.componentLayout;',
                'Ext.DomHelper.generateMarkup(toolbar.getRenderTree(), out);',
            '}',
        '%}',
//        '{%',
//            'var me=values.$comp, pagingToolbar=me.pagingToolbar;',
//            'if (pagingToolbar) {',
//                'pagingToolbar.ownerLayout = me.componentLayout;',
//                'Ext.DomHelper.generateMarkup(pagingToolbar.getRenderTree(), out);',
//            '}',
//        '%}',
        {
            disableFormats: true
        }
    ];

});
