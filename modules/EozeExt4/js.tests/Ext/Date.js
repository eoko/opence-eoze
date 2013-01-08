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
 * @since 2012-11-28 16:28
 */
describe('Eoze.Ext.Date', function () {

	it('isEqual should not be broken', function () {

		var xDate = Ext4.Date,
			format = 'Y-m-d H:i';

		var d = xDate.parseDate('1982-08-30 18:00', format),
			d2 = xDate.parseDate('1982-08-30 18:00', format),
			d3 = xDate.parseDate('1982-08-30 19:00', format);

		expect(xDate.isEqual(d, d2)).toBeTruthy();
		expect(xDate.isEqual(d, d3)).toBeFalsy();
	});

	it('isEqual should respect ignoreTime option', function () {

		var xDate = Ext4.Date,
			format = 'Y-m-d H:i';

		var d = xDate.parseDate('1982-08-30 18:00', format),
			d2 = xDate.parseDate('1982-08-30 19:00', format);

		expect(xDate.isEqual(d, d2)).toBeFalsy();

		d.ignoreTime = true;
		d2.ignoreTime = true;

		expect(xDate.isEqual(d, d2)).toBeTruthy();
	});
});
