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
 * Adds {@link #delay} option to LoadMask.
 *
 * @since 2013-07-10 00:39
 */
Ext.define('Eoze.Ext.LoadMask.Delay', {
	override: 'Ext.LoadMask'

	/**
	 * Delay after which the mask is effectively shown, when {@link #show} is called. If {@link #hide}
	 * is called before the end of this delay, then the mask won't be displayed at all. In some
	 * situation, that can prevent the mask from flickering.
	 *
	 * @cfg {Integer}
	 */
	,delay: 0

	,constructor: function() {
		Ext.apply(this, {
			superShow: this.show
			,show: this.delayedShow
		});

		this.callParent(arguments);
	}

	// private
	,delayedShow: function() {
		var delay = this.delay;
		if (delay) {
			this.toBeShown = true;
			var args = arguments;
			Ext.defer(function() {
				if (this.toBeShown) {
					this.superShow.apply(this, args);
				}
			}, delay, this);
		} else {
			this.callParent(arguments);
		}
	}

	/**
	 * Implements {@link #delay}.
	 */
	,hide: function() {
		var delay = this.delay;
		if (delay) {
			this.toBeShown = false;
		}
		this.callParent(arguments);
	}

});
})(window.Ext4 || Ext.getVersion && Ext);
