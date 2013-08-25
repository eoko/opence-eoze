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
Ext4.define('Eoze.Ext.ux.grid.filter.Filter.EmptyValues', {
	override: 'Ext.ux.grid.filter.Filter'

	/**
	 * Text for the "accept empty value" menu item.
	 *
	 * @cfg {String}
	 */
	,emptyValueText: "Vide" // i18n
	/**
	 * Text for the "accept non empty value" menu item.
	 *
	 * @cfg {String}
	 */
	,nonEmptyValueText: "Non vide" // i18n

	,constructor: function() {

		var me = this,
			base = me.EmptyValues,
			infra = {};

		'init,isActivatable,getSerialArgs,validateRecord'.split(',').forEach(function(prop) {
			infra[prop] = me[prop];
		});

		// Child classes don't call their superclass methods (sigh), so we have to do that:
		Ext4.apply(this, {
			init: Ext4.Function.createSequence(
				infra.init,
				base.init
			)

			,isActivatable: function() {
				return infra.isActivatable.apply(this, arguments)
					|| base.isActivatable.apply(this, arguments);
			}

			,getSerialArgs: function() {
				return base.getSerialArgs.call(this, infra.getSerialArgs.apply(this, arguments));
			}

			,validateRecord: function() {
				return infra.validateRecord.apply(this, arguments)
					&& base.validateRecord.apply(this, arguments);
			}
		});

		this.callParent(arguments);
	}

	/**
	 * @inheritdoc
	 */
	,onEmptyItemCheckChange: function() {
		if (this.dt) {
			this.dt.delay(this.updateBuffer);
		} else {
			this.fireUpdate();
		}
	}

	,EmptyValues: {
		/**
		 * @inheritdoc
		 */
		init: function() {
			var menu = this.menu,
				fields = this.emptyValueFields = {};

			menu.add('-');

			['empty', 'nonEmpty'].forEach(function(key) {
				var text = this[key + 'ValueText'];

				var item = menu.add({
					text: text
					,xtype: 'menucheckitem'
					,filterEmptyValue: key === 'empty'
					,checked: true
					,listeners: {
						scope: this
						,checkchange: this.onEmptyItemCheckChange
					}
				});

				fields[key] = item;
			}, this);
		}

		/**
		 * @inheritdoc
		 */
		,isActivatable: function () {
			var key;
			for (key in this.emptyValueFields) {
				if (this.emptyValueFields[key].checked) {
					return true;
				}
			}
			return false;
		}

		/**
		 * @inheritdoc
		 */
		,getSerialArgs: function(args) {
			var fields = this.emptyValueFields;
			if (Ext4.isArray(args)) {
				args.push({
					type: 'emptyvalue'
					,acceptEmpty: fields.empty.checked
					,acceptNonEmpty: fields.nonEmpty.checked
				})
			} else {
				Ext4.apply(args, {
					acceptEmpty: fields.empty.checked
					,acceptNonEmpty: fields.nonEmpty.checked
				});
			}
			return args;
		}

		/**
		 * @inheritdoc
		 */
		,validateRecord: function(record) {
			var fields = this.emptyValueFields,
				value = record.get(this.dataIndex),
				empty = value === null || value === undefined;

			// if empty values are filtered out
			if (!fields.empty.checked) {
				if (empty) {
					return false;
				}
			}
			// if non empty values are filtered out
			if (!fields.nonEmpty.checked) {
				if (!empty) {
					return false;
				}
			}
			return true;
		}
	}
});
