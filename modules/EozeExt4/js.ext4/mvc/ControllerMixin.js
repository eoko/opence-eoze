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
 * Base class for {@link Deft.mvc.ViewController controller mixins}.
 *
 * This base implementation simply assert the existence of a {@link #init} method, that must be
 * called in the {@link Deft.mvc.ViewController#init} method.
 *
 * This class provides a static {@link #initMixins} method that can initialize all
 * {@link Eoze.mvc.ControllerMixin} mixins of a given controller.
 *
 * Usage:
 *
 *     Ext.define('MyController', {
 *         extend: 'Deft.ViewController'
 *
 *         ,mixins: [
 *             // ...
 *         ]
 *
 *         ,init: function() {
 *             // Will call the init() method of all ControllerMixin mixins
 *             Eoze.mvc.ControllerMixin.initMixins(this);
 *             // ...
 *         }
 *     });
 *
 * @since 2013-03-27 10:37
 */
Ext4.define('Eoze.mvc.ControllerMixin', {

	statics: {
		/**
		 * Initialize {@link Eoze.mvc.ControllerMixin controller mixins} for the passed controller.
		 *
		 * @param {Deft.mvc.ViewController} controller
		 */
		initMixins: function(controller) {
			var ControllerMixin = Eoze.mvc.ControllerMixin;
			Ext.iterate(controller.mixins, function(name, mixin) {
				if (mixin instanceof ControllerMixin) {
					mixin.init(controller);
				}
			});
		}
	}

	/**
	 * @param {Deft.mvc.ViewController} controller
	 */
	,init: function(controller) {
		this.controller = controller;
	}

	/**
	 * @return {Deft.mvc.ViewController}
	 */
	,getController: function() {
		return this.controller;
	}
});
