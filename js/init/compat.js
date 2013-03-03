/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 19 sept. 2012
 */
if (Ext.Compat) {
//	Ext.Compat.silent = true;
}

else {
//else if (console && console.warn) {

//console.warn('Dead code');

Ext.ns('Ext.toolbar');
Ext.toolbar.TextItem = Ext.Toolbar.TextItem;
Ext.grid.View = Ext.grid.GridView;


(function() {

	var extend = Ext.extend,
		noArgs = [];

	Ext.extend = function() {

		var c = extend.apply(this, arguments);

		Ext.iterate(c.prototype, function(k, e) {
			if (Ext.isFunction(e)) {
				e.$owner = c;
				// This test is needed to support aliasing of functions as a class member
				// 
				// Like in:
				// 
				// MyClass = Ext.extend(AnotherClass, {
				// 
				//   AliasedClass: com.demo.AliasedClass
				// 
				// });
				// 
				// Without the test, the $name for com.demo.AliasedClass will be changed
				// to AliasedClass (instead of expected 'constructor').
				//
				if (!e.$name) {
					e.$name = k;
				}
			}
		});

		c.prototype.callParent = function(args) {
			var m = this.callParent.caller;
			return m.$owner.superclass[m.$name].apply(this, args || noArgs);
		};

		return c;
	};

})();

Ext.ns('Ext.Function');
Ext.Function.createSequence = function (originalFn, newFn, scope) {
	if (!newFn) {
		return originalFn;
	}
	else {
		return function() {
			var result = originalFn.apply(this, arguments);
			newFn.apply(scope || this, arguments);
			return result;
		};
	}
};

Ext.Function.createDelegate = function(fn, obj, args, appendArgs) {
	var args = Array.prototype.slice.call(arguments, 0),
		fn = args.shift();
	return fn.createDelegate.apply(fn, args);
};

Ext.bind = Ext.Function.createDelegate;

(function() {
var reg = Ext.reg;
var resolve = function resolve(name, force) {
	var o;
	if (!Ext.isString(name)) {
		o = name;
	} else {
		o = window;
		//noinspection FunctionWithInconsistentReturnsJS
		Ext.each(name.split('.'), function(sub) {
			o = o[sub];
			if (!o) {
				return false;
			}
		});
	}
	if (!o || o === window) {
		if (force !== false) {
			throw new Error('Class not found: ' + name);
		} else {
			return null;
		}
	}
	return o;
};

// Ext.reg
Ext.reg = function(xtype, cls) {
	var o = resolve(cls);
	return reg(xtype, o);
};

// --- Ext.define

var setClass = function(cls, o) {
	var parts = cls.split('.'),
		name = parts.pop();
	return Ext.namespace(parts.join('.'))[name] = o;
};

var alias = function(aliases, c) {
	if (aliases) {
		Ext.each(aliases, function(alias) {
			var r = /^([^.]+)\.(.+)$/.exec(alias);
			if (!r) {
				throw new Error('Illegal alias: ' + alias);
			}
			if (r[1] === 'widget') {
				Ext.reg(r[2], c);
			} else if (r[1] === 'feature' || r[1] === 'plugin') {
				throw new Error('Not implemented yet: support for alias namespace: ' + r[1]);
			} else {
				throw new Error('Illegal alias namespace: ' + r[1]);
			}
		});
	}
};

var define = function(cls, o, createFn) {
	var parentCls,
		parent,
		deps,
		previous = cls && resolve(cls, false);

	if (o.extend) {
		parentCls = o.extend;
		parent = resolve(parentCls, false);
		if (!parent) {
			deps = parentCls;
		}
	} else {
		parent = Object;
	}

	if (o.requires) {
		if (deps) {
			deps = [deps];
			deps = deps.concat(o.requires);
		} else {
			deps = o.requires;
		}
	}
	
	var define = function() {
		var c = Ext.extend(parent, o);
		c.prototype.$className = cls;
		alias(o.alias, c);
		if (cls) {
			var C = setClass(cls, c);

			if (previous) {
				Ext.apply(C, previous);
			}

			Ext.reg(cls, cls);
			Oce.deps.reg(cls);
		}
		
		if (createFn) {
			createFn.call(c);
		}
		
		return c;
	};
	
	if (deps) {
		Oce.deps.wait(deps, function() {
			if (parentCls) {
				parent = resolve(parentCls);
			}
			define();
		});
	} else {
		return define();
	}
};

Ext.define = function(cls, o, createFn) {
	if (o.singleton) {
		var previous = resolve(cls, false),
			instance;

		var postCreate = function() {
			var constructor = this;
			instance = new constructor;
			if (cls) {
				var matches = /^(?:(.*)\.)?([^.]+)$/.exec(cls),
					name = matches[2],
					ns = matches[1],
					node = window;
				if (ns) {
					node = resolve(ns, true);
				}
				node[name] = instance;
				if (previous) {
					Ext.apply(instance, previous);
				}
			}

			Ext4.callback(createFn, this, arguments);
		};

		define(cls, o, postCreate);

		return instance;
	} else {
		return define(cls, o, createFn);
	}
};

// --- Ext.create

Ext.ComponentMgr.create = function() {
	var uber = Ext.ComponentMgr.create;
	return function(config, defaultType) {
		if (config.xclass) {
			var cls = Ext.ns(config.xclass);
			return new cls(config);
		} else {
			return uber.apply(this, arguments);
		}
	}
}();

Ext.widget = Ext.ComponentMgr.create;

Ext.create = function(cls, config) {
	if (Ext.isString(cls)) {
		var c = resolve(cls);
		return new c(config);
	} else {
		return Ext.widget.apply(Ext, arguments);
	}
};

// --- Ext.ClassManager

Ext.ClassManager = Ext.apply(Ext.ClassManager, {
	get: function(name) {
		return resolve(name, true);
	}
});

})(); // closure

// Defer
Ext.Function.defer = function(fn, millis, obj, args, appendArgs) {
	Function.defer.apply(fn, Array.prototype.slice.call(arguments, 1));
};

// Date

Ext.ns('Ext.Date');

Ext.Date.format = function (date, format) {
	return date && date.format(format) || undefined;
};

Ext.Date.isEqual = function (date1,date2){
	if (date1 && date2) {
		if (date1.ignoreTime && date2.ignoreTime) {
			return Ext.Date.format(date1, 'Ymd') === Ext.Date.format(date2, 'Ymd');
		} else {
			return (date1.getTime() === date2.getTime());
		}
	} else {
		return !(date1 || date2);
	}
};

// String

Ext.ns('Ext.String');

Ext.String.format = function() {
	return String.format.apply(String, arguments);
};


// --- Mixed collection ---

(function(p) {
	p.sortByKey = p.keySort;
})(Ext.util.MixedCollection.prototype);


// --- QuickTips: use only Ext4 manager, dismiss Ext3's entirely ---

if (Ext.QuickTips.isEnabled()) {
	Ext.QuickTips.disable();
	Ext4.tip.QuickTipManager.enable();
}
Ext.QuickTips = Ext4.tip.QuickTipManager;
Ext4.require('Eoze.Ext3.CompatibilityFixes');


// --- Standard functions ---

Ext.copyTo(Ext4, Ext, [
	'apply',
	'applyIf',
	// Copy
	'copyTo',
	// Clone
	'clone'
]);

// --- Ext3 & 4 window z-index conflicts ---

// Use Ext4.WindowManager
Ext.WindowMgr = Ext4.WindowManager;

// Ext4.WindowManager rely on the presence of isComponent
Ext.Component.prototype.isComponent = true;

// setZIndex must return the next index to be used
Ext.Window.prototype.setZIndex = (function() {
	var uber = Ext.Window.prototype.setZIndex;
	return function(index) {
		uber.apply(this, arguments);
		return Ext.isDefined(this.lastZIndex) ? this.lastZIndex : index + 10;
	}
})();

// A window ghost be use the same z-index as the window (which has probably just
// been brought to front by startDrag).
Ext.Window.DD.prototype.startDrag = Ext.Function.createSequence(Ext.Window.DD.prototype.startDrag, function() {
	var w = this.win,
		g = w.activeGhost;
	if (g) {
		g.setStyle('z-index', w.el.getZIndex());
	}
});

// The window should be brought to front before resize
Ext.Window.prototype.beforeResize = Ext.Function.createSequence(Ext.Window.prototype.beforeResize, function () {
	this.toFront(false);
});

// Fix menu z-index
Ext.menu.Menu.prototype.zIndex = 60000;
// Fix combo list z-index
Ext.form.ComboBox.prototype.getZIndex = function(listParent){
	listParent = listParent || Ext.getDom(this.getListParent() || Ext.getBody());
	var zindex = parseInt(Ext.fly(listParent).getStyle('z-index'), 10);
	if(!zindex){
		zindex = this.getParentZIndex();
	}
	// rx: changed 12000 to 62000
	return (zindex || 62000) + 5;
};


// --- Element ---

(function(p) {
	p.addCls = p.addClass;
	p.removeCls = p.removeClass;
})(Ext.Element.prototype);

} // end of compat patches
