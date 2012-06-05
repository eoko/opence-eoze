/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 5 juin 2012
 */
(function() {

var spp = Ext.form.NumberField.prototype,
	initComponent = spp.initComponent,
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
	 * @cfg
	 * @inheritdoc
	 * Allow for both "," and "." as input decimal separators.
	 */
	maskRe: /[0123456789,.\-]/
	
	/**
	 * @cfg {String/Object} [maxDecimalPrecision=undefined]
	 * @cfg {Integer} maxDecimalPrecision.maxInteger The maximum number of digits in the integer part
	 * @cfg {Integer} maxDecimalPrecision.maxDecimal The maximum number of digits in the decimal part
	 * 
	 * This option will configure the {@link #regex} and {@link #regexText} options to validate
	 * a decimal number with a maximum number of digits in the integer part and the decimal part.
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
	 * regex fails. If and only if {@link #maxDecimalPrecision.maxDecimal} is 0, then
	 * {@link #maxDecimalPrecisionZeroText} will be used instead.
	 */
	,maxDecimalPrecisionText: "La valeur maximale de ce champ est {0} avec une précision de {1} "
			+ "chiffres après la virgule"
	/**
	 * @cfg
	 * Text that will be used by {@link #maxDecimalPrecision} instead of 
	 * {@link #maxDecimalPrecisionText}, if {@link #maxDecimalPrecision.maxDecimal} is
	 * exactly 0 (that is, no decimals are allowed).
	 */
	,maxDecimalPrecisionZeroText: "La valeur maximale de ce champ est {0}"
	
	// private
	,initComponent: function() {
		
		var mdp = this.maxDecimalPrecision;
		
		if (Ext.isString(mdp)) {
			var parts = mdp.split(',');
			mdp = {
				maxInteger: parts[0]
				,maxDecimals: parts[1]
			};
		}
		
		if (Ext.isObject(mdp)) {
			var maxInt = mdp.maxInteger || mdp.maxInt,
				maxDec = mdp.maxDecimal || mdp.maxDecimals || mdp.maxDec,
				msg = 'maxDecimalPrecisionText';

			if (!this.regex || this.regex === spp.regex) {
				// build regex string
				var res = '^(?:\d';
				if (!Ext.isEmpty(maxInt)) {
					res += '{0,' + maxInt + '}'
				} else {
					res += '*'
				}
				res += ')?';
				if (!Ext.isEmpty(maxDec)) {
					if (maxDec > 0) {
						res += '(?:[,.]\d{0,' + maxDec + '})?';
					} else { // decimals are forbidden
						msg = 'maxDecimalPrecisionZeroText';
					}
				} else {
					res += '(?:[,.]\d*)?'
				}
				res += '$';
				// Ext3
				this.regex = new RegExp(res);
			}
			
			// replace the default text
			if (this.regexText === spp.regexText) {
				var maxValue = this.makeMaxValue(maxInt, maxDec);
				this.regexText = String.format(this[msg], maxValue, maxDec);
			}
		}
		
		initComponent.call(this);
	}

	// private
	,makeMaxValue: function(maxInt, maxDec) {
		var r = '', i;
		for (i=0; i<maxInt; i++) {
			r += '9';
		}
		if (!Ext.isEmpty(maxDec) && maxDec > 0) {
			r += this.decimalSeparator || '.'; // don't know... just to be sure
			for (i=0; i<maxDec; i++) {
				r += '9';
			}
		}
		return r;
	}
});

Oce.deps.reg('Ext.form.Field.overrides');

})(); // closure