/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 5 juin 2012
 */
(function() {

var spp = Ext.form.NumberField.prototype,
	initComponent = spp.initComponent,
	initEvents = spp.initEvents,
	onRender = spp.onRender;

/**
 * @class Ext.form.NumberField
 * @inheritdoc
 * 
 * @author Éric Ortega <eric@eoko.fr>
 * 
 * Overridden to:
 * 
 * - Add {@link #maxDecimalPrecision} option.
 * 
 * - Allow both "." and "," as {@link #maskRe input decimal separators}.
 */
Ext.override(Ext.form.NumberField, {
	
	/**
	 * @cfg {String/Object} [maxDecimalPrecision=undefined]
	 * @cfg {Integer} maxDecimalPrecision.integer The maximum number of digits in the integer part
	 * @cfg {Integer} maxDecimalPrecision.decimal The maximum number of digits in the decimal part
	 * 
	 * This option will configure the {@link #regex} and {@link #regexText}, and the 
	 * {@link #maxValue} options to validate a decimal number with a maximum number 
	 * of digits in the integer part and the decimal part.
	 * 
	 * The options will be configured only if they are the default ones (i.e. the same
	 * as the prototype's ones).
	 * 
	 * A String of the form "maxInt,maxDec" can be used.
	 * 
	 * Empty values for maxInteger or maxDecimal (i.e. null or undefined) are interpreted
	 * as no limit.
	 */

	// i18n
	/**
	 * @cfg
	 * Text that will be used by {@link #maxDecimalPrecision} as the field error text when the
	 * regex fails. If and only if {@link #maxDecimalPrecision.decimal} is 0, then
	 * {@link #maxDecimalPrecisionZeroText} will be used instead.
	 */
	maxDecimalPrecisionText: "La valeur maximale de ce champ est {0} avec une précision de {1} "
			+ "chiffres après la virgule"
	/**
	 * @cfg
	 * Text that will be used by {@link #maxDecimalPrecision} instead of 
	 * {@link #maxDecimalPrecisionText}, if {@link #maxDecimalPrecision.decimal} is
	 * exactly 0 (that is, no decimals are allowed).
	 */
	,maxDecimalPrecisionZeroText: "La valeur maximale de ce champ est {0}"
	
	// private
	,initComponent: function() {
		
		var mdp = this.maxDecimalPrecision;
		
		if (Ext.isString(mdp)) {
			var parts = mdp.split(',');
			mdp = {
				integer: parts[0]
				,decimal: parts[1]
			};
		}
		
		if (Ext.isObject(mdp)) {
			var maxInt = mdp.integer,
				maxDec = mdp.decimal || mdp.decimals,
				msg = 'maxDecimalPrecisionText';

			if (!this.regex || this.regex === spp.regex) {
				// build regex string
				var res = '^(?:';
				if (this.allowNegative) {
					res += '-?';
				}
				res = '\\\d';
				if (!Ext.isEmpty(maxInt)) {
					res += '{0,' + maxInt + '}'
				} else {
					res += '*'
				}
				res += ')?';
				if (!Ext.isEmpty(maxDec)) {
					if (maxDec > 0) {
						res += '(?:[,.]\\\d{0,' + maxDec + '})?';
					} else { // decimals are forbidden
						msg = 'maxDecimalPrecisionZeroText';
					}
				} else {
					res += '(?:[,.]\\\d*)?'
				}
				res += '$';
				// Ext3
				this.regex = new RegExp(res);
			}
			
			// replace the default text
			var maxValue = this.makeMaxValue(maxInt, maxDec),
				hsMaxValue = maxValue.toString().replace('.', this.decimalSeparator);
			if (this.regexText === spp.regexText) {
				this.regexText = String.format(this[msg], hsMaxValue, maxDec);
			}

			// maxValue
			if (this.maxValue === spp.maxValue) {
				this.maxValue = maxValue;
			}
		}
		
		initComponent.call(this);
	}

	// overridden to allow for both "." and "," as input decimal separator
	// private
    ,initEvents: function() {
        var allowed = this.baseChars + '';
        if (this.allowDecimals) {
            // allowed += this.decimalSeparator;
			// allow for both "." and "," as input decimal separator
            allowed += ',.';
        }
        if (this.allowNegative) {
            allowed += '-';
        }
        allowed = Ext.escapeRe(allowed);
        this.maskRe = new RegExp('[' + allowed + ']');
        if (this.autoStripChars) {
            this.stripCharsRe = new RegExp('[^' + allowed + ']', 'gi');
        }
        
        Ext.form.NumberField.superclass.initEvents.call(this);
    }

	// private
	,makeMaxValue: function(maxInt, maxDec) {
		return (Math.pow(10, maxInt) - 1/Math.pow(10, maxDec)).toFixed(maxDec);
	}
});

Oce.deps.reg('Ext.form.Field.overrides');

})(); // closure