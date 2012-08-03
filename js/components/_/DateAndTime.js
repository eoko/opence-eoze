(function() {

	var NS = Ext.ns("eo.form");
	
	var localeNS = Ext.ns("eo.locale");

	var dateFormat = localeNS.dateFormat = "d/m/Y",
		altDateFormats = "j/n/Y|j/n/y|j/n|j n Y|j n y|j n|Y-m-d|Y n j",
		timeFormat = "H:i",
		altTimeFormats = "G:i|G:i|G i|Gi|G|G:i:s|G i s";
		
	function safeParse(value, format) {
        if (/[gGhH]/.test(format.replace(/(\\.)/g, ''))) {
			// if parse format contains hour information, no DST adjustment is necessary
			return Date.parseDate(value, format);
		} else {
			// set time to 12 noon, then clear the time
//			var parsedDate = Date.parseDate(value + ' ' + this.initTime, format + ' ' + this.initTimeFormat);
			var parsedDate = Date.parseDate(value + ' ' + 12, format + ' ' + 'H');
 
			if (parsedDate) {
				return parsedDate.clearTime();
			}
		}
		return undefined;
	}
	
	function parseDate(value) {
		
		if(!value || Ext.isDate(value)) {
			return value;
		}

		var v = safeParse(value, dateFormat),
			af = altDateFormats;

		if (!v && af) {
			var afa = af.split("|");
			for (var i = 0, len = afa.length; i < len && !v; i++) {
				v = safeParse(value, afa[i]);
			}
		}
		
		return v;
	}
	
	/**
	 * Parses a date or throw an {@link Error} if the value passed cannot
	 * be parsed to a date.
	 * @param {String} v The value to parse to a {Date}.
	 * @return {Date}
	 * @private
	 */
	function parseDateOrDie(v) {
		var r = parseDate(v);
		if (!(r instanceof Date)) {
			throw new Error('Cannot parse date: ' + v);
		}
		return r;
	}
	
	eo.datesEqual = function(date1, date2, mask) {
		// test null/undefined dates
		if (!date1) {
			if (!date2) {
				return true;
			} else {
				return false;
			}
		} else if (!date2) {
			return false;
		}

		// convert to dates
		if (!date1.format) {
			date1 = parseDateOrDie(date1);
		}
		if (!date2.format) {
			date2 = parseDateOrDie(date2);
		}
		// default mask
		if (!mask) {
			mask = 'YmdHis';
		}
		// compare dates
		return date1.format(mask) === date2.format(mask);
	};

	eo.form.DateField = Ext.extend(Ext.form.DateField, {
		constructor: function(config) {
			config = Ext.apply({
				format: dateFormat
				,altFormats: altDateFormats
			}, config);
			eo.form.DateField.superclass.constructor.call(this, config);
		}
	});

	eo.form.TimeField = Ext.extend(Ext.form.TimeField, {
		constructor: function(config) {
			config = Ext.apply({
				format: timeFormat
				,altFormats: altTimeFormats
				,autoSelect: false
				,minChars: 100
				,triggerClass: "x-form-clock-trigger"
				,enableKeyEvents: true
				,validationEvent: "keyup"
			}, config);
			eo.form.TimeField.superclass.constructor.call(this, config);
		}
	});

	Ext.reg("timefield", eo.form.TimeField);
	Ext.reg("datefield", eo.form.DateField);

	function parseDatetimeName(config) {
		var n = config.name;
		if (!n) {
			return undefined;
		} else {
			if (Ext.isString(n)) {
				return n;
			} else if (Ext.isArray(n)) {
				if (n.length !== 2) {
					throw new Error("Invalid name spec for name, an array of length of exactly 2 is expected (here: " + n.length + ")");
				} else {
					return n;
				}
			} else if (Ext.isObject(n)) {
				return [n.date, n.time];
			} else {
				throw new Error("Invalid name spec (should be [DATE_NAME, TIME_NAME] or {date: DATE_NAME, time: TIME_NAME}): " + n);
			}
		}
	}

	var removeFieldName = function() {
		this.el.dom.removeAttribute("name");
		this.un("afterrender", arguments.callee);
	}

	/**
	 * @cfg {Boolean} nowButton (default: false) add a button to set the fields
	 * to the current date and time.
	 */
	eo.form.DateTimeField = Ext.extend(Ext.form.CompositeField, {

		dateTimeSeparator: "T"
		,dateTimeSplitter: /T| /
		,outDateFormat: 'Y-m-d'
		,outTimeFormat: 'H:i'

		,initComponent: function() {

			var dc = this.date || this.dateConfig || {},
				tc = this.time || this.timeConfig || {},
				dateName = dc.name || this.dateFieldName,
				timeName = tc.name || this.timeFieldName,
				items = [];

			var df = this.dateField = new eo.form.DateField(Ext.apply({
				flex: 1
				,enableKeyEvents: true
				,name: dateName
				,submitValue: !!dateName
			}, dc));

			var tf = this.timeField = new eo.form.TimeField(Ext.apply({
				flex: 1
				,name: timeName
				,submitValue: !!timeName
			}, tc));

			// Remove autogen name attribute if fields are not to be
			// submitted independantly
			if (!dateName) {
				df.on('afterrender', removeFieldName);
			}
			if (!timeName) {
				tf.on('afterrender', removeFieldName);
			}

			if (this.name && this.submitValue !== false) {

				var hidden = this.hiddenField = new Ext.form.Hidden({
					name: this.name
				});

				var syncFn = this.updateFieldValue.createDelegate(this);
				this.mon(df, "keyup", syncFn);
				this.mon(df, "select", syncFn);
				this.mon(tf, "keyup", syncFn);
				this.mon(tf, "select", syncFn);
				this.hiddenField.on("afterrender", function() {
					this.un("afterrender", arguments.callee);
					syncFn();
				});

				// If there is a hidden field in composite field, we don't want
				// to have it as the first or the last item, or it will make
				// the field improperly add a gutter space (ext3.3 15/02/11 17:19)
				items.push(df, hidden, tf);
			} else {
				items.push(df, tf);
			}

			if (this.nowButton) {
				items.push({
					xtype: "button"
					,width: 24
					,height: 18
					,cls: "x-form-now-button"
					,tooltip: "Now"
					,handler: function() {
						var now = new Date();
						df.setValue(now);
						tf.setValue(now);
					}
				});
			}

			this.items = items;
			
			eo.form.DateTimeField.superclass.initComponent.call(this);
		}

		,updateFieldValue: function() {
			if (this.hiddenField) {
				this.hiddenField.setValue(this.getRawValue());
			}
		}

		,getDate: function() {
			return this.dateField.getValue();
		}

		,getTime: function() {
			return this.timeField.getValue();
		}

		/**
		 * Returns the value of the datetime field, as a Date object.
		 */
		,getValue: function() {
			
			var date = this.getDate();
			
			if (!Ext.isDate(date)) {
				return "";
			}

			var tf = this.timeField,
				def = Date.defaults;
			
			Ext.apply(def, {
				d: date.getDate(),
				m: date.getMonth() + 1,
				y: date.getFullYear()
			});

			var r = Date.parseDate(tf.getValue(), tf.format);
			delete def.d;
			delete def.m;
			delete def.y;

			if (!r) {
				return "";
			} else {
				return r;
			}
		}

		,getRawValue: function() {
			var date = this.getValue();
			if (!date) return "";
			var df = this.outDateFormat || this.dateField.format,
				tf = this.outTimeFormat || this.timeField.format;
			return date.format(df) + this.dateTimeSeparator + date.format(tf);
		}

		,setDate: function(date) {
			this.dateField.setValue(date);
			this.updateFieldValue();
		}

		,setTime: function(time) {
			this.timeField.setValue(time);
			this.updateFieldValue();
		}

		,setValue: function(value) {
			if (!value) {
				setDate(undefined);
				setValue(undefined);
				return;
			}
			if (Ext.isString(value)) {
				value = value.split(this.dateTimeSplitter || this.dateTimeSeparator);
			} else if (Ext.isDate(value)) {
				value = [value, value];
			}
			var ufn = this.updateFieldValue;
			// disable field update while setting value
			this.updateFieldValue = Ext.emptyFn;
			this.setDate(value.shift());
			this.setTime(value.shift());
			// restore & apply field update
			this.updateFieldValue =  ufn;
			this.updateFieldValue();
		}
	});

	Ext.reg("datetimefield", eo.form.DateTimeField);

})(); // closure

/** DEBUG
Ext.onReady(function() {
	var dtf = new eo.form.DateTimeField({
		name: "dtf"
	});

	(new Ext.Window({
		width: 300
		,height: 200
		,items: dtf
	})).show();

	dtf.setDate(new Date("1982-08-30"));
	dtf.setTime("10:11");

	dtf.getValue();
})
*/