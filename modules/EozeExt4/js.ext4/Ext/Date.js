/**
 * Copyright (C) 2012 Eoko
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
 * @copyright Copyright (C) 2012 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

/**
 *
 * - Overrides {@link #isEqual} method to implement `ignoreTime` option.
 *
 * - Adds 'r' date format for [RFC2822](http://www.faqs.org/rfcs/rfc2822.html) date time.
 *
 * @since 2012-11-28 16:14
 */
Ext4.define('Eoze.Ext.Date', {
	override: 'Ext.Date'

	// TODO move that to application domain
	,defaultFormat: 'd/m/Y'
	,defaultDateTimeFormat: 'd/m/Y H:i'

	/**
	 * This method is overridden to respect the `ignoreTime` option in dates.
	 *
	 * @see {@link Ext.Date#isEqual}
	 * @param {Date} date1
	 * @param {Date} date2
	 * @return {Boolean}
	 */
	,isEqual: function(date1, date2) {
		var xDate = Ext4.Date;
		if (date1 && date2) {
			if (date1.ignoreTime) {
				if (date2.ignoreTime) {
					return xDate.format(date1, 'Ymd') === xDate.format(date2, 'Ymd');
				} else {
					date1 = xDate.clearTime(date1, true);
				}
			} else if (date2.ignoreTime) {
				date2 = xDate.clearTime(date2, true);
			}
			return date1.getTime() === date2.getTime();
		}
		// one or both isn't a date, only equal if both are falsey
		return !(date1 || date2);
	}
}, function() {

	var Ext = Ext4,
		utilDate = this;

	var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
		months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
		format = this.format;

	Ext.apply(this.formatFunctions, {
		r: function() {
			var date = this;
			return days[date.getDay()] + ', '
				+ format(date, 'd ')
				+ months[date.getMonth()]
				+ format(date, ' Y H:i:s ')
				+ date.getGMTOffset();
		}

		,'defaultDateTime': function() {
			return utilDate.format(this, utilDate.defaultDateTimeFormat);
		}

		/**
		 * ISO 8601 Date time format, precision to the second, including time zone
		 */
		,ISO: function() {
			return utilDate.format(this, 'Y-m-d\\TH:i:sO');
		}

		/**
		 * Friendly elapsed time representation (e.g. "5 min ago", etc.).
		 */
		,social: function() {
			var dayNames = Ext4.Date.dayNames,
				format = Ext4.String.format,
				diff = Math.round((new Date - this) / 1000),
				s = 1,
				min = 60*s,
				h = 60*min,
				day = 24*h,
				ago;
			if (diff >= day) {
				// i18n
				return "le " + utilDate.format(this, "j M Y à H:i");
			} else if (diff < 30*s) {
				// i18n
				return "à l'instant";
			} else if (diff < min) {
				ago = diff;
				// i18n
				return format("il y a {0} seconde{1}", ago, ago > 1 ? 's' : '');
			} else if (diff < h) {
				ago = Math.round(diff / min);
				// i18n
				return format("il y a {0} minute{1}", ago, ago > 1 ? 's' : '');
			} else if (diff < day) {
				ago = Math.round(diff / h);
				// i18n
				return format("il y a {0} heure{1}", ago, ago > 1 ? 's' : '');
			} else if (diff < day*2) {
				// i18n
				return 'hier à ' + utilDate.format(this, 'H:i');
			} else if (diff < day*4) {
				// i18n
				return dayNames[this.getDay()] + ' à ' + utilDate.format(this, 'H:i');
			} else if (diff < day*8) {
				// i18n
				return dayNames[this.getDay()] + ' dernier à ' + utilDate.format(this, 'H:i');
			} else {
				// i18n
				return "le " + utilDate.format(this, "j M Y à H:i");
			}
		}
	});
});
