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
 * Internal value for a {@link Eoze.time.Clock clock}.
 *
 * @since 2013-03-13 11:41
 */
Ext4.define('Eoze.time.ClockValue', {
	extend: 'Eoze.util.RenderableValue'

	/**
	 * @property {Integer} hours
	 */
	/**
	 * @property {Integer} minutes
	 */
	/**
	 * @property {Integer} seconds
	 */

	/**
	 * @cfg {String[]/String}
	 */
	,units: ['hours', 'minutes', 'seconds']
	/**
	 * @cfg {String[]/String} [show=undefined]
	 *
	 * Units to show in rendered string. Defaults to {Eoze.time.ClockValue#units}.
	 */

	/**
	 * @cfg {Ext4.Template/String} [tpl=undefined]
	 *
	 * If unspecified, will be created automatically from {@link Eoze.time.ClockValue#show}
	 * or {@link Eoze.time.ClockValue#units}.
	 */

//	,tpl: '{minutes:leftPad(2,"0")}:{seconds:leftPad(2,"0")}'

	,constructor: function(config) {

		Ext.apply(this, config);

		// Units
		this.units = this.parseUnits(this.units);
		this.show = this.parseUnits(this.show || this.units);

		// Init value (need units)
		this.set(config);
	}

	/**
	 * @inheritdoc
	 */
	,createTemplate: function() {
		var format = [];

		this.show.forEach(function(unit) {
			format.push('{' + unit + ':leftPad(2,"0")}');
		});

		return Ext4.create('Ext4.Template', format.join(':'));
	}

	/**
	 * Parse a string or array to an array of units.
	 *
	 * @param {String[]/String} units
	 * @return {String[]}
	 * @private
	 */
	,parseUnits: function(units) {
		if (Ext.isArray(units)) {
			return units;
		} else if (Ext.isString(units)) {
			return units.split(/[,;\s]/);
		} else {
			throw new Error('Illegal argument (string or array expected): ' + units);
		}
	}

	,clone: function() {
		return new Eoze.time.ClockValue(this);
	}

	/**
	 * Sets alls unit components to the specified value, or zero.
	 *
	 * @param {Object} value
	 * @param {Integer} [value.hours=0]
	 * @param {Integer} [value.minutes=0]
	 * @param {Integer} [value.seconds=0]
	 */
	,set: function(value) {
		this.units.forEach(function (unit) {
			this[unit] = value[unit] || 0;
		}, this);
	}

	/**
	 * Sets all unit components to zero.
	 */
	,zero: function() {
		this.set(0);
	}

	/**
	 * Returns the difference between this value, and the specified one. The returned
	 * value will have the same configuration (rendering template, etc.) as this one.
	 *
	 * @param {Eoze.time.ClockValue/Object}
	 * @return {Eoze.time.ClockValue}
	 */
	,diff: function(value) {
		var result = Ext.clone(this);

		this.units.forEach(function(unit) {
			result[unit] = this[unit] - (value[unit] || 0);
		}, this);

		return result;
	}

	/**
	 * Returns true if all unit components of the value are zero.
	 *
	 * @return {Boolean}
	 */
	,isZero: function() {
		var units = this.units,
			l = units.length,
			i;
		for (i=0; i<l; i++) {
			if (this[units[i]] !== 0) {
				return false;
			}
		}
		return true;
	}
});
