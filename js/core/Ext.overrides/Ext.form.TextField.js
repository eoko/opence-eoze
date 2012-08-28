/**
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 4 juin 2012
 */
(function() {

var spp = Ext.form.TextField.prototype,
	getAutoCreate = spp.getAutoCreate;
	
/**
 * @class Ext.form.TextField
 * @author Éric Ortega <eric@eoko.fr>
 * 
 * Overriden to implement {@link #enforeMaxLength}.
 */
Ext.override(Ext.form.TextField, {
	
	/**
	 * @cfg {Integer/Boolean}
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

// This is a try to enforce maskRe on the whole value, not only the next input char
// 
//    ,filterKeys: function(e){
//        if(e.ctrlKey){
//            return;
//        }
//        var k = e.getKey();
//        if(Ext.isGecko && (e.isNavKeyPress() || k == e.BACKSPACE || (k == e.DELETE && e.button == -1))){
//            return;
//        }
//        var cc = String.fromCharCode(e.getCharCode());
//        if(!Ext.isGecko && e.isSpecialKey() && !cc){
//            return;
//        }
//		if (this.fullMaskRe) {
//			cc = this.getValue() + cc;
//		}
//        if(!this.maskRe.test(cc)){
//            e.stopEvent();
//        }
//    }
	
});

Oce.deps.reg('Ext.form.Field.overrides');

})(); // closure