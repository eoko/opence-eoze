/**
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 4 juin 2012
 */
(function() {

var spp = Ext.form.TextField.prototype,
	getAutoCreate = spp.getAutoCreate,
	focus = spp.focus,
	preFocus = spp.preFocus;
	
/**
 * @class Ext.form.TextField
 * @author Éric Ortega <eric@eoko.fr>
 *
 * Overridden for the following:
 *
 * - implements {@link #enforeMaxLength}.
 * - forces focus(false) to prevent selection
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

	/**
	 * Overridden to enforce preventing selection on focus if the first argument is false.
	 * @see {Ext.form.Field#focus}
	 */
	,focus: function(selectText) {
		if (selectText === false) {
			this.preventSelectOnFocus = true;
		}
		focus.apply(this, arguments);
	}

	/**
	 * Overridden to enforce preventing selection on focus when focus is called with
	 * argument select = false.
	 * @see {Ext.form.TextField#focus}
	 * @private
	 */
	,preFocus: function() {
		if (this.selectOnFocus && this.preventSelectOnFocus) {
			this.selectOnFocus = false;
			this.preventSelectOnFocus = false;
			preFocus.apply(this, arguments);
			this.selectOnFocus = true;
		} else {
			preFocus.apply(this, arguments);
		}
	}
	
});

Oce.deps.reg('Ext.form.Field.overrides');

})(); // closure
