Ext.namespace('Oce');

Oce.isString = function(val) {return typeof val.valueOf() == 'string'}
Oce.isArray = function(val) {return val instanceof Array}
Oce.isBoolean = function(val) {return typeof val.valueOf() == 'boolean'};


Oce.applyReccursivelyIf = function(o, src) {
	Ext.iterate(src, function(k,v) {
		if (k in o) {
			if (Ext.isObject(o[k])) o.applyReccursivelyIf(o[k], v);
		} else {
			o[k] = v;
		}
	});
};

Oce.applyReccursively = function(o, src) {
	Ext.iterate(src, function(k,v) {
		if (k in o) {
			if (Ext.isObject(o[k])) o.applyReccursively(o[k], v);
			else o[k] = v;
		} else {
			o[k] = v;
		}
	});
};

Oce.clone = function(o) {
	var o2 = {}
	Ext.iterate(o, function(k,v) {o2[k] = v})
	return o2;
//	Ext.apply({}, o);
}

Oce.pushListener = function(config, name, fn) {
	if (false === 'listeners' in config) {
		config.listeners = {};
	}
	config.listeners[name] = fn;
}

Oce.pickFirst = function(o, names) {
	if (!Ext.isArray(names)) throw 'IllegalArgument';
	for (var i=0,l=names.length; i<l; i++) {
		if (names[i] in o) return o[names[i]];
	}
	return undefined;
}

Oce.pickInBy = function(o, name) {
	var r = null;
	Ext.iterate(o, function(k,v) {
		if (k === name) r = v;
	})
	return r;
}

Oce.walk = function(o, fn) {
	if (Ext.isArray(o)) {
		for (var i=0,len=o.length; i<len; i++) {
			fn(i, o[i]);
		}
	} else if (Ext.isObject(o)) {
		Ext.iterate(o, fn);
	} else {
		return false;
	}
	return true;
}


//// http://ejohn.org/blog/simple-javascript-inheritance/#postcomment
//// Inspired by base2 and Prototype
//(function(){
//  var initializing = false, fnTest = /xyz/.test(function(){xyz;}) ? /\b$super\b/ : /.*/;
//
//  // The base Class implementation (does nothing)
//  Oce.Class = function(){};
//
//  // Create a new Class that inherits from this class
//  Oce.Class.extend = function(prop) {
//
//	var $super = this.prototype;
//
//    // Instantiate a base class (but only create the instance,
//    // don't run the init constructor)
//    initializing = true;
//    var prototype = new this();
//    initializing = false;
//
//    // Copy the properties over onto the new prototype
//    for (var name in prop) {
//      // Check if we're overwriting an existing function
//      prototype[name] = typeof prop[name] == "function" &&
//        typeof $super[name] == "function" && fnTest.test(prop[name]) ?
//        (function(name, fn){
//          return function() {
//            this.$super = $super[name];
//			return fn.apply(this, arguments);
////            var tmp = this.$super;
////
////            // Add a new ._super() method that is the same method
////            // but on the super-class
////            this.$super = $super[name];
////
////            // The method only need to be bound temporarily, so we
////            // remove it when we're done executing
////            var ret = fn.apply(this, arguments);
////            this.$super = tmp;
////
////            return ret;
//          };
//        })(name, prop[name]) :
//        prop[name];
//    }
//
//    // The dummy class constructor
//    function F() {
//      // All construction is actually done in the init method
//      if (!initializing && this.constructor)
//        this.constructor.apply(this, arguments);
//    }
//
//    // Populate our constructed prototype object
//    F.prototype = prototype;
//
//    // Enforce the constructor to be what we expect
//    F.constructor = F;
//
//    // And make this class extendable
//    F.extend = arguments.callee;
//
//    return F;
//  };
//})();
//

Ext.ns('Oce.util.JSON');
Oce.util.JSON.encode = function(value, encode) {
	var json = Ext.util.JSON.encode(value);
	if (encode) {
		json = encodeURIComponent(json);
	}
	return json;
}
Oce.JSON = Oce.util.JSON;

Oce.util.ArrayEx = function() {
	for (var i=0,l=arguments.length; i<l; i++) {
		this.push(arguments[i]);
	}
};
Oce.util.ArrayEx.prototype = [];
Oce.util.ArrayEx.prototype.each = function(fn, scope) {
	Ext.each(this, fn, scope || this);
};

(function() {
	var s = Ext.isArray;
	Ext.isArray = function(o) {
		return s(o) || o instanceof Array;
	}
})();

Oce.util.HashEx = function(){};
Oce.util.HashEx.prototype = {};
Oce.util.HashEx.prototype.iterate = function(fn, scope) {
	Ext.iterate(this, fn, scope || this);
};
Oce.util.HashEx.prototype.each = function(fn, scope) {
	var me = this;
	this.iterate(function(k,v) {
		fn.call(scope || this, v);
	});
};