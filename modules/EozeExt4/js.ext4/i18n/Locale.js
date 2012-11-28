/**
 * The Locale exposes internationalization methods and its subclasses contains the
 * data that implements actual locales.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 4 oct. 2012
 */
Ext4.define('Eoze.i18n.Locale', {
	
	requires: [
		'Eoze.i18n.Ext.util.Format',
		'Eoze.i18n.Entry' // needed in postCreate function
	]
	
	/**
	 * @protected
	 * @template
	 * Dictionary data, that should be provided by subclasses.
	 */
	,dictionary: {}
	
	/**
	 * @public
	 * @todo Trying to mimick RoR i18n API:
	 *       http://guides.rubyonrails.org/i18n.html#internationalizing-your-application
	 */
	,translate: function(key, options) {
		var tags,
			count = 1;
			
		// forms
		// 1. {String} key
		// 2. {String} key, {Object} options
		// 3. {Object} options
		if (!Ext4.isString(key)) {
			options = key;
			key = options && (options.message || options.msg);
		}
		if (options) {
			tags = options.tags;
			count = options.count || count;
		}
		
		if (!Ext4.isArray(tags)) {
			tags = [tags];
		}
		
		var entry = this.lookup(key, tags) || key;
		
		if (Ext4.isString(entry)) {
			return entry;
		} else {
			// TODO
			return entry[this.isPlural(count) ? 'one' : 'other'];
		}
	}
	
	/**
	 * @protected
	 */
	,isPlural: function(count) {
		return count != 1;
	}

	/**
	 * @private
	 */
	,lookup: function(key, tags) {
		return this.dictionary[key];
	}
	
}, function() {
	// Configure default locale
	Deft.Injector.configure({
		locale: 'Eoze.i18n.Locale'
	});
	
	Eoze._ = function(text) {
		return Ext4.create('Eoze.i18n.Entry', {
			text: text
		});
	};
});