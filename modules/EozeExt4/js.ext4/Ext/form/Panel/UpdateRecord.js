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
 * Adds {@link #updateRecord} method, for Ext version prior to 4.2.1.
 *
 * @since 2013-07-17 22:41
 */
Ext.define('Eoze.Ext.form.Panel.UpdateRecord', {
	override: 'Ext.form.Panel'
}, function() {
	var proto = this.prototype;
	// This has been included in Ext from version 4.2.1
	if (!proto.updateRecord) {
		/**
		 * @inheritdoc Ext.form.Basic#updateRecord
		 */
		proto.updateRecord = function() {
			var form = this.getForm();
			return form.updateRecord.apply(form, arguments);
		}
	}
});
})(window.Ext4 || Ext.getVersion && Ext);
