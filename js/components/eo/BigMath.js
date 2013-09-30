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
 * Math functions for arbitrary length and precision number.
 *
 * @since 2012-12-10 11:00
 */
Ext.define('eo.BigMath', {
}, function() {

	Ext.apply(this, {
		/**
		 * Modulo for arbitrary length divident.
		 *
		 * @param {String/Number} divident
		 * @param {String/Number} divisor
		 *
		 * @return {String}
		 *
		 * @class eo.BigMath
		 * @static
		 */
		modulo: function(divident, divisor) {
			var cDivident = '',
				cRest = '';
			for (var i=0,l=divident.length; i<l; i++) {
				var cChar = divident[i];
				var cOperator = cRest + '' + cDivident + '' + cChar;

				if ( cOperator < parseInt(divisor) ) {
					cDivident += '' + cChar;
				} else {
					cRest = cOperator % divisor;
					if ( cRest == 0 ) {
						cRest = '';
					}
					cDivident = '';
				}

			}
			cRest += '' + cDivident;
			if (cRest == '') {
				cRest = 0;
			}
			return cRest;
		}
	});

});
