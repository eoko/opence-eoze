// packer.compilable
/**
 * @author Éric Ortéga <eric@plansyphere.fr>
 */

Ext.namespace('Oce');

Oce.ClassLoader = function() {

    var loadedClasses = {};

    var rawRequestMaker = function(controller, action, name) {
        return 'index.php?controller=' + controller + '&action=' + action + '&name=' + name;
    };
    var htaccessRequestMaker = function(controller, action, name) {
        return 'jsod/' + controller + '/' + name + (action == 'get_module' ? '.mod' : '') + '.js';
    };

    var config = {
        // If true: fails on the first required file which has an error
        // DBG: can be turns ON in prod, best on OFF in debug
        failFast: false
        
        // static: the script is loaded by creating a script tag with its src 
        // property set => a consistent & readable filename appears as firebug
        // source script, and error line number are the same as in the real file
        ,method: 'static'
        // ajax: the script content is loaded by AJAX, and passed through eval
        // => produce charabia filenames and different file/line numbers than
        // in the real file, but the script loading request appears in firebug's
        // console
//        ,method: 'ajax'
        // ajax-debug: mix the previous two approach to combine the best of the
        // two world, at the cost of effectively loading the file twice. The
        // content loaded by AJAX is ignored though...
//        ,method: 'ajax-debug'

//        ,getRequestMaker: htaccessRequestMaker
        ,getRequestMaker: rawRequestMaker
    };

    // Force ajax loading for IE (doesn't load static script ... probably due
    // to our method, that seems weird that the principle in itself doesn't
    // work in ie)
    if (Ext.isIE) {
        config.method = 'ajax';
    }

    var defaultErrorHandler = function(error) {
//        Oce.Logger.error(error);
        throw error;
    };

    var requestModule = function (fn, controller, jsModule, successCallback, errorCallback) {
        return fn(controller, jsModule, function(success, error) {
            if (success) {
                successCallback();
            } else {
                if (errorCallback !== undefined) {
                    errorCallback(error);
                } else {
                    defaultErrorHandler(error);
                }
            }
        }, 'get_module');
    };

    /**
     *
     * @param {string} controller name of the module to load from
     * @param {string} name name of the class to load
     * @param {function} [callback] callback(boolean success, array[error])
     * @param {string} [action='get_js'] callback(boolean success, array[error])
     */
    var loadFile = function (controller, name, callback, action) {

        if (action === undefined) {
            action = 'get_js';
        }

//        Oce.Logger.debug('Loading class/module file: ' + name);

        if (config.method == 'static') {
            var headID = document.getElementsByTagName("head")[0];
            var newScript = document.createElement('script');
            newScript.type = 'text/javascript';
            // TODO
            newScript.onload=callback;
            newScript.src = config.getRequestMaker(controller, action, name);
//            Oce.Logger.debug('src=' +newScript.src);
            headID.appendChild(newScript);
        } else {
            Oce.Ajax.requestRaw({
                params: {
                    'controller': controller,
                    'action': action,
                    'name': name
                }
                ,waitMsg: false

                ,onSuccess: function(responseText) {
                    var success = true;

                    try {
                        if (config.method == 'ajax-debug') {
                             var headID = document.getElementsByTagName("head")[0];
                             var newScript = document.createElement('script');
                             newScript.type = 'text/javascript';
                             // TODO
                            newScript.onload = function() {
                                callback(true);
                            };

                            newScript.src = config.getRequestMaker(controller, action, name);
                             headID.appendChild(newScript);
                        } else {
                            eval(responseText);
                        }
                    } catch (err) {
    //                    console.error('Line ' + err.lineNumber - 39 + ': ' + err);
                        console.error(err);
                        throw err;
    //                    callback(false, new Oce.ClassLoader.BrokenClassException(module, clazz, err));
    //                    success = false;
                    }
                    if (success) {
    //                    loadedClasses[Oce.ClassLoader.canonicClassName(module, clazz)] = true;
                        loadedClasses[name] = true;
                        callback(true);
                    }
                },

                onFailure: function(errors, response) {
                    callback(false, new Oce.ClassLoader.ClassNotFoundException(controller, name));
                }
            });
        }
    };

    var ControllerClassLoader = function(controller) {

        var doneCount = 0;
        var fullSuccess = true;
        var errors = [];

        var readyCallbacks = [];

        var countDown = function(success, newErrors) {
            doneCount--;
            fullSuccess = fullSuccess && success;
            errors.push(newErrors);

            if (doneCount === 0) {
                processCallbacks();
            }
        }

        var processCallbacks = function() {
            Ext.each(readyCallbacks, function(callback) {
                callback(fullSuccess, errors);
            });
        };

        return {
            require: function(classes) {
                doneCount++;

                if (arguments.length > 1) {
                    classes = Array.prototype.slice.call(arguments);
                }

                Oce.ClassLoader.require(controller, classes, countDown);
                return this;
            }
            ,include: function(classes) {
                doneCount++;

                if (arguments.length > 1) {
                    classes = Array.prototype.slice.call(arguments);
                }

                Oce.ClassLoader.include(controller, classes, countDown);
                return this;
            }
            ,requireModule: function(module, successCallback, errorCallback) {
                doneCount++;
                Oce.ClassLoader.requireModule(controller, module, successCallback, errorCallback);
                return this;
            }
            /**
             * Called when all the previously requested class are loaded.
             * @param {function} [callback] callback(boolean success, array[error])
             */
            ,onReady: function(callback) {
                readyCallbacks.push(callback);
                if (doneCount === 0) {
                    processCallbacks();
                }
            }
        }
    };

    return {

        ClassNotFoundException: function(controller, clazz) {
            return {
                controller: controller
                ,className: clazz
                ,name: 'ClassNotFoundException'
                ,message: 'Class not found: ' + clazz + ' in controller: ' + controller
                ,toString: function() {return this.message;}
            }
        }
        
        ,BrokenClassException: function(controller, clazz, previous) {
            var r = {
                controler: controller
                ,className: clazz
                ,previous: previous
                ,name: 'BrokenClassException'
                ,message: 'Broken class: ' + clazz + ' in controller: ' + controller + '. Error: ' + previous
                ,toString: function() {return this.message;}
            };
//            Oce.Logger.debug(r.toString());
            return r;
        }
        
        ,canonicClassName: function(controller, clazz) {return clazz;}

        ,inController: function(controller) {
            return new ControllerClassLoader(controller);
        }

        /**
         * @param {String} controller
         * @param {Array} classes
         * @param {Function} callback
         * @param {boolean} [failFast]
         * @param {string} [action]
         */
        ,include: function(controller, classes, callback, failFast, action) {

            failFast = failFast !== undefined ? failFast : false;
            action = action !== undefined ? action : 'get_js';

            // --- Loading multiple classes
            if (classes instanceof Array) {

                var count = classes.length;
                var errors = [];

                Ext.each(classes, function(clazz) {

                    // If class is already loaded
                    if (false == clazz in loadedClasses) {
//                        callback(true);
//                    } else {

                        // If loading has not already (fast-)failed
                        if (callback !== null) {
                            loadFile(controller, clazz, function(success, error) {

                                // Error handling
                                if (!success) {
                                    errors.push(error);

                                    if (failFast) {
                                        callback(false, errors);
                                        callback = null; // prevents other loading tentatives
                                        count = -1; // prevents count from firing the callback
                                    }
                                }
                            }, action);
                        }
                    }

                    // Countdown
                    if (--count == 0) {
                        if (errors.length == 0) {
                            callback(true);
                        } else {
                            callback(false, errors);
                        }
                    }
                });
            }
            
            // --- Loading one class
            else if (Oce.isString(classes)) {
                loadFile(controller, classes, function(success, error) {
                    if (success) {
                        callback(true);
                    } else {
                        callback(false, [error]);
                    }
                }, action);
            }
            
            // --- Ilegal argument
            else {
                throw new Error('IllegalArgument');
            }

            return this;
        }
        
        ,require: function(controller, classes, callback, action) {
            return Oce.ClassLoader.include(controller, classes, function(success, errors) {
                if (success) {
                    callback(success);
                } else {
                    callback(success, errors);
                }
            }, config.failFast, action);
        }

        ,requireModule: function(controller, jsModule, successCallback, errorCallback) {
            return requestModule(Oce.ClassLoader.require, controller, jsModule, successCallback, errorCallback);
        }

        ,includeModule: function(controller, jsModule, successCallback, errorCallback) {
            return requestModule(Oce.ClassLoader.include, controller, jsModule, successCallback, errorCallback);
        }

    }
}(); // closure