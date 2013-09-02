(function(Ext) {
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
 * Simple util for periodically updating date representations on a page.
 *
 * After the {@link #init} method has been called, this class will update all spans
 * on the page that have a `data-dyndate` attribute. The refresh delay is configurable
 * with the {@link #refreshDelay} option.
 *
 * Example markup:
 *
 *     <span data-dyndate="2012-12-12 18:16:21">2012-12-12 18:16:21</span>
 *
 * Loading this class will also add a `dynDate` format to {@link Ext.util.Format}
 * that can be used in {@link Ext.Template} to easily generate the required markup.
 *
 * Example usage in a template:
 *
 *     My dynamic date: {myDate:dynDate}
 *
 * @since 2013-07-17 01:44
 */
Ext.define('Eoze.util.DynDate', {
	singleton: true

	,requires: [
		'Ext.util.Format'
	]

	/**
	 * Refresh rate, in milliseconds.
	 *
	 * @cfg {Integer}
	 */
	,refreshDelay: 15000 // 15s

	// private
	,polling: false
	// private
	,dateFormat: 'social'

	,init: function(config) {

		Ext.apply(this, config);

		if (this.polling) {
			clearInterval(this.interval);
			delete this.interval;
		}

		this.interval = setInterval(Ext.bind(this.onPoll, this), this.refreshDelay);
		this.polling = true;
	}

	/**
	 * @private
	 */
	,onPoll: function() {

		var format = Ext.Date.format;

		function updateTarget(t) {
			var el = Ext.fly(t),
				date = new Date(el.getAttribute('data-dyndate')),
				value = format(date, 'social');
			if (el.getHTML() !== value) {
				el.update(value);
			}
		}

		return function() {
			var targets = Ext.query('[data-dyndate]');
			targets.forEach(updateTarget);
		}
	}()
}, function() {

	var tpl = new Ext.XTemplate(
		'<span data-qtip="Le {date:date(\"j M Y à H:i\")}" data-dyndate="{date:date(\'@!Y-m-d\TH:i:s\')}">',
			'{date:date("social")}',
		'</span>'
	);

	// Adds dynDate formatter
	Ext.util.Format.dynDate = function(date) {
		return tpl.apply({
			date: date
		});
	}
});
})(window.Ext4 || Ext.getVersion && Ext);
