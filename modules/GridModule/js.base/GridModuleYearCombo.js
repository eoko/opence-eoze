Ext.ns('Oce.GridModule')

Oce.deps.wait('Oce.form.ForeignComboBox', function() {

	/**
	 * A date selector intended to let the user choose the date on
	 * which the presented data will be synchronized.
	 * 
	 * In order to ease possible changes in implementation, this class
	 * defines custom events ({@link #beforedatechanged} and
	 * {@link #datechanged}) on which other components can rely safely.
	 * For the same reason, it defines a custom {@link #getDate} method.
	 */
	Oce.YearCombo = Ext.extend(eo.form.DateField, {
		
		label: 'Date'
		
		,constructor: function() {
			Oce.YearCombo.superclass.constructor.apply(this, arguments);
			
			this.addEvents(
				/**
				 * @event beforedatechanged
				 * Fires before the date is changed. Returning `false` will prevent
				 * the {@link #datechanged} event from being fired (but the value 
				 * will still be changed).
				 * @param {Oce.YearCombo} this
				 * @param {Date} date The new date value.
				 */
				'beforedatechange',
				
				/**
				 * @event datechanged
				 * Fires when the date value of the selector has changed.
				 * @param {Oce.YearCombo} this
				 * @param {Date} date The new date value.
				 */
				'datechanged'
			);
				
			this.on({
				scope: this
				,specialkey: function(me, e) {
					if (e.getKey() === e.ENTER) {
						e.stopEvent();
						if (!this.dateEquals(this.startValue)) {
							this.onSetValue(this.getValue());
						}
					}
				}
			});
		}
		
		,onBlur: function() {
			Oce.YearCombo.superclass.onBlur.call(this);
			if (!this.dateEquals(this.startValue)) {
				this.onSetValue(this.getValue());
			}
		}

		// prevents the field from stealing focus on menu selection
		,onMenuHide: Ext.emptyFn
		
		// prevents the field from blinking when the menu is triggered
		,onTriggerClick: function() {
			var orig = this.selectOnFocus;
			this.selectOnFocus = false;
			Oce.YearCombo.superclass.onTriggerClick.apply(this, arguments);
			this.selectOnFocus = orig;
		}
		
		/**
		 * Changes the value of the field, and fire the beforedatechanged
		 * and datechanged event as needed. This method does not test for
		 * difference with the already set value, that is expected to be
		 * done by {#setValue}.
		 * 
		 * @param {Date} v
		 * @param {Boolean} skipEvent `true` to prevent the firing of the 
		 * {@link #datechanged} event.
		 * 
		 * @private
		 */
		,onSetValue: function(v, skipEvent) {
			var setValue = Oce.YearCombo.superclass.setValue;
			setValue.call(this, v);
			this.startValue = this.getValue();
			if (false !== this.fireEvent('beforedatechange', this, v)
					&& !skipEvent) {
				this.fireEvent('datechanged', this, v);
			}
		}
		
		/**
		 * Changes the value of the field.
		 * 
		 * @param {Date} v
		 * @param {Boolean} skipEvent `true` to prevent the firing of the 
		 * {@link #datechanged} event.
		 */
		,setValue: function(v, skipEvent) {
			if (!this.dateEquals(v)) {
				if (!v.format) {
					v = this.parseDateOrDie(v);
				}
				this.onSetValue(v, skipEvent);
			}
		}
		
		/**
		 * Parses a date or throw an {@link Error} if the value passed cannot
		 * be parsed to a date.
		 * @param {String} v The value to parse to a {Date}.
		 * @return {Date}
		 * @private
		 */
		,parseDateOrDie: function(v) {
			var r = this.parseDate(v);
			if (!(r instanceof Date)) {
				throw new Error('Cannot parse date: ' + v);
			}
			return r;
		}
		
		/**
		 * Compares the value of this field with the given date.
		 * @param {Date|String} date The date to compare to. Can be `undefined` or
		 * `null`. If given as a string, the date will be converted using the 
		 * {@link eo.form.DateField#format format} of this DateField.
		 * @param {String} [mask='Ymd'] The mask to be used to compare the
		 * dates.
		 * @return {Boolean}
		 */
		,dateEquals: function(date, mask) {
			var v = this.getValue();
			// test null/undefined dates
			if (!v) {
				if (!date) {
					return true;
				} else {
					return false;
				}
			} else if (!date) {
				return false;
			}
			
			// convert to dates
			if (!v.format) {
				v = this.parseDateOrDie(v);
			}
			if (!date.format) {
				date = this.parseDateOrDie(date);
			}
			// default mask
			if (!mask) {
				mask = 'Ymd';
			}
			// compare dates
			return v.format(mask) === date.format(mask);
		}
		
		/**
		 * Gets the current date as a string in the format `Y-m-d`. If the
		 * date is not set when this method is called, it will raise and Error.
		 * This method should be used instead of {@link #getValue} in order
		 * to ease future implementation changes.
		 * @return {String}
		 */
		,getDateString: function() {
			var v = this.getValue();
			if (v instanceof Date) {
				return v.format('Y-m-d');
			} else if (!v) {
				throw new Error('Date is not set');
			} else {
				return v;
			}
		}
		
		/**
		 * @deprecated
		 */
		,waitFirstLoad: function(callback) {
			callback();
		}
	});

	Ext.reg('oce.yearcombo', Oce.YearCombo);

	Oce.GlobalYearManager = Ext.extend(Oce.YearCombo, {

		constructor: function(config) {
			Oce.GlobalYearManager.superclass.constructor.call(this, config);
		}
	})

	Oce.deps.reg('Oce.GridModule.YearCombo');
});