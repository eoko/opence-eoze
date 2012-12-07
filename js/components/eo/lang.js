/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 17 nov. 2011
 */
Ext.ns('eo.lang');

eo.lang.isVowel = eo.lang.isVowel = function(c) {
	return /a|e|i|o|u|y/i.test(c);
};

(function() {

function get(n, g, l) {
	var e = g ? 'e' : '',
		s = l ? 's' : '';
	var left, right;
	if (n < 0) {
		return "moins " + get(-n, g, l);
	} else if (n <= 16) {
		switch (n) {
			case 0:  return "zéro";
			case 1:  return "un" + e;
			case 2:  return "deux";
			case 3:  return "trois";
			case 4:  return "quatre";
			case 5:  return "cinq";
			case 6:  return "six";
			case 7:  return "sept";
			case 8:  return "huit";
			case 9:  return "neuf";
			case 10: return "dix";
			case 11: return "onze";
			case 12: return "douze";
			case 13: return "treize";
			case 14: return "quatorze";
			case 15: return "quinze";
			case 16: return "seize";
		}
	} else if (n < 100) {
		if (n % 10 === 0) {
			switch (n) {
				case 20: return "vingt";
				case 30: return "trente";
				case 40: return "quarante";
				case 50: return "cinquante";
				case 60: return "soixante";
				case 70: return "soixante-dix";
				case 80: return "quatre-vingt" + s;
				case 90: return "quatre-vingt-dix";
			}
		} else {
			var units = n % 10,
				tens  = n - units,
				trait = units === 1 && tens < 80 ? ' et ' : '-';
			if (n > 70 && n < 80 || n > 90) {
				tens  -= 10;
				units += 10;
			}
			return get(tens, g, false) + trait + get(units, g, true);
		}
	} else if (n < 1000) {
		right = n % 100;
		left  = (n - right) / 100;
		return (left > 1 ? get(left, g, false) + ' ' : '') + 'cent' 
				+ (right === 0 ? (l ? 's' : '') : ' ' + get(right, g, true));
	} else if (n < 1000000) {
		right = n % 1000;
		left  = (n - right) / 1000;
		return (left > 1 ? get(left, g, false) + ' ' : '') + 'mille' 
				+ (right === 0 ? '' : ' ' + get(right, g, true));
	} else if (n < 1000000000) {
		right = n % 1000000;
		left  = (n - right) / 1000000;
		s = left > 1 ? 's' : '';
		return get(left, g, true) + ' million' + s 
				+ (right === 0 ? '' : ' ' + get(right, g, true));
	} else if (n < 1000000000*1000000) {
		right = n % 1000000000;
		left  = (n - right) / 1000000000;
		s = left > 1 ? 's' : '';
		return get(left, g, true) + ' milliard' + s + (right === 0 ? '' : ' ' + get(right, g, true));
	} else if (n <= 9007199254740991) { // http://ecma262-5.com/ELS5_HTML.htm#Section_8.5
		 // actual integer max is 9007199254740991 or -9007199254740991
		right = n % 1000000000;
		left  = (n - right) / 1000000000;
		s = left > 1 ? 's' : '';
		return get(left, g, true) + ' de milliards' + (right === 0 ? '' : ' et ' + get(right, g, true));
	}
	return n;
} // get()

eo.lang.intToWord = function(num, genre) {
	return get(num, genre && /f/i.test(genre), true);
};

})(); // closure
