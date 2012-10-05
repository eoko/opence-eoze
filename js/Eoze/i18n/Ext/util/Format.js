/**
 * Overrides Ext.util.Format to add support for date format localization.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 4 oct. 2012
 */
Ext4.define('Eoze.i18n.Ext.util.Format', {
	override: 'Ext4.util.Format'
	
	/**
	 * @property {Boolean}
	 * If set to true, all date format will be automatically localized. If not,
	 * only formats using the localization modifier "@" will be localized
	 * (see {@link #localizeDateFormat}).
	 */
	,localizeAllDates: true

	// <locale>
	/**
	 * @property {Object} [localeDateFormats={}]
	 * Locale versions of date formats.
	 */
	// </locale>

	/**
	 * Eoze overrides this method to support localization of Date formats:
	 * see {@link #localizeDateFormat}.
	 */
	,date: function(v, format) {
		if (!v) {
			return "";
		}
		if (!Ext.isDate(v)) {
			v = new Date(Date.parse(v));
		}
		return Ext4.Date.dateFormat(v, this.localizeDateFormat(format || Ext4.Date.defaultFormat));
	}
	
	/**
	 * Localize the specified format... Or not, dependending on the value of the
	 * {@link #localizeAllDates} option, and optionnal specifiers in the format
	 * string.
	 * 
	 * The following two specifiers are supported:
	 * 
	 * -   **@** will force the localization of the format, independantly of the
	 *     value of the localizeAllDates option;
	 * 
	 * -  **@!** will prevent the localization of the format, independantly of the
	 *    value of the localizeAllDates option.
	 *    
	 * E.g.:
	 *     "@Y-m-d" // will always be localized
	 *     "@!H:i:s" // will never be localized
	 *     "H:i" // will be localized according to the localizeAllDates option
	 * 
	 * @param {String} format
	 */
	,localizeDateFormat: function(format) {
		if (format.substr(0,1) === '@') {
			if (format.substr(0,2) === '@!') {
				return format.substr(2);
			} else {
				return this.doLocalizeDateFormat(format.substr(1));
			}
		} else if (this.localizeAllDates) {
			return this.doLocalizeDateFormat(format);
		} else {
			return format;
		}
	}
	
	/**
	 * Localize the specified format.
	 * 
	 * As opposed to {@link #localizeDateFormat}, config options and modifiers are
	 * not taken into account. This method is used internally by 
	 * {@link #localizeDateFormat}.
	 * 
	 * @private
	 */
	,doLocalizeDateFormat: function(format) {
		var map = this.localeDateFormats;
		return map && map[format] || format;
	}
});