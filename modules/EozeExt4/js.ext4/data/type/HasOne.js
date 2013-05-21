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
 * @since 2013-04-19 17:08
 */
(function(Ext) {

	var prefix = 'Eoze.data.type.HasOne#',
		dirtyChangedHandler = prefix + 'onDirtyChanged',
		instanceSuffix = 'HasOneRecord';

	function onDirtyChanged(parentRecord, fieldName, dirty) {
		parentRecord.setFieldDirty(fieldName, dirty);
	}

	function attachChildRecord(parentRecord, record, fieldName) {
		var instanceName = fieldName + instanceSuffix;
		parentRecord[instanceName] = record;

		var handler = function(record, dirty) {
			return onDirtyChanged(parentRecord, fieldName, dirty);
		};

		record[dirtyChangedHandler] = handler;

		record.on('dirtychanged', handler, record);
	}

	function detachChildRecord(parentRecord, record, fieldName) {
		var instanceName = fieldName + instanceSuffix,
			handler = record[dirtyChangedHandler];

		// Detach record
		delete parentRecord[instanceName];

		// Detach event
		record.un('dirtychanged', handler, record);
	}

	/**
	 * This field type represents a "has one" relation.
	 *
	 * It accepts inputs from init values, setters, as well as data read from the proxy. In contrast
	 * with {@link Ext.data.association.HasOne}, it will not try to load the referred record when
	 * the data are already provided.
	 *
	 * Raw data can be accessed with the standard {@link Ext.data.Model#get} and
	 * {@link Ext.data.Model#set} methods.
	 *
	 * A getter and a setter will also be generated, based on the name of the field (e.g. getMyField).
	 * The getter will return an object of the configured `model` class.
	 *
	 *
	 * Configuration
	 * -------------
	 *
	 * The field must be configured with a `model`.
	 *
	 * Example:
	 *
	 *     Ext.define('Parent', {
	 *         extend: 'Ext.data.Model'
	 *
	 *         ,fields: [
	 *             'id',
	 *             {name: 'myChild', type: 'hasOne', model: 'Child'}
	 *         ]
	 *     });
	 */
	Ext.define('Eoze.data.type.HasOne', {

		singleton: true

		,type: 'hasOne'

		/**
		 * @cfg {String} model
		 */

		,convert: function() {

			function createTargetRecord(value, parentRecord) {

				var targetModel = this.model,
					targetRecordClass = Ext.isString(targetModel)
						? Ext.ClassManager.get(targetModel)
						: targetModel;

				var record;

				if (value instanceof targetRecordClass) {
					record = value;
				} else if (Ext.isObject(value)) {
					record = new targetRecordClass(value);
				}

				// Return
				return record;
			}

			return function(value, record) {

				var fieldName = this.name,
					instanceName = fieldName + instanceSuffix;

				var childRecord = record[instanceName];

				if (childRecord) {
					if (Ext.isEmpty(value)) {
						detachChildRecord(record, childRecord, fieldName);
						return null;
					} else {
						childRecord.set(value);

						// If id was in the data, we need to update phantom status
						if (childRecord.modified.hasOwnProperty(childRecord.idProperty)) {
							childRecord.setId(childRecord.getId());
						}
					}
				} else {
					if (Ext.isEmpty(value)) {
						return null;
					} else {
						childRecord = createTargetRecord.call(this, value, record);
						attachChildRecord(record, childRecord, this.name);
					}
				}

				return childRecord[childRecord.persistenceProperty];
			};
		}()
	}, function() {

		var type = this.type,
			upperCaseType = type.toUpperCase();

		// --- Register data type

		Ext.data.Types[upperCaseType] = this;

		// ---

		Ext.data.Model.override({
			copyFrom: function(sourceRecord) {
				if (sourceRecord) {

					var me = this,
						fields = me.fields.items,
						fieldCount = fields.length,
						field, i = 0,
						myData = me[me.persistenceProperty],
						sourceData = sourceRecord[sourceRecord.persistenceProperty],
						value;

					for (; i < fieldCount; i++) {
						field = fields[i];

						// Do not use setters.
						// Copy returned values in directly from the data object.
						// Converters have already been called because new Records
						// have been created to copy from.
						// This is a direct record-to-record value copy operation.
						value = sourceData[field.name];

						if (field.model) {
							value = field.convert(value, this);
						}

						if (value !== undefined) {
							myData[field.name] = value;
						}
					}

					// If this is a phantom record being updated from a concrete record, copy the ID in.
					if (me.phantom && !sourceRecord.phantom) {
						me.setId(sourceRecord.getId());
					}
				}
			}
		});

		// --- Getter & setter generation

		function generateAccessors(proto) {
			proto.fields.each(function(field) {
				if (field.type && field.type.type === type) {
					var fieldName = field.name,
						instanceName = fieldName + instanceSuffix,
						associateName = fieldName.charAt(0).toUpperCase() + fieldName.slice(1),
						getterName = 'get' + associateName,
						setterName = 'set' + associateName,
						previousGetter = proto[getterName],
						previousSetter = proto[setterName];

					function getter() {
						return this[instanceName];
					}

					function setter(newRecord) {
						var existingRecord = this[instanceName];

						if (existingRecord === newRecord) {
							return;
						}

						if (existingRecord) {
							detachChildRecord(this, existingRecord, fieldName);
						}

						if (newRecord) {
							attachChildRecord(this, newRecord, fieldName);
						}

						onDirtyChanged(this, fieldName, true);
					}

					if (previousGetter) {
						previousGetter.$previous = getter;
					} else {
						proto[getterName] = getter;
					}

					if (previousSetter) {
						previousSetter.$previous = setter;
					} else {
						proto[setterName] = setter;
					}
				}
			});
		}

		function removeAccessors(proto) {
			proto.fields.each(function(field) {
				if (field.type && field.type.type === type) {
					var fieldName = field.name,
						associateName = fieldName.charAt(0).toUpperCase() + fieldName.slice(1),
						getterName = 'get' + associateName,
						setterName = 'set' + associateName;

					delete proto[getterName];
					delete proto[setterName];
				}
			});
		}

		Ext.ModelManager.onModelDefined = Ext.Function.createSequence(Ext.ModelManager.onModelDefined, function(model) {
			generateAccessors(model.prototype);
		});

		// Getter & setter generation on fields modification

		var Model = Ext.data.Model,
			setFields = Model.setFields;

		Model.setFields = function() {

			var proto = this.prototype;

			removeAccessors(proto);

			var result = setFields.apply(this, arguments);

			generateAccessors(proto);

			return result;
		};
	});

})(window.Ext4 || Ext);
