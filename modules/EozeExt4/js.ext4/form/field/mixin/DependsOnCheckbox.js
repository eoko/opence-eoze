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
 * Behavioral plugin that disables the affected field when a source checkbox is not checked (and enables it
 * when the checkbox is checked).
 *
 * @since 2013-02-15 01:01
 */
Ext4.define('Eoze.form.field.mixin.DependsOnCheckbox', {

	alias: ['plugin.dependsOnCheckbox']

	/**
	 * @param {Object} config
	 * @param {String} config.source Field name or itemId (if source begins with a '#') of the source checkbox
	 */
	,constructor: function(config) {
		Ext.apply(this, config);
	}

	/**
	 * @param {Ext.form.field.Base} field
	 */
	,init: function(field) {
		field.on('afterrender', this.initEvents, this, {single: true});
	}

	/**
	 * @private
	 */
	,initEvents: function(field) {
		var form = field.up('form'),
			source = this.source,
			checkbox = form && source
				&& (source.indexOf('#') !== -1 ? form.down(source) : form.findField(source));
		if (checkbox) {
			checkbox.on('change', function(checkbox) {
				field.setDisabled(!checkbox.checked);
			});
			field.setDisabled(!checkbox.checked);
		}
	}
});
