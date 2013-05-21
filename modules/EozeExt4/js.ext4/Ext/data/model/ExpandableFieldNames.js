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
 * This overrides allows using dotted notation to access associated models.
 *
 * In details, this override affects the following methods:
 *
 * - {@link Ext.data.Model#get} can be used to access associated models value with dotted notation;
 *
 * - {@link Ext.data.Model#fields}'s {@link Ext.util.MixedCollection#get} method is overridden to retrieve
 *   associated models fields when dotted notation is used.
 *
 * @since 2013-05-06 15:01
 */
Ext4.define('Eoze.Ext.data.model.ExpandableFieldNames', {
	override: 'Ext.data.Model'

	/**
	 * Regular expression used to split field names to enable expandable field names.
	 *
	 * @var {RegExp}
	 * @private
	 */
	,fieldSplitRe: /\[(\d+)\]\.|\./

	/**
	 * @inheritdoc
	 *
	 * This method is overridden to allow using dotted notation to access associated models data.
	 */
	,get: function(fieldName) {
		var data = this[this.persistenceProperty],
			nodes = fieldName.split(this.fieldSplitRe),
			value = data;
		if (nodes.length > 1) {
			Ext.each(nodes, function(node) {
				if (value) {
					if (node !== undefined) {
						value = value[node];
					}
				} else {
					return false;
				}
			});
		} else {
			value = data[fieldName];
		}
		return value;
	}

//	,set: function(fieldName, newValue) {
//		var nodes = fieldName.split(this.fieldSplitRe),
//			node;
//		if (nodes.length > 1) {
//			node = this[this.persistenceProperty];
//			Ext.each(nodes, function(part) {
//				if (value) {
//					if (node !== undefined) {
//						node = node[part];
//					}
//				} else {
//					return false;
//				}
//			});
//		} else {
//			return this.callParent(arguments);
//		}
//	}
}, function() {

	// regex to filter out square brackets [] in field name
	var cleanRe = /^[^[]+/;

	Ext4.ModelManager.onModelDefined = Ext4.Function.createSequence(Ext4.ModelManager.onModelDefined, function(model) {
		var proto = model.prototype,
			fields = proto.fields,
			uber = fields.get;

		fields.get = function(key) {
			if (Ext4.isString(key) && key.indexOf('.') !== -1) {
				var nodes = key.split('.'),
					first = cleanRe.exec(nodes.shift())[0],
					targetField = nodes.join('.'),
					field = fields.get(first),
					targetModel = field.model,
					targetRecordClass;
				if (targetModel) {
					targetRecordClass = Ext4.isString(targetModel)
						? Ext4.ClassManager.get(targetModel)
						: targetModel;
					if (targetRecordClass) {
						return targetRecordClass.prototype.fields.get(targetField);
					} else {
						throw new Error('Cannot find model class: ' + targetModel);
					}
				}
			}

			// defaults to the parent's method
			return uber.apply(this, arguments);
		}
	});
});
