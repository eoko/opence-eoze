
//Oce.Module = Oce.Object.create(function(my) {
//
////	my.namespace;
////	my.controller;
////	my.name;
//
//	this.init = function() {
//		// override me :)
//	}
//
//	// Override create, to trigger the module initialisation process on creation
//	this.create = function(o) {
//		// Create the object
//		var obj = Oce.Object.create.call(this, o);
//
//		// Init
//		loadDependencies.call(obj);
//		obj.init();
//
//		// Must be done after initialisation since register will call back
//		// functions that are waiting for this module to be loaded, and that will
//		// must probably use it...
//		register.call(obj);
//
//		return obj;
//	}
//
//	this.createCore = function(o) {
//		// Create the object
//		var obj = Oce.Object.create.call(this, o);
//
//		// Init
//		this.init();
//
//		return obj;
//	}
//
//	function loadDependencies() {
//
//		//--- Take care of dependencies
//		if ('needs' in this.my) {
//			// TODO
//		}
//		if ('likes' in this.my) {
//			// TODO
//		}
//	}
//
//	function register() {
//
//		//--- Grab canonical name, namespace & controller
//
//		if (!('name' in this.my)) throw new Error('Invalid');
//
//		if (this.my.controller === undefined || this.my.namespace === undefined) {
//			Oce.NameParser.parseModule(this.my.name, function(ns, controllerName, moduleName){
//				this.my.namespace = this.my.namespace || ns || 'Oce.Modules.ux';
//				this.my.controller = this.my.controller || controllerName || 'root';
//			}, this)
//		}
//
//		// Register the namespace
//		var fullNS = this.my.namespace + '.' + this.my.controller;
//		Ext.namespace(fullNS + '.' + this.my.name);
//
//
//		// --- Register module to the ModuleManager
//		//
//		// This step will put the instance in its fully qualified namespace,
//		// and trigger all callback waiting for this module to be loaded
//
//		Oce.ModuleManager.register(this.my.namespace, this.my.controller, this.my.name, this);
//	}
//
//	// ---
//
//})
//
//Oce.ApplicationModule = Oce.Module.createCore(function(my) {
//
////	my.controller;
////	my.application;
//
////	this.start = function() {
////		this.$prototype.start();
////	}
//
//	this.open = function(destination) {
//
//	}
//})


Oce.Module = Ext.extend(Ext.util.Observable, {

	constructor: function(config) {
		Oce.Module.superclass.constructor.call(this, config);
		this.initEvents();
	}

	,initEvents: function() {

	}

	,start: function() {

	}
});

Oce.Module.executeAction = function(action, listeners, scope) {
	
	var before, after;
	
	if (!listeners) {
		listeners = action.listeners;
	}
	if (listeners) {
		scope = listeners.scope || scope;
		before = listeners.beforeAction || listeners.before;
		after = listeners.afterAction || listeners.after;
	}
	
	if (before) before.call(scope);
	
	var module = action.module;
	
	if (module.substr(0,12) !== "Oce.Modules.") {
		module = String.format("Oce.Modules.{0}.{0}", action.module);
	}
	
	Oce.mx.application.getModuleInstance(
		module,
		function(module) {
			module.executeAction({
				action: action.method || action.action
				,args: action.args
				,callback: function() {
					if (after) after.call(scope, true);
				}
			});
		},
		function() {
			after.call(scope, false);
		}
	);
};

Oce.getModule = function(module, callback, scope) {
	
	var app = Oce.MainApplication || Oce.mx.application;
	
	if (module.indexOf(".") == -1) {
		module = String.format("Oce.Modules.{0}.{0}", module);
	}
	var registry = app.moduleInstances;
	
	if (registry[module]) {
		if (callback) {
			app.getModuleInstance(module, callback, scope);
		}
		return registry[module];
	}
	
	else if (callback) {
		app.getModuleInstance(module, callback, scope);
	}
	
	else {
		app.getModuleInstance(module, function(m) {
			registry[module] = m;
		});
//		throw new Error(String.format("Module {0} has not been loaded", module));
	}
	
	return false;
}

Oce.ApplicationModule = Ext.extend(Ext.Panel, {

//	my: {}
//
//	,getName: function() {
//		return this.my.name;
//	}
//
//	,getController: function() {
//		return this.my.controller;
//	}
//
//	,getNamespace: function() {
//		return this.my.namespace;
//	}
//
//	,getInstance: function() {
//		throw new Error('Abstract');
//	}
})

/* REM

Oce.ApplicationModule = function(name) {

	isLoaded = function() {return Oce.Application.isLoaded(instance)}

	load = function(moduleLib) {Oce.Application.loadModule(instance)}

	focus = function() {
		alert('focusing on ' + this.getName())
		// TODO
	}

	getTitle = function() {
		// TODO
		return this.getName();
	}

	this.start = function(destination) {
		destination.add(this);
	}
}
Oce.ApplicationModule.prototype = Oce.Module;


Oce.module = function(config, constructor) {

	var name = config.name;

	var controller, namespace;
	if (config.controller === undefined || config.namespace === undefined) {
		if (config.controller !== config.namespace) throw new 'Invalid Config Argument';

		Oce.NameParser.parseModule(name, function(ns, controllerName, moduleName){
			namespace = ns || 'Oce.Modules.ux';
			controller = controllerName || 'root';
			name = moduleName;
		})
	} else {
		namespace = config.namespace;
		controller = config.controller;
	}

	// Register namespace
	var fullNS = namespace + '.' + controller;
	Ext.namespace(fullNS + '.' + name);

	// Handle inheritance
	constructor.prototype = config.base;

	// Needs, likes & accesses
	var fx = {};

	var includes = [];
	if (config.requires !== undefined) includes = config.requires;
	if (config.likes !== undefined) includes = includes.concat(config.likes);

	var instance;

	var builder = function(results, modules, functionnalities) {
		// Test requires
		if (config.requires !== undefined) {
			for (var i=0, l=config.requires.length; i<l; i++) {
				if (results[config.requires[i]] == null) {
					throw new 'Missing required module: ' + config.requires[i];
				}
			}
		}

		// TODO: functionnalities
		var fx = {};

		constructor.prototype = config.base;

		// Create singleton/factory
		instance = new function() {

			var tools = {
				isset: function(variable) {
					return fx[variable] !== undefined;
				},
				createAPI: function(members) {
					function F(){}
					F.prototype = config.base;
					return Ext.apply(new F(), members);
				}
			}

			var o = constructor(config.base, tools);

			if (!(o instanceof Oce.Module)) {
				config.base.constructor.apply(o, o)
			}
		}();
	}

	if (includes.length > 0) {
		Oce.getMultiples(includes, builder);
	} else {
		builder({});
	}
	
//	var ns = Oce.NameParser.resolve(namespace, controller);

	Oce.ModuleManager.register(namespace, controller, name, instance);
//	ns[name] = instance;

	return instance;
}
*/



/*
echo = function() { console.log.apply(this,arguments) }

Module = function() {
    var moduleConstructor = function() {}

    //moduleConstructor.prototype = new Module.constructor()
    moduleConstructor.prototype = {
        isA: function(o) {
            var proto = this.$prototype;
            while (proto !== undefined) {
                if (proto === o) return true;
                proto = proto.$prototype;
            }
            return this === o;
//            return this.$prototype instanceof o.$constructor || this === o;
//            return this.$constructor.prototype instanceof o.$constructor || this === o;
        }
        ,augments: function(o) {
            if (Ext.isObject(o)) {
                var constructor = function(){}
                constructor.prototype = this;
                var obj = new constructor();

                obj.$constructor = constructor;
                obj.$prototype = this;

                Ext.apply(obj, o);

                return obj;
            } else if (Ext.isFunction(o)) {
                o.prototype = this;
                var obj = new o();

                obj.$constructor = o;
                obj.$prototype = this;

                return obj;
            }
        }

        ,name: 'Module'
        ,greats: function() { return this.name + ' says "Hello, sir"' }
    }

    var o = new moduleConstructor();
    o.$prototype = undefined;
    o.$constructor = moduleConstructor;

    return o;
}()

var MyModule = Module.augments({
})

var MyMyModule = MyModule.augments({
})

var MyModule2 = Module.augments(function(){
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

*/