/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 19 janv. 2012
 */

Ext.ns('Oce.form');

/**
 * A display field that converts input value to a given date format (or
 * {@link eo.Locale#getDateFormat} if none is configured). 
 * 
 * Accepted input values for this field are either {@link Date} objects, 
 * or {@link String}s that complies with any of this formats:
 * 
 * -   the {@link #format} config option or {@link eo.Local#getDateFormat} if none
 * -   `'Y-m-d H:i:s'`
 * -   `'Y-m-dTH:i:s'`
 * 
 * @xtype datedisplayfield
 */
Oce.form.DateDisplayField = Ext.extend(Ext.form.DisplayField, {

	/**
	 * @cfg {String} format
	 */
	format: undefined

	,setValue: function(date) {
		var format = this.format || eo.Locale.getDateFormat(),
			v;
		if (date instanceof Date) {
			v = date.format(format);
		} else {
			date = Date.parseDate(date, format)
					|| Date.parseDate(date, 'Y-m-d H:i:s')
					|| Date.parseDate(date, 'Y-m-dTH:i:s');
			if (date instanceof Date) {
				v = date.format(format);
			}
		}
		return Oce.form.DateDisplayField.superclass.setValue.call(this, v);
	}
});

Ext.reg('datedisplayfield', Oce.form.DateDisplayField);
