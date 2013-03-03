/**
 * Copyright (C) 2013 Eoko
 *
 * This file is part of Opence.
 *
 * Opence is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Opence is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Opence. If not, see <http://www.gnu.org/licenses/gpl.txt>.
 *
 * @copyright Copyright (C) 2013 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

/**
 * A simple clock engine.
 *
 * @since 2013-03-13 09:19
 */
Ext4.define('Eoze.time.Clock', {

	mixins: {
		observable: 'Ext.util.Observable'
	}

	,requires: [
		'Ext.Template',
		'Ext.util.TaskManager',
		'Eoze.time.ClockValue'
	]

	/**
	 * @cfg {Eoze.time.ClockValue} value
	 */

	/**
	 * @property {Integer[]}
	 * @protected
	 */
	,zeros: [0,0,0]
	/**
	 * @property {Integer[]}
	 * @protected
	 */
	,boundaries: [60,60]
	/**
	 * @property {String[]}
	 * @private
	 */
	,units: ['seconds', 'minutes']

	,constructor: function(config) {
		this.mixins.observable.constructor.call(this, config);

		this.addEvents(
			/**
			 * @event
			 * Fires each time the value of the clock changes.
			 * @param {Eoze.util.Clock} this
			 */
			'change'
		);

		if (this.value) {
			this.setValue(this.value);
		}

		this.units = this.value.parseUnits(this.units);

		this.initAutoStart();
	}

	/**
	 * Starts the clock.
	 */
	,start: function() {
		if (!this.updateTask) {
			this.updateTask = Ext4.util.TaskManager.start({
				scope: this
				,run: this.tick
				,interval: 1000
			});
		}
	}

	/**
	 * Stops the clock.
	 */
	,stop: function() {
		var updateTask = this.updateTask;
		if (updateTask) {
			Ext4.TaskManager.stop(updateTask);
			delete this.updateTask;
		}
	}

	/**
	 * Reset the clock.
	 */
	,reset: function() {
		this.setValue(this.initialValue);
	}

	/**
	 * @private
	 */
	,initAutoStart: function() {
		var as = this.autoStart;
		if (as) {
			if (as === true) {
				this.start();
			} else {
				Ext.Function.defer(this.start, as, this);
			}
		}
	}

	/**
	 * Updates the clock.
	 *
	 * @private
	 */
	,tick: function() {
		var value = this.value,
			units = this.units,
			boundaries = this.boundaries,
			finished = false;

		function tick(unit) {
			var index = units.indexOf(unit),
				treshold = boundaries[index],
				next = units[index+1];

			if (Ext.isEmpty(value[unit]) || value[unit] === treshold) {
				finished = true;
			} else {
				value[unit] = this.increment(value[unit]);

				if (value[unit] === treshold) {
					value[unit] = this.zeros[index];
					if (next) {
						return next;
					} else {
						finished = true;
					}
				}
			}

			return false;
		}

		var next = units[0];
		//noinspection StatementWithEmptyBodyJS
		while (next = tick.call(this, next));

		this.fireEvent('change', this, this.value);

		if (finished) {
			this.onFinish();
		}
	}

	/**
	 * Increments the passed unit component value by 1.
	 *
	 * @param {Integer} value
	 * @protected
	 */
	,increment: function (value) {
		return value + 1;
	}

	/**
	 * @protected
	 */
	,onFinish: function() {
		this.stop();
	}

	/**
	 * @private
	 */
	,updateClock: function() {
		var next = 'seconds';
		while (next = this.tick(next)) {
			this.tick(this.nextUnit[unit]);
		}

		this.units.forEach(function(unit) {
			if (this.tick(unit)) {
				this.tick(this.nextUnit[unit]);
			}
		}, this);

		var value = this.value,
			sec = ++value.seconds;

		var sec = this.tick('seconds');

		if (sec === 60) {
			value.minutes++;
			value.seconds = 0;
		}

		if (value.minutes === 60) {
			value.hours++;
			value.minutes = 0;
		}

		this.fireEvent('change', this);
	}

	/**
	 * @param {Eoze.time.ClockValue/Object}
	 */
	,setValue: function(value) {
		var ClockValue = Eoze.time.ClockValue;

		if (value instanceof ClockValue) {
			this.value = value;
		} else if (this.value instanceof ClockValue) {
			this.value.set(value);
		} else {
			this.value = Ext4.create(ClockValue, value);
		}

		this.initialValue = this.value.clone();
	}

	/**
	 * @return {Eoze.time.ClockValue}
	 */
	,getValue: function() {
		return this.value;
	}
});
