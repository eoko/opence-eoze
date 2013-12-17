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
 * @since 2013-04-19 17:19
 */
describe('Eoze.data.HasOne', function() {

	var Ext = Ext4 || Ext;

	beforeEach(function() {
		Ext.define('Parent', {
			extend: 'Ext.data.Model'

			,fields: [
				'id',
				{name: 'myChild', type: 'hasOne', model: 'Child'}
			]
		});

		Ext.define('Child', {
			extend: 'Ext.data.Model'

			,fields: [
				'id', 'name', 'age',
				{name: 'myOwnChild', type: 'hasOne', model: 'GrandChild'}
			]
		});

		Ext.define('GrandChild', {
			extend: 'Ext.data.Model'

			,fields: ['id', 'name']
		});
	});

	it('should generate getter and setter methods for has one fields', function() {
		expect(Ext.isFunction(Parent.prototype.getMyChild)).toBe(true);
		expect(Ext.isFunction(Parent.prototype.setMyChild)).toBe(true);
	});

	it('the generated getter should return a model instance', function() {

		var record = new Parent;

		expect(Ext.isEmpty(record.getMyChild())).toBe(true);

		record = new Parent({
			id: 'test'
			,myChild: {
				id: 'rx'
				,name: 'eric'
				,age: 30
			}
		});

		expect(record.getMyChild()).toBe(record.myChildHasOneRecord);
	});

	it('should be used for the "hasOne" field type', function() {

		var field = Parent.prototype.fields.get('myChild'),
			type = field.type;

		expect(type).toBe(Eoze.data.type.HasOne);
	});

	it('should update the child record with initial data', function() {

		var record = new Parent({
			id: 'test'
			,myChild: {
				id: 'rx'
				,name: 'eric'
				,age: 30
			}
		});

		var childRecord = record.getMyChild();

		expect(childRecord.get('id')).toBe('rx');
		expect(childRecord.get('age')).toBe(30);
	});

	it('should update the child record when the field is modified', function() {

		var record = new Parent({
			id: 'test'
			,myChild: {
				id: 'rx'
				,name: 'eric'
				,age: 30
			}
		});

		var childRecord = record.getMyChild();

		expect(childRecord.get('age')).toBe(30);

		record.set('myChild', {age: 25});

		expect(childRecord.get('age')).toBe(25);
	});

	it('should mark the record as dirty when the field is modified', function() {

		var record = new Parent({
			id: 'test'
			,myChild: {
				id: 'rx'
				,name: 'eric'
				,age: 30
			}
		});

		var childRecord = record.getMyChild();

		expect(record.dirty).toBe(false);
		expect(childRecord.dirty).toBe(false);

		record.set('myChild', {age: 30});

		expect(record.dirty).toBe(false);
		expect(childRecord.dirty).toBe(false);

		record.set('myChild', {age: 25});

		expect(record.dirty).toBe(true);
		expect(childRecord.dirty).toBe(true);
	});

	it('should mark the record as dirty when the child record is modified directly', function() {

		var record = new Parent({
			id: 'test'
			,myChild: {
				id: 'rx'
				,name: 'eric'
				,age: 30
			}
		});

		var childRecord = record.getMyChild();

		expect(childRecord.dirty).toBe(false);
		expect(record.dirty).toBe(false);

		childRecord.set({age: 30});

		expect(childRecord.dirty).toBe(false);
		expect(record.dirty).toBe(false);

		childRecord.set({age: 25});

		expect(childRecord.dirty).toBe(true);
		expect(record.dirty).toBe(true);
	});

	it('should update parent record dirty state when a nested record is modified directly', function() {

		var record = new Parent({
			id: 'test'
			,myChild: {
				id: 'rx'
				,myOwnChild: {
					name: 'bob'
				}
			}
		});

		expect(record.getMyChild().get('id')).toBe('rx');
		expect(record.getMyChild().getMyOwnChild().get('name')).toBe('bob');

		var child = record.getMyChild(),
			grandChild = child.getMyOwnChild();

		expect(record.dirty).toBe(false);
		expect(child.dirty).toBe(false);
		expect(grandChild.dirty).toBe(false);

		grandChild.set('name', 'jean');

		expect(record.dirty).toBe(true);
		expect(child.dirty).toBe(true);
		expect(grandChild.dirty).toBe(true);
	});

	it('should detach previous child record when the setter is used with another model instance', function() {

		var record = new Parent({
			id: 42,
			myChild: {id: 'rx'}
		});

		var myChild = record.getMyChild();

		expect(record.dirty).toBe(false);

		var newChild = new Child({
			id: 'bob'
		});

		record.setMyChild(newChild);

		expect(record.getMyChild()).toBe(newChild);
		expect(record.dirty).toBe(true);

		record.commit();
		expect(record.dirty).toBe(false);

		newChild.set('id', 'john');

		expect(newChild.dirty).toBe(true);
		expect(record.dirty).toBe(true);

		newChild.set('id', 'bob');

		expect(newChild.dirty).toBe(false);
		expect(record.dirty).toBe(false);

		myChild.set('id', 'jean');
		expect(myChild.dirty).toBe(true);
		expect(record.dirty).toBe(false);

		record.set('myChild', {name: 'alice'});
		expect(newChild.get('name')).toBe('alice');
	});

	it('shoud remove getter & setter when the hasOne field is removed with Model#setFields', function() {

		Parent.setFields([
			'id',
			{name: 'myOtherChild', type: 'hasOne', model: 'Child'}
		]);

		expect(Parent.prototype.getMyChild).toBeUndefined();
		expect(Parent.prototype.setMyChild).toBeUndefined();
	});

	it('should generate accessors for new hasOnen fields added through Model#setFields', function() {

		Parent.setFields([
			'id',
			{name: 'myOtherChild', type: 'hasOne', model: 'Child'}
		]);

		expect(Ext.isFunction(Parent.prototype.getMyOtherChild)).toBe(true);
		expect(Ext.isFunction(Parent.prototype.setMyOtherChild)).toBe(true);
	});
});
