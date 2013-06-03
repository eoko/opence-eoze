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
 * @inheritdoc Ext.ComponentQuery
 *
 * This override fixes the matcher regex to accept square brackets in attribute queries.
 *
 * E.g.
 *     [attribute[x].y=value]
 *
 * @since 2013-05-21 13:57
 */
Ext4.define('Eoze.Ext.ComponentQuery.AcceptingSquareBrackets', {
	override: 'Ext.ComponentQuery'
}, function() {
	var Ext = Ext4;

	// <rx> Added:
	/**
	 * Ensures that the version is the same as the one against which has been applied the patch.
	 * Unfortunately, we have to copy a huge chunk of code just to change one of the regex, and
	 * the ComponentQuery class is quite fundamental... That's why it seems sensible to ensure
	 * that we don't override changes coming with new versions.
	 */
	(function ensureExtVersionHasNotChanged() {
		var version = Ext.getVersion(),
			testedWorkingVersions = [
				'4.2.0.663',
				'4.2.1.883' // not entirely check to say the truth
			],
			untestedVersion = true;

		Ext.each(testedWorkingVersions, function(testVersion) {
			if (version.equals(testVersion)) {
				untestedVersion = false;
			}
			return untestedVersion;
		});

		if (untestedVersion) {
			debugger
			Ext4.Logger.warn(
				'Please ensure that the code of this class has not changed with this version.'
					+ ' See documentation of this function above.'
			);
		}
	})();
	// </rx>

	var cq = this,
		domQueryOperators = Ext.dom.Query.operators,

		// A function source code pattern with a placeholder which accepts an expression which yields a truth value when applied
		// as a member on each item in the passed array.
		filterFnPattern = [
			'var r = [],',
			'i = 0,',
			'it = items,',
			'l = it.length,',
			'c;',
			'for (; i < l; i++) {',
			'c = it[i];',
			'if (c.{0}) {',
			'r.push(c);',
			'}',
			'}',
			'return r;'
		].join(''),

		// Filters the passed candidate array and returns only items which match the passed xtype
		filterByXType = function(items, xtype, shallow) {
			if (xtype === '*') {
				return items.slice();
			}
			else {
				var result = [],
					i = 0,
					length = items.length,
					candidate;
				for (; i < length; i++) {
					candidate = items[i];
					if (candidate.isXType(xtype, shallow)) {
						result.push(candidate);
					}
				}
				return result;
			}
		},

		// Filters the passed candidate array and returns only items which have the passed className
		filterByClassName = function(items, className) {
			var result = [],
				i = 0,
				length = items.length,
				candidate;
			for (; i < length; i++) {
				candidate = items[i];
				if (candidate.hasCls(className)) {
					result.push(candidate);
				}
			}
			return result;
		},

		// Filters the passed candidate array and returns only items which have the specified property match
		filterByAttribute = function(items, property, operator, compareTo) {
			var result = [],
				i = 0,
				length = items.length,
				mustBeOwnProperty,
				presenceOnly,
				candidate, propValue,
				j, propLen;

			// Prefixing property name with an @ means that the property must be in the candidate, not in its prototype
			if (property.charAt(0) === '@') {
				mustBeOwnProperty = true;
				property = property.substr(1);
			}
			if (property.charAt(0) === '?') {
				mustBeOwnProperty = true;
				presenceOnly = true;
				property = property.substr(1);
			}

			for (; i < length; i++) {
				candidate = items[i];

				// Check candidate hasOwnProperty is propName prefixed with a bang.
				if (!mustBeOwnProperty || candidate.hasOwnProperty(property)) {

					// pull out property value to test
					propValue = candidate[property];

					if (presenceOnly) {
						result.push(candidate);
					}
					// implies property is an array, and we must compare value against each element.
					else if (operator === '~=') {
						if (propValue) {
							//We need an array
							if (!Ext.isArray(propValue)) {
								propValue = propValue.split(' ');
							}

							for (j = 0, propLen = propValue.length; j < propLen; j++) {
								if (domQueryOperators[operator](Ext.coerce(propValue[j], compareTo), compareTo)) {
									result.push(candidate);
									break;
								}
							}
						}
					} else if (!compareTo ? !!candidate[property] : domQueryOperators[operator](Ext.coerce(propValue, compareTo), compareTo)) {
						result.push(candidate);
					}
				}
			}
			return result;
		},

		// Filters the passed candidate array and returns only items which have the specified itemId or id
		filterById = function(items, id) {
			var result = [],
				i = 0,
				length = items.length,
				candidate;
			for (; i < length; i++) {
				candidate = items[i];
				if (candidate.getItemId() === id) {
					result.push(candidate);
				}
			}
			return result;
		},

		// Filters the passed candidate array and returns only items which the named pseudo class matcher filters in
		filterByPseudo = function(items, name, value) {
			return cq.pseudos[name](items, value);
		},

		// Determines leading mode
		// > for direct child, and ^ to switch to ownerCt axis
		modeRe = /^(\s?([>\^])\s?|\s|$)/,

		// Matches a token with possibly (true|false) appended for the "shallow" parameter
		tokenRe = /^(#)?([\w\-]+|\*)(?:\((true|false)\))?/,

		matchers = [{
			// Checks for .xtype with possibly (true|false) appended for the "shallow" parameter
			re: /^\.([\w\-]+)(?:\((true|false)\))?/,
			method: filterByXType
		}, {
			// checks for [attribute=value], [attribute^=value], [attribute$=value], [attribute*=value], [attribute~=value], [attribute%=value], [attribute!=value]
			// Allow [@attribute] to check truthy ownProperty
			// Allow [?attribute] to check for presence of ownProperty
			// <rx> Changed:
			// re: /^(?:\[((?:@|\?)?[\w\-\$]*[^\^\$\*~%!])\s?(?:(=|.=)\s?['"]?(.*?)["']?)?\])/,
			// To:
			re: /^(?:\[((?:@|\?)?[\w\-\$]*[^\^\$\*~%!])\s?(?:(=|.=)\s?['"]?(.*)["']?)?\])/,
			// </rx>
			method: filterByAttribute
		}, {
			// checks for #cmpItemId
			re: /^#([\w\-]+)/,
			method: filterById
		}, {
			// checks for :<pseudo_class>(<selector>)
			re: /^\:([\w\-]+)(?:\(((?:\{[^\}]+\})|(?:(?!\{)[^\s>\/]*?(?!\})))\))?/,
			method: filterByPseudo
		}, {
			// checks for {<member_expression>}
			re: /^(?:\{([^\}]+)\})/,
			method: filterFnPattern
		}];

	this.parse = function(selector) {
		var operations = [],
			length = matchers.length,
			lastSelector,
			tokenMatch,
			matchedChar,
			modeMatch,
			selectorMatch,
			i, matcher, method;

		// We are going to parse the beginning of the selector over and
		// over again, slicing off the selector any portions we converted into an
		// operation, until it is an empty string.
		while (selector && lastSelector !== selector) {
			lastSelector = selector;

			// First we check if we are dealing with a token like #, * or an xtype
			tokenMatch = selector.match(tokenRe);

			if (tokenMatch) {
				matchedChar = tokenMatch[1];

				// If the token is prefixed with a # we push a filterById operation to our stack
				if (matchedChar === '#') {
					operations.push({
						method: filterById,
						args: [Ext.String.trim(tokenMatch[2])]
					});
				}
				// If the token is prefixed with a . we push a filterByClassName operation to our stack
				// FIXME: Not enabled yet. just needs \. adding to the tokenRe prefix
				else if (matchedChar === '.') {
					operations.push({
						method: filterByClassName,
						args: [Ext.String.trim(tokenMatch[2])]
					});
				}
				// If the token is a * or an xtype string, we push a filterByXType
				// operation to the stack.
				else {
					operations.push({
						method: filterByXType,
						args: [Ext.String.trim(tokenMatch[2]), Boolean(tokenMatch[3])]
					});
				}

				// Now we slice of the part we just converted into an operation
				selector = selector.replace(tokenMatch[0], '');
			}

			// If the next part of the query is not a space or > or ^, it means we
			// are going to check for more things that our current selection
			// has to comply to.
			while (!(modeMatch = selector.match(modeRe))) {
				// Lets loop over each type of matcher and execute it
				// on our current selector.
				for (i = 0; selector && i < length; i++) {
					matcher = matchers[i];
					selectorMatch = selector.match(matcher.re);
					method = matcher.method;

					// If we have a match, add an operation with the method
					// associated with this matcher, and pass the regular
					// expression matches are arguments to the operation.
					if (selectorMatch) {
						operations.push({
							method: Ext.isString(matcher.method)
								// Turn a string method into a function by formatting the string with our selector matche expression
								// A new method is created for different match expressions, eg {id=='textfield-1024'}
								// Every expression may be different in different selectors.
								? Ext.functionFactory('items', Ext.String.format.apply(Ext.String, [method].concat(selectorMatch.slice(1))))
								: matcher.method,
							args: selectorMatch.slice(1)
						});
						selector = selector.replace(selectorMatch[0], '');
						break; // Break on match
					}
					// Exhausted all matches: It's an error
					if (i === (length - 1)) {
						Ext.Error.raise('Invalid ComponentQuery selector: "' + arguments[0] + '"');
					}
				}
			}

			// Now we are going to check for a mode change. This means a space
			// or a > to determine if we are going to select all the children
			// of the currently matched items, or a ^ if we are going to use the
			// ownerCt axis as the candidate source.
			if (modeMatch[1]) { // Assignment, and test for truthiness!
				operations.push({
					mode: modeMatch[2]||modeMatch[1]
				});
				selector = selector.replace(modeMatch[0], '');
			}
		}

		//  Now that we have all our operations in an array, we are going
		// to create a new Query using these operations.
		return new cq.Query({
			operations: operations
		});
	};
});
