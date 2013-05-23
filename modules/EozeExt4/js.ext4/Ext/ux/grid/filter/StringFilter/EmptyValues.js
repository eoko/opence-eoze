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
 * This override adds support for filtering empty or non empty values.
 *
 * @since 2013-05-23 17:21
 */
Ext4.define('Eoze.Ext.ux.grid.filter.StringFilter.EmptyValues', {
	override: 'Ext.ux.grid.filter.StringFilter'

	/**
	 * Text for the "accept empty value" menu item.
	 *
	 * @cfg {String}
	 */
	,emptyText: "Vide" // i18n
	/**
	 * Text for the "accept non empty value" menu item.
	 *
	 * @cfg {String}
	 */
	,nonEmptyText: "Non vide" // i18n

	/**
	 * @inheritdoc
	 */
	,init: function() {
		this.callParent(arguments);

		var menu = this.menu,
			fields = this.fields = {};

		menu.add('-');

		['empty', 'nonEmpty'].forEach(function(key) {
			var text = this[key + 'Text'];

			var item = menu.add({
				text: text
				,xtype: 'menucheckitem'
				,filterEmptyValue: key === 'empty'
				,checked: true
				,listeners: {
					scope: this
					,checkchange: this.onCheckChange
				}
			});

			fields[key] = item;
		}, this);
	}

	/**
	 * @inheritdoc
	 */
	,onCheckChange: function() {
		this.setActive(this.isActivatable());
		this.fireEvent('update', this);
	}

	/**
	 * @inheritdoc
	 */
	,isActivatable: function () {
		var key;
		for (key in this.fields) {
			if (this.fields[key].checked) {
				return true;
			}
		}
		return this.callParent(arguments);
	}

	/**
	 * @inheritdoc
	 */
	,getSerialArgs: function() {
		var args = this.callParent(arguments),
			fields = this.fields;
		Ext.apply(args, {
			acceptEmpty: fields.empty.checked
			,acceptNonEmpty: fields.nonEmpty.checked
		});
		return args;
	}

	/**
	 * @inheritdoc
	 */
	,validateRecord: function(record) {
		if (this.callParent(arguments)) {
			var fields = this.fields,
				value = record.get(this.dataIndex),
				empty = value === null || value === undefined;

			// if empty values are filtered out
			if (!fields.empty.checked) {
				if (empty) {
					return false;
				}
			}
			// if non empty values are filtered out
			if (!field.nonEmpty.checked) {
				if (!empty) {
					return false;
				}
			}
			return true;
		} else {
			return false;
		}
	}
});
