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
 * @since 2013-04-20 00:05
 */
describe('Eoze.Ext.data.model.DirtyEvents', function() {

	var Ext = window.Ext4 || Ext;

	var Model, calls, record;

	beforeEach(function() {

		Model = Ext.define(null, {
			extend: 'Ext.data.Model'

			,fields: [
				'id',
				'name',
				{name: 'age', type: 'int'}
			]
		});

		calls = 0;

		record = new Model({
			id: 12
			,name: 'eric'
		});

		record.on('dirtychanged', function() {
			calls++;
		});

		expect(record.dirty).toBe(false);
		expect(calls).toBe(0);
	});

	it('should fire a dirtychanged event when the model first becomes dirty', function() {

		record.set('age', 30);

		expect(record.dirty).toBe(true);
		expect(calls).toBe(1);

		record.set('age', undefined);

		expect(record.dirty).toBe(false);
		expect(calls).toBe(2);
	});

	it('should intercept edit transaction & commit methods', function() {

		record.beginEdit();
		record.set('age', 30);

		expect(record.dirty).toBe(true);
		expect(calls).toBe(1);

		record.cancelEdit();

		expect(record.dirty).toBe(false);
		expect(calls).toBe(2);

		record.beginEdit();
		record.set('age', 31);

		expect(record.dirty).toBe(true);
		expect(calls).toBe(3);

		record.endEdit();

		expect(record.dirty).toBe(true);
		expect(calls).toBe(3);

		record.commit();

		expect(record.dirty).toBe(false);
		expect(calls).toBe(4);
	});

	it('setFieldDirty should fire the dirtychanged event if the record became dirty', function() {

		record.setFieldDirty('name', true);

		expect(record.dirty).toBe(true);
		expect(calls).toBe(1);

		record.setFieldDirty('name', false);

		expect(record.dirty).toBe(false);
		expect(calls).toBe(2);
	});

	it('setFieldDirty should fire the dirtychanged event if the record became unmodified', function() {

		record.set('name', 'bob');

		expect(record.dirty).toBe(true);
		expect(calls).toBe(1);

		record.setFieldDirty('name', false);

		expect(record.dirty).toBe(false);
		expect(calls).toBe(2);
	});

	it('setFieldDirty should not fire any event if the dirty state remains the same', function() {

		record.setFieldDirty('name', false);

		expect(record.dirty).toBe(false);
		expect(calls).toBe(0);

		record.setFieldDirty('name', true);

		expect(record.dirty).toBe(true);
		expect(calls).toBe(1);

		record.setFieldDirty('id', true);

		expect(record.dirty).toBe(true);
		expect(calls).toBe(1);
	});
});
