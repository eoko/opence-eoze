/**
 * Copyright (C) 2012 Eoko
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
 * @copyright Copyright (C) 2012 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

/**
 * Ajax (i.e. client side) route.
 *
 * @since 2012-12-17 14:57
 */
Ext.define('eo.AjaxRouter.Route', {

	requires: ['eo.AjaxRouter']

	/**
	 * @property {RegExp|RegExp[]} regex
	 */

	/**
	 * @property {String[]} paramMap
	 * Maps param name to their extracted index in the regex.
	 */

	/**
	 * Creates a new Route instance.
	 *
	 * Either `spec`, or `regex` must be specified.
	 *
	 * @param {Object} config
	 * @param {String/String[]} config.name
	 * @param {String[]} [config.alias]
	 * @param {String} [config.spec]
	 * @param {String} [config.regex]
	 * @param {Object} config.constraints RegExp associated to the param name in the spec.
	 */
	,constructor: function(config) {
		Ext.apply(this, config);

		// name as array
		if (Ext.isArray(this.name)) {
			this.aliases = this.name;
			this.name = this.aliases[0];
		} else {
			if (this.aliases && this.aliases.indexOf(this.name) === -1) {
				this.aliases.unshift(this.name);
			}
		}

		if (this.regex) {
			this.matcher = new this.Matcher(this.regex, this.paramMap);
		} else {
			var matcher;
			if (Ext.isArray(this.spec)) {
				matcher = this.matcher = new this.Matcher([], []);
				Ext.each(this.spec, function(spec) {
					matcher.add.apply(matcher, this.regexFromSpec(spec))
				}, this)
			} else {
				var args = this.regexFromSpec(this.spec);
				matcher = new this.Matcher(args[0], args[1]);
			}
			this.matcher = matcher;
		}
	}

	/**
	 * Gets the aliases of the route, as an array of strings.
	 *
	 * @return {String[]}
	 */
	,getAliases: function() {
		return this.aliases || this.name && [this.name] || [];
	}

	,Matcher: Ext.define(null, {
		constructor: function(regex, map) {
			if (Ext.isArray(regex)) {
				this.test = this.testArray;
				this.exec = this.execArray;
			} else {
				if (Ext.isString(regex)) {
					regex = new RegExp(regex);
				}
			}
			this.regex = regex;
			this.map = map;
		}
		/**
		 * @param {RegExp} regex
		 * @param {String[]} map
		 */
		,add: function(regex, map) {
			if (this.regex) {
				if (!Ext.isArray(this.regex)) {
					this.regex = [this.regex];
					this.map = [this.map];
				}
				this.regex.push(regex);
				this.map.push(map);
			} else {
				this.regex = [regex];
				this.map = [map];
			}
		}
		/**
		 * @return {String}
		 */
		,toString: function() {
			return Ext.isArray(this.regex) ? this.regex[0] : this.regex;
		}
		/**
		 * @param {String} s
		 * @return {String[]/undefined}
		 */
		,test: function(s) {
			return this.regex.test(s);
		}
		/**
		 * @param {String} s
		 * @return {String[]/undefined}
		 */
		,exec: function(s) {
			return this.mapParams(this.regex.exec(s));
		}
		/**
		 * @param {String} s
		 * @return {String[]/undefined}
		 * @private
		 */
		,testArray: function(s) {
			return undefined !== Ext.each(this.regex, function(re) {
				return !re.test(s);
			});
		}
		/**
		 * @param {String} s
		 * @return {String[]/undefined}
		 * @private
		 */
		,execArray: function(s) {
			var matches,
				maps = this.map,
				map;
			Ext.each(this.regex, function(re, i) {
				matches = re.exec(s);
				map = maps[i];
				return !matches;
			});
			return this.mapParams(matches, map);
		}
		,mapParams: function(matches, map) {
			if (!map) {
				map = this.map;
			}
			if (!map) {
				return matches;
			}
			if (!matches) {
				return;
			}
			Ext.each(map, function(param, i) {
				matches[param] = matches[i+1];
			});
			return matches;
		}
	})

	/**
	 * @param {String} spec
	 * @return {Array} [regExp, paramMap]
	 * @private
	 */
	,regexFromSpec: function(spec) {
		var regex = Ext.escapeRe(spec),
			constraints = this.constraints || {},
			map = Ext.create('Ext.util.MixedCollection'),
			defaultConstraint = '[^/?=&]+'

		function constraint(param) {
			var constraint = constraints[param] || defaultConstraint;
			if (constraint instanceof RegExp) {
				// remove the delimiters
				constraint = constraint.toString().slice(1,-1);
			}
			return constraint;
		}

		// optional placeholder
		regex = regex.replace(/\\\[\\(\/)?:(\w+)/g, function(match, slash, paramName, offset) {
			map.add(offset, paramName);
			return Ext.String.format('(?:{0}({1})?', slash, constraint(paramName));
		});
		regex = regex.replace(/\\\]/g, ')?');

		// required placeholder
		regex = regex.replace(/:(\w+)/g, function(match, paramName, offset) {
			map.add(offset, paramName);
			return Ext.String.format('({0})', constraint(paramName));
		});

		map.sortByKey('ASC', function(a, b) { return a - b; });
		var paramMap = [];
		map.each(function(name) {
			paramMap.push(name);
		});

		var re = new RegExp('^' + regex + '$');

		return [re, paramMap];
	}

	,toString: function() {
		return this.name || this.matcher.toString();
	}

	/**
	 * Assembles the route with the given params.
	 *
	 * @return {String}
	 */
	,assemble: function(params) {
		var spec = this.spec;
		if (Ext.isArray(spec)) {
			spec = spec[0];
		}
		if (!spec) {
			throw new Error('Missing spec for route ' + this.toString());
		}
		// default params
		params = params || {};
		// optional params
		spec = spec.replace(/\[\/:([^/:\]]+)\]/g, function(s, key) {
			var v = params[key];
			return Ext.isDefined(v) ? '/' + v : '';
		});
		// required params
		spec = spec.replace(/:([^/:]+)/g, function(s, key) {
			var v = params[key];
			if (!Ext.isDefined(v)) {
				throw new Error('Missing required route param: ' + key);
			}
			return v;
		});
		// add base prefix
		return '#!/' + spec;
	}

	/**
	 * Tests the specified path.
	 *
	 * @param {String} path
	 * @return {String[]} matches
	 */
	,test: function(path) {
		return this.matcher.exec(path);
	}

	/**
	 * Executes the associated action (handler) if the specified path matches the route.
	 *
	 * @param {String} path
	 * @param {Function} [callback]
	 * @param {Object} [scope]
	 *
	 * @return {Boolean} True if the route matches the tested path.
	 */
	,run: function(path, callback, scope) {
		var matches = this.test(path);
		if (!matches) {
			return false;
		}

//		console.debug('Matched route: ' + this.toString());

		this.doRun(matches, callback, scope);

		return true;
	}

	/**
	 * @protected
	 */
	,doRun: function(matches, callback, scope) {
		var fn = this.handler || this.fn;
		fn.call(this.scope || this, matches, callback, scope);
	}

});
