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
 * @since 2013-03-29 16:06
 */
describe('Eoze.Deft.ioc.Injector', function() {

	var Ext = Ext4,
		constructed;

	beforeEach(function() {

		constructed = false;

		Ext.define('Foo.Injection', {
			extend: 'Ext.util.Observable'
			,constructor: function() {
				this.callParent(arguments);
				this.addEvents('myevent');
				constructed = true;
			}
		});

		Deft.Injector.configure({
			foo: {
				className: 'Foo.Injection'
				,getter: false
			}
			,lazyFoo: {
				className: 'Foo.Injection'
				,getter: true
			}
			,lazyFooWithEmptyValue: {
				className: 'Foo.Injection'
				,getter: true
				,emptyValue: null
			}
		});
	});

	it('should not break constructor injection', function() {

		expect(constructed).toBe(false);

		Ext.define('Foo.Injected', {
			inject: ['foo']
		});

		var foo = Ext.create('Foo.Injected');

		expect(constructed).toBe(true);
		expect(foo.foo.$className).toBe('Foo.Injection');
	});

	it('should inject with a getter method', function() {

		expect(constructed).toBe(false);

		Ext.define('Foo.Injected', {
			inject: {
				foo: 'lazyFoo'
			}
		});

		var foo = Ext.create('Foo.Injected');

		expect(constructed).toBe(false);
		expect(foo.getFoo().$className).toBe('Foo.Injection');
		expect(constructed).toBe(true);
	});

	it('should work when the dependent class already has a getter (or a config option)', function() {

		expect(constructed).toBe(false);

		Ext.define('Foo.Injected', {
			inject: {
				foo: 'lazyFoo'
			}
			,config: {
				foo: undefined
			}
		});

		var foo = Ext.create('Foo.Injected');

		expect(constructed).toBe(false);
		expect(foo.getFoo().$className).toBe('Foo.Injection');
		expect(constructed).toBe(true);
	});

	it('should use the configured emptyValue', function() {

		expect(constructed).toBe(false);

		Ext.define('Foo.Injected', {
			inject: {
				foo: 'lazyFooWithEmptyValue'
			}
			,config: {
				foo: null
			}
		});

		var foo = Ext.create('Foo.Injected');

		expect(constructed).toBe(false);
		expect(foo.getFoo().$className).toBe('Foo.Injection');
		expect(constructed).toBe(true);
	});

	it('should preserve ViewController observe property with standard injection', function() {

		var observed = false;

		Ext.define('Foo.Controller', {
			extend: 'Deft.mvc.ViewController'
			,inject: {
				foo: 'foo'
			}
			,observe: {
				foo: {
					myevent: 'onMyEvent'
				}
			}
			,onMyEvent: function() {
				observed = true;
			}
		});

		Ext.define('Foo.View', {
			extend: 'Ext.Window'
			,controller: 'Foo.Controller'
		});

		var win = Ext.create('Foo.View');

		expect(observed).toBe(false);
		expect(constructed).toBe(true);

		var foo = win.getController().foo;
		foo.fireEvent('myevent', foo);
		expect(observed).toBe(true);
	});

	it('should preserve ViewController observe property with getter injection', function() {

		var observed = false;

		Ext.define('Foo.Controller', {
			extend: 'Deft.mvc.ViewController'
			,inject: {
				foo: 'lazyFoo'
			}
			,config: {foo: undefined}
			,observe: {
				foo: {
					myevent: 'onMyEvent'
				}
			}
			,onMyEvent: function() {
				observed = true;
			}
		});

		Ext.define('Foo.View', {
			extend: 'Ext.Window'
			,controller: 'Foo.Controller'
		});

		var win = Ext.create('Foo.View');

		expect(observed).toBe(false);
//		expect(constructed).toBe(true);

		var foo = win.getController().getFoo();
		foo.fireEvent('myevent', foo);
		expect(observed).toBe(true);
	});

});
