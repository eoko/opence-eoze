/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 15 nov. 2011
 */
eo.deps.waitIn('eo.form.contact', 'config', function(NS, ns) {

NS.locale = function(key, num, genre, replacements) {
	
	// use arguments
	// this is overriden if params are given in the key
	if (Ext.isString(num)) {
		replacements = genre;
		genre = num;
		num = undefined;
	} else if (Ext.isObject(num)) {
		replacements = num;
		genre = num = undefined;
	}
	
	// extract params from key
	var matches = /^(.+?)\s*{(.+)}$/.exec(key);
	if (matches) {
		key = matches[1];
		Ext.each(matches[2].split(','), function(param) {
			param = param.trim();
			
			if (param.substr(0,2) === 'g:') {
				genre = NS.locale.genre(param.substr(2));
				return;
			}
			
			switch (param) {
				case 'f':genre = 'f';break;
				case 'm':genre = 'm';break;
				case '+':num = 2;break;
			}
		});
	}
	
	var text = NS.texts[key];
	
	if (text) {
		text = text.replace(/^{\w{1,3}?:\w?}/g, '');
		if (num > 1) {
			text = text.replace(/{p}(.+?){\/p}/g, '$1');
			text = text.replace(/{s}.+?{\/s}/g, '');
			text = text.replace(/{p\/}/g, 's');
		} else {
			text = text.replace(/{s}(.+?){\/s}/g, '$1');
			text = text.replace(/{p}.+?{\/p}/g, '');
			text = text.replace(/{p\/}/g, '');
		}
		if (genre === 'f' || genre === 'F') {
			text = text.replace(/{f}(.+?){\/f}/g, '$1');
			text = text.replace(/{m}.+?{\/m}/g, '');
		} else {
			text = text.replace(/{m}(.+?){\/m}/g, '$1');
			text = text.replace(/{f}.+?{\/f}/g, '');
		}
		// Replacements
		if (replacements) {
			Ext.iterate(replacements, function(key, value) {
				value = '' + value;
				if (value.substr(0,1) === ':') {
					value = NS.locale(value.substr(1), num, genre);
				}
				text = text.replace(new RegExp('{:' + key + '}', 'g'), value);
				text = text.replace(new RegExp('{:' + key + ':lc}', 'g'), value.toLowerCase());
			});
		}
		// Conditionnal words
		var re = /{de}\s?(.)/;
		while ((matches = re.exec(text))) {
			if (eo.lang.isVowel(matches[1])) {
				text = text.replace(re, "d'$1");
			} else {
				text = text.replace(re, "de $1");
			}
		}
	} else {
		eo.warn('Missing locale for key: ' + key);
		return key;
	}
	return text;
};

NS.locale.genre = function(key) {
	return /^{(?:nf|gnf|gn:f|n:f)[^}]*:}/.test(NS.texts[key]) ? 'f' : 'm';
};

Oce.deps.reg('locale', ns);
	
}); // deps