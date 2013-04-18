(function(){
	
Oce.MainApplication = {
	
	moduleInstances: {}
	
	,waitingFactoryCallbacks: {}

	,start: function(){
		var env = Oce.Context.environment;
		if (env) {
			if (!Oce.Environments) {
				alert('Oce.Environments is undefined');
			} else {
				Oce.Environments[env]();
			}
		} else {
			eo.root.MainApplication.start();
//			Oce.ClassLoader.require('root', 'ApplicationBootstrap', function(success) {
//				if (success) eo.root.MainApplication.start();
//			});
		}
	}

	,defaultModuleLoadingErrorCallback: function(error) {
		// TODO
	}

	/**
	 * This method tries to use factories registered in Oce.MainApplication.moduleFactories
	 * to delegate creation of module instances.
	 * 
	 * A factory function must return `true` to take the creation of the instance to
	 * its charge.
	 * 
	 * @return {Boolean}
	 * 
	 * @private
	 */
	,createModuleInstance: function(moduleName, callback) {
		
		var me = this,
			queue = this.waitingFactoryCallbacks[moduleName];
		
		if (queue) {
			queue.push(callback);
			return true;
		}
		
		else {
			queue = this.waitingFactoryCallbacks[moduleName] = [];
			
			var factories = Oce.MainApplication.moduleFactories,
				fn, i, l, delegating;
				
			for (i=0, l=factories.length; i<l; i++) {
				fn = factories[i];
				delegating = fn(moduleName, function(instance) {
					Ext.each(queue, function(cb) {
						cb(instance);
					});
					delete me.waitingFactoryCallbacks[moduleName];
					callback(instance);
				});
				if (delegating === true) {
					return true;
				}
			}
			return false;
		}
	}

	,getModuleInstance: function(moduleName, callback, errorCallback) {
		if (moduleName in this.moduleInstances) {
			if (callback) {
				callback(this.moduleInstances[moduleName]);
			}
		} else {
			var me = this;
			var previousCursor = Ext.getBody().getStyle("cursor");
			
			// Trying module factories
			if (this.createModuleInstance(moduleName, function(module) {
				me.moduleInstances[moduleName] = module;
				me.getModuleInstance(moduleName, callback, errorCallback);
			})) {
				return;
			}
			
			Oce.getModuleByName(moduleName,
				function(module) {
					
					var cb,
						instance = me.moduleInstances[moduleName];

					if (callback) {
						cb = function() {
							if (Ext.isChrome) {
								// Chrome handles error correctly, but traces them
								// to the point where they are thrown, so it's 
								// better to not rethrow the exception
								callback(instance);
							} else {
								try {
									callback(instance);
								} catch (err) {
									throw err;
								}
							}
						};
					}
					
					if (!instance) {
						instance = me.moduleInstances[moduleName] = new module()
						if (instance.doAsyncConstruct) {
							instance.doAsyncConstruct(cb)
						} else if (cb) {
							cb();
						}
					} else {
						if (cb) {
							cb();
						}
					}
				}
				// error
				,function(error) {
					Ext.getBody().setStyle("cursor", previousCursor);
					if (errorCallback) errorCallback(error);
					else this.defaultModuleLoadingErrorCallback(error);
				}
			)
		}
	}
	
	,exec: function(cmd, action, args, callback) {
		Oce.command.compile(cmd, action, args, callback)();
	}
	
	,open: function(cmd, callback) {
		Oce.command.compile(cmd, "open", this.getMainDestination(), callback, true)();
	}

	,close: function(force, callback) {
		callback(true);
	}
	
	,getMainDestination: function() {
		return Ext.getCmp('main-destination');
	}

	/**
	 * Get the currently displayed module.
	 *
	 * @return {Oce.BaseModule/null}
	 */
    ,getFrontModule: function() {
        var md = this.getMainDestination(),
            t = md && md.getActiveTab();
        if (t) {
			if (t.getActiveModule) {
				return t.getActiveModule();
			} else {
				return t.module;
			}
		} else {
			return null;
		}
    }
};

// Initialize module factory register
Oce.MainApplication.moduleFactories = [];

/**
 * Registers a module factory.
 *
 * For dynamic resolving of module names, this method also accepts skipping the first argument. In
 * this case, the factoryFunction must return `true` if it decide to resolve the module itself.
 *
 * Example:
 *
 *     Oce.registerModuleFactory(function(name, callback) {
 *         if (name === resolvableModuleName) {
 *             resolveModule()
 *                 .always(callback); // assuming resolveModule returns a Promise
 *
 *             // return true to indicate that the module manager must stop searching
 *             return true;
 *         }
 *     });
 *
 * @param {String} moduleName
 * @param {Function} factoryFunction
 * @param {Object} [scope]
 */
Oce.registerModuleFactory = function registerModuleFactory(moduleName, factoryFunction, scope) {
	// Arguments
	if (Ext.isFunction(moduleName)) {
		scope = factoryFunction;
		factoryFunction = moduleName;
	}

	// Register with module name
	if (moduleName) {
		if (moduleName.indexOf('.') === -1) {
			moduleName = Ext.String.format('Oce.Modules.{0}.{0}', moduleName);
		}
		var fn = factoryFunction;
		factoryFunction = function(name, callback) {
			if (moduleName === name) {
				var promise = fn.call(scope, callback);

				Oce.deps.reg(moduleName);

				if (promise) {
					promise.then({
						success: callback
					});
				}

				return true;
			}
		};
	}
	// Register without module name (runtime binding)
	else if (scope) {
		factoryFunction = Ext.Function.bind(factoryFunction, scope);
	}

	// push
	Oce.MainApplication.moduleFactories.push(factoryFunction);
};

Oce.getFrontModule = function() {
    return Oce.mx.application.getFrontModule();
};

var parseCmdRegex = /^(.+)#(.+?)(?:\((.+)\))?$/;
var splitArgsRegex = /\s*,\s*/;

var mergeCallbacks = function(f1, f2) {
	if (!f2) return f1;
	else if (!f1) return f2;
	
	return function(module) {
		f1(module);
		f2(module);
	}
}

Oce.command = {

	/**
	 * Creates a function to execute the specified command, deferring the actual
	 * compilation of the command until the first time the returned function is
	 * called.
	 */
	create: function(cmd, action, args, callback, forceParse) {
		var f = function(cb) {
			f.run(cb);
		}
		f.run = function(cb) {
			f.run = Oce.command.compile(cmd, action, args, callback, forceParse);
			f.run(cb);
		}
		f.toString = function() {
			var a = "";
			if (args) {
				if (Ext.isString(args)) a = args;
				else if (Ext.isArray(args)) {
					a = args.join(",");
				}
			} else {
				a = "";
			}
			return String.format(
				"javascript: Oce.cmd('{0}')()",
				cmd + (action ? "#" + action : "") + a
			)
		}
		return f;
	}
	
	/**
	 * Compiles a command, that is deciphers how the command is specified from
	 * the various allowed way and returns a function which directly execute
	 * the specified command.
	 * 
	 * <p>A callback can be passed as any of the 2nd, 3rd or 4th arguments.
	 * Only one callback can be passed, though, since as soon as a function is
	 * found in the argument list, it will be considered the last argument.
	 * The Boolean value <b>false</b> can be passed as the <b>callback</b>
	 * argument, to prevent this searching behaviour.
	 * The callback will be called with an instance of the module specified
	 * in the <b>cmd</b> string as its only argument.</p>
	 * 
	 * <p>An action can be specified with the <b>action</b> and </b>args</b>
	 * arguments, or in the <b>cmd</b> string, but not both (except if the
	 * <b>forceParse</b> is set to <b>true</b>. Elst, if the <b>action</b>
	 * argument is given, the <b>cmd</b> string won't be parsed, and the module
	 * name will consequently be invalid if it contains an action...</p>
	 * 
	 * @param {Boolean} forceParse If set to <code>true</code>, the {@link #cmd}
	 * parameter will be parsed, even if an {@link #action} is given. If the
	 * <code>cmd</code> string do contains an action, it will be executed after
	 * the one passed as parameter (but before the callback).
	 */
	,compile: function(cmd, action, args, callback, forceParse) {
		
		if (callback === undefined) {
			if (Ext.isFunction(action)) {
				callback = action;
				action = false;
			} else if (Ext.isFunction(args)) {
				callback = args;
				args = false;
			}
		}

		if (forceParse && action) {
			var aAction = action, aArgs;
			if (args) aArgs = Ext.isArray(args) ? args : [args];
			callback = mergeCallbacks(function(module) {
				module[aAction].apply(module, aArgs);
			}, callback);
			// clear the args, so that the cmd will be parsed
			action = undefined;
		}
		
		if (!action) {
			var m = parseCmdRegex.exec(cmd);
			if (m) {
				cmd = m[1];
				action = m[2];
				args = m[3];
				if (args) args = args.split(splitArgsRegex);
			}
		} else {
			// ensure the passed arg is an array
			if (!Ext.isArray(args)) args = [args];
		}

		if (action) {
			if (callback) {
				return function(cb) {
					callback = mergeCallbacks(callback, cb);
					Oce.mx.application.getModuleInstance(cmd, function(module) {
						module[action].apply(module, args);
						callback(module);
					});
				};
			} else {
				return function(cb) {
					Oce.mx.application.getModuleInstance(cmd, function(module) {
						module[action].apply(module, args);
						if (cb) cb(module);
					});
				};
			}
		} else {
			if (callback) {
				return function(cb) {
					Oce.mx.application.getModuleInstance(cmd, mergeCallbacks(callback, cb));
				};
			} else {
				return function(cb) {
					Oce.mx.application.getModuleInstance(cmd, cb);
				};
			}
		}
	}
};

// Shortcut
Oce.cmd = Oce.command.create;

})(); // closure

Oce.Functionality('application', Oce.MainApplication);
