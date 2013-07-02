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
 * @since 2013-06-04 12:07
 */
Ext4.define('Eoze.form.mixin.FormAsField', {
	extend: 'Ext.form.field.Field'

	,isFormField: true

	/**
	 * @inheritdoc
	 */
	,initField: function() {
		var fieldProto = Ext4.form.field.Field.prototype,
			form = this.getForm();

		// init Field mixin
		fieldProto.initField.apply(this, arguments);

		// prevent the fields from getting captured by parent form
		form.getFields().each(function(field) {
			field.isFormField = false;
		});

		// we don't want FormPanel.isDirty to override Field.isDirty
		this.isDirty = fieldProto.isDirty;
	}

	/**
	 * @inheritdoc
	 */
	,getValue: function() {
		var form = this.getForm();
		if (form.isDirty()) {
			return form.getFieldValues(false);
		} else {
			return this.value;
		}
	}

	/**
	 * @inheritdoc
	 */
	,isEqual: Ext4.Object.equals

	/**
	 * @inheritdoc
	 */
	,getModelData: function() {
		var data = {};
		data[this.name] = this.getValue();
		return data;
	}

	/**
	 * @inheritdoc
	 */
	,setValue: function(value) {
		var form = this.getForm();
		// We don't want to have a complete reference, that could get modified later
		// without us being able to detect that it has changed.
		this.value = Ext4.clone(value);

		form.trackResetOnLoad = true;
		form.setValues(value);

		return this;
	}

	/**
	 * @inheritdoc
	 */
	,getErrors: function(value) {
		debugger
	}

});
