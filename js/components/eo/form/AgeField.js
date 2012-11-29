/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 23 févr. 2012
 */
Ext.ns('eo.form');

/**
 * @xtype agefield
 */
eo.form.AgeField = Ext.extend(Ext.form.TextField, {
	
	// private
	age: null
	
	/**
	 * @cfg {Boolean} returnObject
	 * 
	 * True to have an {@link Object} returned by the {@link #getValue} method.
	 * 
	 * The Object will be of the form:
	 * 
	 *     {
	 *         years: x,
	 *         months: y,
	 *         days: z
	 *     }
	 */
	,returnObject: false
	
	/**
	 * @cfg {Object} texts
	 * The texts to be displayed to the user.
	 */
	,texts: {
		year: ['an', 'ans']
		,month: 'mois'
		,day: ['jour', 'jours']
	}

	/**
	 * @cfg {RegExp} yearRe
	 * The regular expression that will be used to test if the input is a length in years.
	 */
	,yearRe: /^(a|y)/i
	/**
	 * @cfg {RegExp} monthRe
	 * The regular expression that will be used to test if the input is a length in months.
	 */
	,monthRe: /^m/i
	/**
	 * @cfg {RegExp} dayRe
	 * The regular expression that will be used to test if the input is a length in days.
	 */
	,dayRe: /^d|j/i
	
	// i18n
	/**
	 * @cfg {String/null} The html to be displayed in the help tooltip. Set to null to 
	 * disable help tooltip.
	 */
	,helpHtml: "<h1>Aide</h1>"
			+ "<p>Utilisez les lettres 'a', 'm' et 'j' pour indiquer les années, jours "
			+ "et mois, ou indiquez les en toutes lettres.</p>"
			+ "<p>Exemples&nbsp;:</p>"
			+ "<ul>"
			+ "<li>'1 2 3' pour 1 an 2 mois 3 jours</li>"
			+ "<li>'2 mois' ou '2m'</li>"
			+ "<li>'2 ans 3 jours' ou '2m3j'</li>"
			+ "</ul>"
			+ "<p>Vous pouvez également entrer dans l'ordre le nombre d'années, de jours "
			+ "et de mois, séparés par des espaces.</p>"
			+ "<p>Exemple&nbsp;:</p>"
			+ "<ul>"
			+ "<li>'1 2 3' pour 1 an 2 mois 3 jours</li>"
			+ "</ul>"
	
	,onRender: function() {
		eo.form.AgeField.superclass.onRender.apply(this, arguments);
		var html = this.helpHtml;
		if (html) {
			var w = this.el.wrap();
			this.resizeEl = this.positionEl = w;

			var helpEl = w.createChild({
				tag: 'img'
				,src: Ext.BLANK_IMAGE_URL
				,cls: 'eo-form-agefield fugico_question-frame'
				,style: 'position: absolute; top: 2px; right: 0; cursor: pointer;'
				,width: 16
				,height: 16
			});
			
			new Ext.ToolTip({
				html: html
				,cls: 'eo-help'
				,showDelay: 0
				,dismissDelay: 0
				,trackMouse: true
				,target: helpEl
			});
			
//			helpEl.on({
//				mouseenter: function() {
//					tip.showBy(helpEl);
//				}
//				,mouseleave: function() {
//					tip.hide();
//				}
//			});
			
//			debugger
//			
//			Ext.QuickTips.register({
//				target: helpEl
//				,title: "Aide"
//				,text: "Bla bla bla"
//			})
		}
	}
	
	,onResize: function(w, h){
		eo.form.AgeField.superclass.onResize.call(this, w, h);
		if(Ext.isNumber(w)){
			this.el.setWidth(w - 20);
		}
		this.getResizeEl().setWidth(w);
	}

	// private
	,parseValue: function(v) {
		if (Ext.isString(v)) {
			var yre = this.yearRe,
				mre = this.monthRe,
				dre = this.dayRe,
				mmre = /(\d+)\s*([a-z])/i,
				
				matches = v.match(/\d+(?:\s*[a-z]+)?/ig),
				order = ['years', 'months', 'days'],
				i = 0,
				age = {years: null, months: null, days: null};
				
			if (matches) {
				Ext.each(matches, function(m) {
					var v,
						mm = mmre.exec(m);
					if (mm) {
						v = parseInt(mm[1]);
						if (!isNaN(v)) {
							if (yre.test(mm[2])) {
								age.years = v;
							} else if (mre.test(mm[2])) {
								age.months = v;
							} else if (dre.test(mm[2])) {
								age.days = v;
							}
						}
					} else {
						v = parseInt(m);
						if (!isNaN(v)) {
							age[order[i++]] = v;
						}
					}
				});
			} else {
				return null;
			}
			delete age['undefined']; // just in case...
			return age.years !== null || age.months !== null || age.days !== null
					? age
					: null;
		}
		return null;
	}
	
	// private
	,formatAge: function(age) {
		var y = age.years,
			m = age.months,
			d = age.days,
			lt = this.texts,
			r = '';
		function text(n, w) {
			return n + ' ' + (Ext.isString(w) ? w : (w[0+(n>1)]));
		}
		if (!Ext.isEmpty(y)) {
			r += text(y, lt.year);
		}
		if (!Ext.isEmpty(m)) {
			r += (r !== '' ? ' ' : '') + text(m, lt.month);
		}
		if (!Ext.isEmpty(d)) {
			r += (r !== '' ? ' ' : '') + text(d, lt.day);
		}
		return r;
	}
	
	,setValue: function(v) {
		if (Ext.isObject(v)) {
			this.age = v;
		} else {
			v = this.age = this.parseValue(v);
		}
		if (this.rendered) {
			this.setRawValue(v ? this.formatAge(v) : '');
		} else {
			this.on({
				scope: this
				,single: true
				,afterrender: function() {
					this.setRawValue(v ? this.formatAge(v) : '');
				}
			});
		}
	}
	
	,getValue: function() {
		var a = this.age;
		if (this.returnObject) {
			return a;
		} else if (a) {
			return 'P'
					+ (a.years !== null ? a.years + 'Y' : '')
					+ (a.months !== null ? a.months + 'M' : '')
					+ (a.days !== null ? a.days + 'D' : '');
		} else {
			return null;
		}
	}
	
	,beforeBlur: function() {
		var age = this.age = this.parseValue(this.getRawValue());
		if (!Ext.isEmpty(age)) {
			this.setRawValue(this.formatAge(age));
		}
	}
	
});

Ext.reg('agefield', 'eo.form.AgeField');