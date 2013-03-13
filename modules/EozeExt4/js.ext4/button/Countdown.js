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
 * A button that can be configured to auto fire after a given time. The countdown is displayed to the user
 * in the button text.
 *
 * @since 2013-03-13 10:03
 */
Ext4.define('Eoze.button.Countdown', {
	extend: 'Ext.button.Button'

	,alias: 'widget.countdownbutton'

	,requires: [
		'Eoze.time.Countdown'
	]

	/**
	 * @cfg {Boolean/Integer}
	 */
	,autoStart: false

	/**
	 * @cfg {Integer} [minutes=0]
	 */
	/**
	 * @cfg {Integer} [seconds=0]
	 */

	/**
	 * @inheritdoc
	 */
	,initComponent: function() {
		this.callParent(arguments);

		this.initialText = this.text;

		if (this.clock) {
			this.setClock(this.clock);
		} else if (this.minutes) {
			this.setClock({
				value: {
					units: 'minutes,seconds'
					,minutes: this.minutes
					,seconds: this.seconds
				}
				,autoStart: this.autoStart
			});
		} else if (this.seconds) {
			this.setClock({
				value: {
					units: 'seconds'
					,seconds: this.seconds
				}
				,autoStart: this.autoStart
			});
		}
	}

	/**
	 * @param {Eoze.time.Clock/Object} clock
	 * @private
	 */
	,setClock: function(clock) {
		if (!(clock instanceof Eoze.time.Clock)) {
			clock = Ext4.create('Eoze.time.Countdown', clock);
		}

		this.clock = clock;

		clock.on({
			scope: this
			,change: this.onClockChange
			,finish: this.onClockFinish
		});

		this.onClockChange(clock, clock.getValue());
	}

	/**
	 * Starts the countdown clock.
	 *
	 * @public
	 */
	,startClock: function() {
		this.clock.start();
	}

	/**
	 * Stops the clock and hide the countdown.
	 *
	 * @public
	 */
	,stopClock: function() {
		this.clock.stop();
		this.setText(this.initialText);
	}

	,resetClock: function() {
		this.clock.reset();
	}

	/**
	 * Handler for updating displayed counter when clock changes.
	 *
	 * @param {Eoze.time.Clock} clock
	 * @param {Eoze.time.ClockValue} value
	 * @private
	 */
	,onClockChange: function(clock, value) {
		this.setText(
			Ext.String.format(
				'{0} ({1})',
				this.initialText,
				value
			)
		);
	}

	/**
	 * Handler for triggering the button handler on clock finish.
	 *
	 * @private
	 */
	,onClockFinish: function() {
		this.stopClock();
		this.fireHandler();
	}

});
