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
		Ext4.form.field.Field.prototype.initField.apply(this, arguments);

		// prevent the fields from getting captured by parent form
		this.getForm().getFields().each(function(field) {
			field.isFormField = false;
		});
	}

	/**
	 * @inheritdoc
	 */
	,getValue: function() {
		debugger
	}

	/**
	 * @inheritdoc
	 */
	,getModelData: function() {
		var data = {};
		data[this.name] = this.getForm().getFieldValues(false);
		return data;
	}

	/**
	 * @inheritdoc
	 */
	,setValue: function(value) {
		this.getForm().setValues(value);
		return this;
	}

	/**
	 * @inheritdoc
	 */
	,getErrors: function(value) {
		debugger
	}

});
