/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 4 juin 2012
 */
(function() {

var spp = Ext.form.TextField.prototype,
	getAutoCreate = spp.getAutoCreate;
	
Ext.override(Ext.form.TextField, {
	
	/**
	 * @cfg {Integer|Boolean}
	 * Enforces maximum number of characters in the field. True to use {@link #maxLength},
	 * or an Integer to set a custom length.
	 */
	enforceMaxLength: undefined
	
	,getAutoCreate: function() {
		var cfg = getAutoCreate.call(this),
			eml = this.enforceMaxLength,
			ml = this.maxLength;
		if (eml) {
			var l = Ext.isNumber(eml) ? eml : ml;
			cfg = Ext.apply({
				maxlength: l
			}, cfg);
		}
		return cfg;
	}
	
});

Oce.deps.reg('Ext.form.Field.overrides');

})(); // closure