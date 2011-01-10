/*
 * @author Éric Ortéga <eric@mail.com>
 */

/**
 * Usage:
 *
 * either with an initialisation function:
 *
 * Note: if the initialisation function has a propotype, it will be overwritten!
 * It is better to use an anonymous function dedicated to this object...
 *
 * <code>
 * Oce.Object.create(function(my) {
 *
 *     // Semi-private members
 *     my.property = ...; // preferred syntax
 *     this.my.property2 = ...;
 *
 *     my.method = function() { ... }
 *
 *     // Private members
 *     var privateProperty = ...;
 *     var privateMethod = function() { ... }
 *
 *     // Public methods
 *     this.publicMethod = function() { ... }
 * })
 * </code>
 *
 * or with an initialisation object:
 * <code>
 * Oce.Object.create({
 *
 *     my: {
 *          // New or overriden semi-private property
 *	        semiPrivateProperty1: ...
 *	       ,semiPrivateProperty2: ...
 *     }
 *
 *     // New or overriden public method
 *     ,publicMethod: function() { ... }
 *
 *     // New or overriden public property
 *     ,publicProperty: value
 * })
 * </code>
 *
 * The initialisation with a function is a bit more eficient, since it doesn't
 * need to copy the object's members, or the my object's members.
 */
Oce.Object = function() {

	var constructor = function() {}

    //moduleConstructor.prototype = new Module.constructor()
    constructor.prototype = {

		isA: function(o) {
            var proto = this.$prototype;
            while (proto !== undefined) {
                if (proto === o) return true;
                proto = proto.$prototype;
            }
            return this === o;
        }

		,my: {
			 apply: function(o) {Ext.apply(this, o)}
			,applyIf: function(o) {Ext.applyIf(this, o)}
		}

		,apply: function(o) {Ext.apply(this, o)}
		,applyIf: function(o) {Ext.applyIf(this,o)}

		,clone: function() {this.create({})}

        ,create: function(o) {

			var obj;

			function My() {}
			My.prototype = this.my;

            if (Ext.isObject(o)) {
                var constructor = function(){}
                constructor.prototype = this;
                obj = new constructor();

                obj.$constructor = constructor;
                obj.$prototype = this;

				if ('my' in o) {
					o.my = Ext.apply(new My(), o.my);
				} else {
					o.my = new My();
				}

                Ext.apply(obj, o);

                return obj;
            } else if (Ext.isFunction(o)) {
                o.prototype = this;

				// Allow for direct use of my (without need to copy an object)
                var my = new My();

				// Protect the object hierarchy from accidental overridng of
				// my values (this.my is this object's my)
				var backupMy = this.my;
                this.my = my;

                obj = new o(my);
                obj.my = my;

				this.my = backupMy; // restore this object's my

                obj.$constructor = o;
                obj.$prototype = this;

                return obj;
            } else {
				throw 'IllegalArgument (should be Object or function): ' + o;
			}
        }
    }

    var object = constructor.prototype.create({});
    object.$prototype = undefined;
    object.$constructor = constructor;

    return object;
}();

/* --- Object Tests:

var Module = Oce.Object.create({
})

var MyModule = Module.create({
})

var MyMyModule = MyModule.create({
    my: {
        name: 'MyMyModule :D'
    }
})

var MyModule2 = Module.create(function(){
    this.greats = function() {
        echo("I don't do that, man!")
    }
})

echo('Module.isA(Module): ' + Module.isA(Module));
echo('Module.isA(MyModule): ' + Module.isA(MyModule));
echo('Module.isA(MyMyModule): ' + Module.isA(MyMyModule));
echo('Module.isA(MyModule2): ' + Module.isA(MyModule2));

echo(' ');

echo('MyModule.isA(Module): ' + MyModule.isA(Module));
echo('MyModule.isA(MyModule): ' + MyModule.isA(MyModule));
echo('MyModule.isA(MyMyModule): ' + MyModule.isA(MyMyModule));
echo('MyModule.isA(MyModule2): ' + MyModule.isA(MyModule2));

echo(' ');

echo('MyMyModule.isA(Module): ' + MyMyModule.isA(Module));
echo('MyMyModule.isA(MyModule): ' + MyMyModule.isA(MyModule));
echo('MyMyModule.isA(MyMyModule): ' + MyMyModule.isA(MyMyModule));
echo('MyMyModule.isA(MyModule2): ' + MyMyModule.isA(MyModule2));

echo(' ');

//echo(MyModule2.greats());
echo('MyModule2.isA(Module): ' + MyModule2.isA(Module));
echo('MyModule2.isA(MyModule): ' + MyModule2.isA(MyModule));
echo('MyModule2.isA(MyMyModule): ' + MyModule2.isA(MyMyModule));
echo('MyModule2.isA(MyModule2): ' + MyModule2.isA(MyModule2));

echo(' ');
echo('Module.greats:');
Module.greats()
echo('MyModule.greats:');
MyModule.greats()
echo('MyMyModule.greats:');
MyMyModule.greats()
echo('MyModule2.greats:');
MyModule2.greats();
*/

(function() {

Ext.ns('eo.Object');

var NS = eo.Object;

/**
 * Creates a constructor with the given members as prototype. This method is a
 * syntactic sugar to allow the creation of an Object in only one call, while
 * preserving the efficiency of the prototypical inheritance.
 * @param {Object} members
 * @return the constructor of the new Object
 */
eo.Class = NS.create = function(members) {
	if (members.constructor === Object) members.constructor = function() {};
	var constructor = members.constructor;
	constructor.prototype = members;
	return constructor;
};

/**
 * Extends the given baseObject prototycally.
 * @param {Object} baseConstructor  the consctuctor to extend
 * @param {Object} overrides     the members to add/override
 * @return the constructor of the extended object
 */
NS.extend = function(baseConstructor, overrides) {
	var superclass = baseConstructor.prototype;
	var constructor = overrides.constructor || function() {
		superclass.constructor.apply(this, arguments);
	};
//	var pc = function() {
//		Ext.apply(this, overrides);
//	};

//	pc = function() {};
//	pc.prototype = baseConstructor.prototype;
//	var cp = constructor.prototype = new pc();
//	Ext.apply(cp, overrides);
//	constructor.superclass = baseConstructor.prototype;
//	return constructor;

	if (Ext.isFunction(overrides)) {
		var op = overrides.prototype;
		overrides.prototype = baseConstructor.prototype;
		constructor.prototype = new overrides();
		overrides.prototype = op; // restore overrides prototype
	} else {
		var pc = function() {};
		pc.prototype = baseConstructor.prototype;
		var cp = constructor.prototype = new pc();
		Ext.apply(cp, overrides);
	}

	constructor.superclass = superclass;
	
	return constructor;
};
	
})(); // eo.Object closure

eo.createIf = function(o) {
	if (o instanceof Ext.Observable()) {
		return o;
	} else {
		return Ext.create(o);
	}
}

eo.Text = {
	get: function(src, type) {

		if (src === undefined || src === null) return undefined;
		if (Ext.isString(src)) return src;

		if (type) {
			var s;
			if (Ext.isArray(type)) {
				for (var i=0,len=type.length; i<len; i++) {
					s = src[type[i]];
					if (s) return s;
				}
			} else {
				s = src[type];
				if (s) return s;
			}
		}

		return src['default'];
	}
};

Function.prototype.createRetry = function(scope, args) {
	var fn = this;
	return function() {
		fn.apply(scope, args);
	}
}

eo.findBy = function(arr, testFn) {
	var r = [];
	for (var i=0,l=arr.length; i<l; i++) {
		var e = arr[i];
		if (testFn(e)) r.push(e);
	}
	return r;
}

eo.hashToArray = function(hash, keyIndex, checkConflicts) {
	checkConflicts = checkConflicts !== false;
	if (Ext.isArray(hash)) {
		return hash;
	} else if (Ext.isObject(hash)) {
		var r = [];
		Ext.iterate(hash, function(k,v) {
			v = Ext.apply({}, v);
			if (checkConflicts && v[keyIndex] !== undefined && v[keyIndex] !== k) {
				throw new Error('Hash index conflicts with object property: ' + keyIndex);
			}
			v[keyIndex] = k;
			r.push(v);
		});
		return r;
	} else {
		throw new Error();
	}
};

eo.arrayToHash = function(arr, keyIndex) {
	if (Ext.isObject(arr)) return arr;
	var r = {};
	Ext.each(arr, function(e) {
		r[e[keyIndex]] = e;
	});
	return r;
};

eo.addAspect = function() {
	for (var i=0,l=arguments.length; i<l; i++) {

	}
}

eo.extendMultiple = function() {
	var r = Array.prototype.shift(arguments);
	for (var i=0, l=arguments.length; i<l; i++) {
		r = Ext.extend(r, arguments[i]);
	}
	return r;
}

/**
 * Makes the specified methods of the given class static, by copying them from
 * the class' prototype to the class Function itself.
 * @param {Function} clazz	constructor of the class
 * @param {Array|String}	members
 * @return {Function}		the class constructor
 */
eo.makeStatic = function(clazz, members) {
	var run = function(method) {
		clazz[method] = clazz.prototype[method];
	};
	if (Ext.isArray(members)) Ext.each(members, run);
	else if (Ext.isString(members)) run(members);
	else throw new Error("Invalid argument: must be Array or String");
	return clazz;
}
