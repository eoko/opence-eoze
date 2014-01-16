/**
 * Copyright (C) 2012 Eoko
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
 * @copyright Copyright (C) 2012 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

/**
 *
 * @since 2012-12-14 12:25
 */
Ext.define('Ext.ux.grid.filter.AgeFilter', {
	extend: 'Ext.ux.grid.filter.NumericFilter'

	,requires: ['eo.form.AgeField']

	,getSerialArgs : function () {
		var args = this.callParent(arguments);
		Ext.each(args, function(arg) {
			arg.type = 'age';
		});
		return args;
	}

}, function() {

	var p = this.prototype;

	p.fieldCls = eo.form.AgeField;

	p.menuItemCfgs = Ext.apply({
		helpHtml: false
	}, p.menuItemCfgs);
});
