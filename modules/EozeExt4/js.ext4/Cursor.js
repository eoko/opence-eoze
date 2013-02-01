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
 * @since 2013-02-01 01:57
 */
Ext4.define('Eoze.Cursor', function() {

	var defaultTimeout = 10000,
		cls = 'eo-cursor-wait',
		latch = 0;

	function release() {
		if (--latch === 0) {
			Ext4.getBody().removeCls(cls);
		}
	}

	return {
		singleton: true
		,defaultTimeout: 10000
		,wait:function (timeout) {
			Ext4.getBody().addCls(cls);

			latch++;

			var released = false;
			function done() {
				if (!released) {
					released = true;
					release();
				}
			}

			if (timeout !== false) {
				Ext4.Function.defer(done, timeout || Eoze.Cursor.defaultTimeout);
			}

			return done;
		}
	}
});
