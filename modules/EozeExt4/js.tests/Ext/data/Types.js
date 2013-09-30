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
 * @since 2012-11-28 16:40
 */
describe('Eoze.Ext.data.Types', function (Ext) {

	Ext = window.Ext4 || window.Ext;

	it('DAYDATE type should exist', function () {
		expect(Ext.data.Types.DAYDATE).toBeDefined();
	});

	it('should add daydate field type', function () {
		var Model = Ext4.define(null, {
			extend: 'Ext.data.Model'
			,fields: [
				'id'
				,{name: 'test', type: Ext4.data.Types.DAYDATE}
				,{name: 'test2', type: 'daydate'}
			]
		});

		var fields = Model.prototype.fields;

		expect(fields.get('id').convert).not.toBe(Ext4.data.Types.DAYDATE.convert);
		expect(fields.get('test').convert).toBe(Ext4.data.Types.DAYDATE.convert);
		expect(fields.get('test2').convert).toBe(Ext4.data.Types.DAYDATE.convert);
	});

	it('DAYDATE should convert to Date with ignoreTime option', function () {
		var Model = Ext4.define(null, {
			extend: 'Ext.data.Model'
			,fields: [
				'id'
				,{name: 'test', type: Ext4.data.Types.DAYDATE}
				,{name: 'test2', type: 'daydate'}
			]
		});

		var o = Ext4.create(Model, {
			test: '1982-08-30'
			,test2: null
		});

		expect(o.get('test').ignoreTime).toBe(true);
		expect(o.get('test2')).toBe(null);

		o.set('test2', '1980-07-22');
		expect(o.get('test2').ignoreTime).toBe(true);
	});
});
