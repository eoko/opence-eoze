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
 * A clock that counts down from a given time, and fires an event when it reaches zero.
 *
 * @since 2013-03-13 09:19
 */
Ext4.define('Eoze.time.Countdown', {
	extend: 'Eoze.time.Clock'

	,constructor: function() {
		this.callParent(arguments);

		this.addEvents(
			/**
			 * @event
			 * Fires when the count down reaches 00:00:00.
			 * @param {Eoze.util.Countdown} this
			 */
			'finish'
		);
	}

	/**
	 * @inheritdoc
	 */
	,zeros: [59,59]
	/**
	 * @inheritdoc
	 */
	,boundaries: [-1,-1]

	/**
	 * @inheritdoc
	 */
	,increment: function(value) {
		return value - 1;
	}

	/**
	 * @inheritdoc
	 */
	,onFinish: function() {
		this.callParent(arguments);
		this.fireEvent('finish', this);
	}

});
