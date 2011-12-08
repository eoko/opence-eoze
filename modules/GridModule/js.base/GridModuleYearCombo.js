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
	Oce.DateYearCombo = Ext.extend(eo.form.DateField, {
		
		label: 'Date'
		
		,constructor: function() {
			Oce.DateYearCombo.superclass.constructor.apply(this, arguments);
			
			this.addEvents(
				/**
				 * @event beforedatechanged
				 * 
				 * Fires before the date is changed. Returning `false` will prevent
				 * the {@link #datechanged} event from being commited. That is, the
				 * displayed value will be changed, but {@link #getValue} will continue
				 * to return the old value as long as the {@link #commitSetValue}
				 * method has not been called. To revert to the old value, call
				 * {@link #cancelSetValue}.
				 * 
				 * Note that calling {@link #setValue} while a value is waiting to be
				 * commited will result in an {@link #Error}.
				 * 
				 * @param {Oce.DateYearCombo} this
				 * @param {Date} date The new date value.
				 */
				'beforedatechange',
				
				/**
				 * @event datechanged
				 * Fires when the date value of the selector has changed.
				 * @param {Oce.DateYearCombo} this
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
							this.onSetValue(this.getValue(), this.startValue);
						}
					}
				}
			});
		}
		
		// prevent beforeBlur from unrulily calling setValue
		,beforeBlur: Ext.emptyFn
		
		,onBlur: function() {
			Oce.DateYearCombo.superclass.onBlur.call(this);
			if (!this.dateEquals(this.startValue)) {
				this.onSetValue(this.getValue(), this.startValue);
			}
		}

		// prevent the field from stealing focus on menu selection
		,onMenuHide: Ext.emptyFn
		
		// prevent the field from blinking when the menu is triggered
		,onTriggerClick: function() {
			var orig = this.selectOnFocus;
			this.selectOnFocus = false;
			Oce.DateYearCombo.superclass.onTriggerClick.apply(this, arguments);
			this.selectOnFocus = orig;
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
		 * Changes the value of the field, and fire the beforedatechanged
		 * and datechanged event as needed. This method does not test for
		 * difference with the already set value, that is expected to be
		 * done by {#setValue}.
		 * 
		 * @param {Date} v
		 * @param {Date} lastValue
		 * 
		 * @private
		 */
		,onSetValue: function(v, lastValue) {
			if (!Ext.isDefined(this.cancelValue)) {
				
				this.cancelValue = lastValue;
				Oce.DateYearCombo.superclass.setValue.call(this, v);
				this.startValue = this.getValue();

				if (false !== this.fireEvent('beforedatechange', this, v)) {
					delete this.cancelValue;
					this.fireEvent('datechanged', this, v);
				}
			} else {
				// This should not happen. Use the call stack & track the ***ing culprit!!!
				debugger
			}
		}

		// overriden to send the old value as long as the new one has not been
		// commited
		,getValue: function() {
			if (this.cancelValue) {
				return this.cancelValue;
			} else {
				return Oce.DateYearCombo.superclass.getValue.call(this);
			}
		}
		
		/**
		 * Commits a value changed put on hold by returning `false` to 
		 * {@link #beforedatechange}. Either this method or 
		 * {@link #cancelSetValue} **must** be called after having blocked
		 * a {@link #setValue} by doing so.
		 * 
		 * This method will trigger the {@link #datechanged} event for the
		 * waiting value.
		 */
		,commitSetValue: function() {
			if (!Ext.isDefined(this.cancelValue)) {
				debugger
				throw new Error('Nothing to commit');
			}
			delete this.cancelValue;
			this.fireEvent('datechanged', this, this.getValue());
		}
		
		/**
		 * Restores the value that the field had at the time the last
		 * {@link #beforedatechange} event was fired.
		 * 
		 * This method does not trigger the {@link #datechanged} event (nor
		 * the beforedatechange one, for that matter), so you'd better have
		 * blocked it earlier, or you date dependent components will stay out
		 * of sync...
		 */
		,cancelSetValue: function() {
			Oce.DateYearCombo.superclass.setValue.call(this, this.cancelValue);
			delete this.cancelValue;
		}
		
		/**
		 * Changes the value of the field.
		 * 
		 * @param {Date} v
		 * @param {Boolean} skipEvent `true` to prevent the firing of the 
		 * {@link #datechanged} event.
		 */
		,setValue: function(v) {
			if (Ext.isDefined(this.cancelValue)) {
				debugger
				throw new Error('Uncommited previous value');
			}
			if (!this.dateEquals(v)) {
				if (!v.format) {
					v = this.parseDateOrDie(v);
				}
				this.onSetValue(v, this.getValue());
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
		 * @param {Date/String} date The date to compare to. Can be `undefined` or
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
		 * @deprecated
		 */
		,waitFirstLoad: function(callback) {
			callback();
		}
	});

	Ext.reg('oce.yearcombo', Oce.DateYearCombo);

	Oce.GlobalYearManager = Ext.extend(Oce.DateYearCombo, {

		constructor: function(config) {
			Oce.GlobalYearManager.superclass.constructor.call(this, config);
		}
	})

	Oce.deps.reg('Oce.GridModule.YearCombo');
});