/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 19 juin 2012
 */
Oce.deps.wait('eo.form.FormattedTextField', function() {

eo.form.FormattedPhoneNumberField = Ext.extend(eo.form.FormattedTextField, {
	
	doFormat: function(s) {
		var ts = s.replace(/\s+/g, ''),
			r = '';
		// the 2 tests are redundant but the first is faster
		if (ts.length === 10 && /^\d{10}$/.test(ts)) {
			for (var i=0; i<5; i++) {
				r += ts.charAt(i*2) + ts.charAt(i*2+1) + " ";
			}
			return r.trimRight();
		}
		
		return s;
	}
	
	/**
	 * Disable and hide trigger, or enable and show it.
	 * @param {Boolean} available
	 */
	,setFormattingAvailable: function(available) {
		this.setHideTrigger(!available);
		this.setFormattingEnabled(available);
	}
});

Ext.reg('formattedphonefield', 'eo.form.FormattedPhoneNumberField');

}); // deps