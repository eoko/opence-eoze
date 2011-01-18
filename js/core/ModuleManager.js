/**
 * @author Éric Ortéga
 */

Oce.NameParser = new function() {

	this.isFunctionnality = function (cannonicalName) {
		// this one makes netbeans highlighter feel bad....
//		return /^(f|m)x\..+/.test(cannonicalName);

		return /^(f|m)x[.].+/.test(cannonicalName);
	}

	this.parseModule = function(cannonicalName, callback, scope) {
		var parts = cannonicalName.split('.');
		var moduleName = parts.pop();
		var controller = parts.pop();
		var namespace = parts.length > 0 ? parts.join('.') : undefined;
		
		if (scope === undefined) callback(namespace, controller, moduleName);
		else callback.call(scope, namespace, controller, moduleName);
	}

	this.parseFunctionnality = function(functionnalityName) {

	}

	this.toName = function(namespace, controller, moduleName) {
		return namespace
			+ ('.' + controller)
			+ (moduleName !== undefined ? '.' + moduleName : '');
	}

	/**
	 * Get the object matching the given parameters. This function does
	 * <b>NOT</b> ensure that the full namespace is valid (defined).
	 * @param {string} namespace
	 * @param {string} [controller]
	 * @param {string} [moduleName]
	 */
	this.resolve = function(namespace, controller, moduleName) {
		var parts = namespace.split('.');
		var o = window[parts.shift()];
		for (var i=0; i<parts.length; i++) {
			o = o[parts[i]];
		}
		if (controller !== undefined) {
			parts = controller.split('.');
			for (i=0; i<parts.length; i++) {
				o = o[parts[i]];
			}

			if (moduleName !== undefined) {
				return o[moduleName];
			}
		}
		return o;
	}

	this.parse = function(cannonicalName, defaultNamespace, defaultController) {
		var parts = cannonicalName.split('.');

		if (parts < 2) throw new 'Illegal Argument';

		var props = {};

		if (parts[0] === 'fx') {
			props.functionalityName = cannonicalName;
			props.isFunctionality = true;
			props.isModule = false;
		} else {
			props.isFunctionality = false;
			props.isModule = true;
			if (parts.length >= 3) {
				props.name = parts.pop();
				props.controller = parts.pop();
				props.namespace = parts.join('.');
				props.isComplete = true;
			} else if (parts.length == 2) {
				props.name = parts.pop();
				props.controller = parts.pop();
				props.namespace = defaultNamespace;
				props.isComplete = false;
			} else if (parts.length == 1) {
				props.name = parts.pop();
				props.controller = defaultController;
				props.namespace = defaultNamespace;
				props.isComplete = false;
			}
		}

		return props;
	}
}



Oce.ModuleManager = function() {

	var defaultMissingModuleHandler = function(error, module) {
		throw 'Cannot load module: ' + module;
	}

	var waitingCallbacks = {};

	function pushWaitingCallback(
		namespace, controller, moduleName,
		successCallback, errorCallback
	) {

		var id = namespace + '.' + controller + '.' + moduleName;

		var cb = {
			success: successCallback
			,error: errorCallback
		};

		if (id in waitingCallbacks) {
			waitingCallbacks[id].push(cb);
		} else {
			waitingCallbacks[id] = [cb];
		}

	}

	function processWaitingCallbacks(id, module, errors) {
		if (waitingCallbacks[id] !== undefined) {
			var i=0, l=waitingCallbacks[id].length;

			if (module !== null) {
				for (; i<l; i++) {
					waitingCallbacks[id][i].success(module);
				}
			} else {
				for (; i<l; i++) {
					waitingCallbacks[id][i].error(errors);
				}
			}

			delete waitingCallbacks[id];
		}
	}

	function loadModule(namespace, controller, moduleName) {

		var id = namespace + '.' + controller + '.' + moduleName;

		Oce.ClassLoader.requireModule(controller, moduleName,
			function() { // success
				// We must wait for module's dependancies to have been honoured,
				// before we process waiting callbacks (or the name will resolve
				// to nothing)
				Oce.deps.wait(
					Oce.NameParser.toName(namespace, controller, moduleName),
					function() {
						processWaitingCallbacks(
							id, Oce.NameParser.resolve(namespace, controller, moduleName)
						);
					}
				);
			}, function(errors) { // failure
				processWaitingCallbacks(
					id, null, errors
				);
			}
		)
	}

	return {

		getModuleByName: function(cannonicalName, successCallback, errorCallback) {
			Oce.NameParser.parseModule(cannonicalName, function(namespace, controller, moduleName) {
				Oce.getModule(namespace, controller, moduleName, successCallback, errorCallback);
			});
		}
		,
		requireModuleByName: function(cannonicalName, successCallback, errorCallback) {
			Oce.getModuleByName(cannonicalName, successCallback, errorCallback || defaultMissingModuleHandler);
		}
		,
		getModule: function(namespace, controller, moduleName, successCallback, errorCallback) {

			var controllerModules = Ext.namespace(namespace + '.' + controller);

			var module = controllerModules[moduleName];

			if (module === undefined) {
//				module = []; // loading mode (store waiting callbacks)
//				controllerModules[moduleName] = module;

				var id = namespace + '.' + controller + '.' + moduleName;

				var launchLoad = waitingCallbacks[id] === undefined;

				pushWaitingCallback(namespace, controller, moduleName,
					successCallback, errorCallback);
					
				if (launchLoad) {
					loadModule(namespace, controller, moduleName);
				}

//			} else if (module instanceof Array) {
//				// means the module is already being loaded
//				// => register a new callback for when the current load
//				// operation will be done
//				pushWaitingCallback(namespace, controller, moduleName,
//					successCallback, errorCallback);
			} else {
				// module already loaded, give it!
				successCallback(module);
			}
		}
		,
		getModules: function(cannonicalNames, callback) {
			if (!Ext.isArray(cannonicalNames)) {
				if (Ext.isString(cannonicalNames)) {
					Oce.getModuleByName(cannonicalNames, function(module) {
						if (callback) callback(module);
					}, function() {
						if (callback) callback(null);
					});
				} else {
					throw new Error('Illegal Argument: cannonicalNames=' + cannonicalNames);
				}
			} else {

				var response = {};

				var responseLatch = new function() {
					var todo = cannonicalNames.length;
					this.countDown = function() {
						if (--todo == 0) {
							if (callback) callback(response);
						}
					}
				}()

				for (var i=0; i<cannonicalNames.length; i++) {
					Oce.getModuleByName(cannonicalNames[i],
						function(module){
							response[cannonicalNames[i]] = module;
							responseLatch.countDown();
						}, function(errors) {
							response[cannonicalNames[i]] = null;
							responseLatch.countDown();
						}
					);
				}
			}
		}
		,
		getMultiples:  function(cannonicalNames, callback) {

			var response = {}
			var modules = []
			var functionnalities = []

			var responseLatch = new function() {
				var todo = cannonicalNames.length;
				this.countDown = function() {
					if (--todo == 0) {
						callback(response, modules, functionnalities);
					}
				}
			}()

			for (var i=0; i<cannonicalNames.length; i++) {
				get(cannonicalNames[i],
					function(module){
						response[cannonicalNames[i]] = module;
						if (Oce.NameParser.isFunctionnality(cannonicalNames[i]))
							functionnalities.push(module);
						else
							modules.push(module);
						responseLatch.countDown();
					}, function(error) {
						response[cannonicalNames[i]] = null;
						responseLatch.countDown();
					}
				);
			}
		}
		,
		requireModules: function(cannonicalNames, successCallback, errorCallback) {
			if (cannonicalNames.length === 0) {
				return successCallback([]);
			}
			var errors = [];
			Oce.getModules(cannonicalNames, function(modules) {
				for (var name in modules) {
					if (modules[name] === null) errors.push('Missing module: ' + name);
				}
				if (errors.length > 0) {
					if (errorCallback === undefined) defaultMissingModuleHandler(errors);
					else errorCallback(errors);
				}
				else successCallback(modules);
			})
		}
		,
		getFunctionnality: function(name, successCallback, errorCallback) {
			// TODO
		}
		,
		get: function(canonicalName, successCallback, errorCallback) {
			var props = Oce.NameParser.parse(cannonicalName);
			if (props.isModule) {
				if (!props.isComplete) throw new 'IllegalArgument: ' + canonicalName;
				return this.getModule(props.namespace, props.controller, props.name, successCallback, errorCallback);
			} else {
				return this.getFunctionnality(props.functionnalityName, successCallback, errorCallback);
			}
		}

	}
}()

// Import ModuleManager namespace into Oce.
Ext.apply(Oce, Oce.ModuleManager);