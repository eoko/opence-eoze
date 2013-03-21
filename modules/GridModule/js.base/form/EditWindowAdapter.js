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
 * @since 2013-03-20 10:07
 */
Ext4.define('Eoze.GridModule.form.EditWindowAdapter', {

	constructor: function() {
		this.windows = {};
	}

	,createWindow: function(operation) {
		return this.module.createEditWindow(operation);
	}

	,showWindow: function(operation) {
		var win = operation.getWindow(),
			sourceEl = operation.getSourceEl();

		win.show(sourceEl);

		return operation;
	}

	,loadWindow: function(operation) {
		var win = operation.getWindow(),
			record = operation.getRecord(),
			startTab = operation.getStartTab();

		if (win.hasBeenLoaded) {
			operation.notifyReady();
		} else {
			if (record) {
				win.setRow(operation.getRecord());

				// 2011-12-15 05:56 added form.record for opence's season module
				// 2013-03-19 13:50 (this snippet was after win.form.reset() and win.show -- see bellow)
				var form = win.formPanel.form;
				if (form) {
					form.record = record;
				}
			} else {
				win.setRowId(operation.getRecordId());
			}

			win.form.reset();

			// 2013-03-19 13:50
			// var form = win.formPanel.form [...] was here

			win.formPanel.load(function() {
				var first = !win.hasBeenLoaded;
				win.hasBeenLoaded = true;
				operation.notifyLoaded(win, first);
			});
		}

		if (startTab) {
			win.setTab(startTab);
		}
	}
});
