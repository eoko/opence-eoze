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
 * @since 2013-03-26 09:49
 */
Ext4.onReady(function() {

	Ext4.define('Eoze.locale.fr.form.Labelable', {
		override: 'Ext.form.Labellable'

		,labelSeparator: '&nbsp;:'

	//	,trimLabelSeparator: function() {
	//		this.callParent(arguments);
	//	}
	});

	Ext4.define('Ext4.locale.fr.picker.Date', {
		override: 'Ext4.picker.Date'
		,format: 'd/m/Y'
	});

	Ext4.define('Ext4.locale.fr.form.field.Date', {
		override: 'Ext4.form.field.Date'
		,format: 'd/m/Y'
		,altFormats: 'j/n/Y|j/n/y|j/n|j n Y|j n y|j n|Y-m-d|Y n j'
	});

	Ext4.define('Ext4.locale.fr.form.field.Time', {
		override: 'Ext4.form.field.Time'
	//	,format: 'H:i'
	//	,altFormats: 'g:ia|g:iA|g:i a|g:i A|h:i|g:i|H:i|ga|h a|g a|g A|gi|hi|Hi|gia|hia|g|H'
		,altFormats: 'G:i|G:i|G i|Gi|G|G:i:s|G i s'
	});

}); // onReady
