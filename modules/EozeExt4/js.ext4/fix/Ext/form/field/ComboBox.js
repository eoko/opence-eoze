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
 *
 * @since 2013-09-06 15:53
 */
Ext.define('Eoze.fix.Ext.form.field.ComboBox', {
	override: 'Ext.form.field.ComboBox'

	// Fixes combo ping pong bug, when tabbing from one combo to another triggers
	// an infinite focus loop between the two.
	,triggerBlur: function() {
		if (Ext.isWebKit) {
			this.inputFocusTask.cancel();
		}
		this.callParent(arguments);
	}

});
})(window.Ext4 || Ext.getVersion && Ext);
