Ext.ns('Oce.mx', 'Oce.ext');

Ext.BLANK_IMAGE_URL = Oce.ext.BLANK_IMAGE_URL || 'images/s.gif';
Ext.Ajax.url = 'index.php';

if (typeof Oce)

if (!Oce.functionality) {

	Oce.functionality = {}

	Oce.Functionality = function(name, fn) {
		Oce.functionality[name] = {
			get: Ext.isFunction(fn) ? fn : function() { return fn }
		}
	}
}

(function() {

var NS = Ext.ns("eo").deps = Oce.deps = {

	registeredKeys: {}
	,lockedKeys: {}
	,lockingKeys: {}
	
	,waitIn: function(ns, key, callback) {
		if (Ext.isArray(key)) {
			var tmp = [];
			Ext.each(key, function(k) {
				tmp.push(ns + "." + k);
			});
			return this.wait(tmp, callback);
		} else if (Ext.isString(key)) {
			return this.wait(ns + "." + key, callback);
		} else {
			throw new Error("Illegal argument type: " + (typeof key));
		}
	}

	/**
	 * @return {boolean} TRUE if all dependencies are already loaded (that is,
	 * the callback has already been called at the time of returning; else FALSE.
	 */
	,wait: function(key, callback) {
//		if (Ext.isObject(key)) {
//			var tmp = [];
//			Ext.iterate(key, function(ns, nsk) {
//				if (Ext.isArray(nsk)) {
//					Ext.each(nsk, function(k) {
//						tmp.push(ns + "." + k);
//					});
//				} else {
//					tmp.push(ns + "." + nsk);
//				}
//			});
//			if (tmp.length === 1) {
//				key = tmp[0];
//			} else {
//				key = tmp;
//			}
//		}
		if (Ext.isArray(key)) {
			var actualWaitings = [];
			for (var i=0,l=key.length; i<l; i++) {
				if (this.registeredKeys[key[i]] !== true) actualWaitings.push(key[i]);
			}
			if (!actualWaitings.length) {
				callback();
				return true;
			} else {
				var latchs = actualWaitings.length;
				var releaseFn = function() {
					if (--latchs === 0) callback();
				};
				Ext.each(actualWaitings, function(key) {
					NS.wait(key, releaseFn);
				});
				return false;
			}
		} else {
			if (this.registeredKeys[key] === true) {
				callback();
				return true;
			} else if (NS.registeredKeys[key]) {
				this.registeredKeys[key].push(callback);
				return false;
			} else {
				this.registeredKeys[key] = [callback];
				return false;
			}
		}
	}
	
	,notify: function(key) {
		if (!this.lockedKeys[key]) {
			if (this.registeredKeys[key]) {
				for (var i=0,l=this.registeredKeys[key].length; i<l; i++) {
					this.registeredKeys[key][i]();
				}
			}
			this.registeredKeys[key] = true;
		}
	}

	,reg: function(key, ns) {
		
		if (ns) {
			key = ns + "." + key;
		}

		this.notify(key);

		if (this.lockingKeys[key]) {
			Ext.each(this.lockingKeys[key], function(lockedKey) {

				var lk = NS.lockedKeys
					,l = lk.len
					;

				for (var i=0; i<l; i++) {
					if (lk[i] == key) {
						lk.slice(i,1);
						l--;
					}
				}

				if (lk.length == 0) {
					delete NS.lockedKeys[lockedKey];
				}

				NS.notify(lockedKey)
			});
			delete this.lockingKeys[key];
		}


//		if (Oce.deps.lockedKeys[key]) {
//			var lockingKeys = Oce.deps.lockedKeys[key];
//			for (i=0,l=lockingKeys.length; i<l; i++) {
//				if (lockingKeys[i] === key) {
//					lockingKeys.slice(i,1);
//					l--;
//				}
//			}
//
//			if (lockingKeys.length === 0) {
//				Oce.deps.registeredKeys[key] = true;
//				delete Oce.deps.lockedKeys.key;
//			}
//
//		} else {
//			Oce.deps.registeredKeys[key] = true;
//		}
	}

	,waitAndLock: function(key, unlockKey, callback) {
		NS.wait(key, function() {
			NS.lock(key, unlockKey);
			callback();
		})
	}

	,lock: function(key, unlockKey) {

		if (unlockKey === undefined) unlockKey = key;

		if (NS.registeredKeys[key] === true) {
			NS.registeredKeys[key] = [];
		}

		if (NS.lockedKeys[key]) {
			NS.lockedKeys[key].push(unlockKey);
		} else {
			NS.lockedKeys[key] = [unlockKey];
		}

		if (NS.lockingKeys[unlockKey]) {
			NS.lockingKeys[unlockKey].push(key);
		} else {
			NS.lockingKeys[unlockKey] = [key];
		}
	}
}

})(); // closure

// Array.indexOf compatibiliy
// @author https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Array/indexOf
if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function (searchElement /*, fromIndex */ ) {
        "use strict";
        if (this === void 0 || this === null) {
            throw new TypeError();
        }
        var t = Object(this);
        var len = t.length >>> 0;
        if (len === 0) {
            return -1;
        }
        var n = 0;
        if (arguments.length > 0) {
            n = Number(arguments[1]);
            if (n !== n) { // shortcut for verifying if it's NaN
                n = 0;
            } else if (n !== 0 && n !== (1 / 0) && n !== -(1 / 0)) {
                n = (n > 0 || -1) * Math.floor(Math.abs(n));
            }
        }
        if (n >= len) {
            return -1;
        }
        var k = n >= 0 ? n : Math.max(len - Math.abs(n), 0);
        for (; k < len; k++) {
            if (k in t && t[k] === searchElement) {
                return k;
            }
        }
        return -1;
    }
}