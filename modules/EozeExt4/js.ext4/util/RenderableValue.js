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
 * An utility class that holds some value that can be rendered to a string with a template.
 *
 * @abstract
 * @since 2013-03-13 12:44
 */
Ext4.define('Eoze.util.RenderableValue', {

	uses: [
		'Ext4.Template'
	]

	/**
	 * @cfg {Ext.Template}
	 */
	,tpl: undefined

	/**
	 * @property {Ext4.Template} autoTemplate = undefined
	 * @private
	 */

	/**
	 * Creates the rendering template according to the configuration.
	 *
	 * @return {Ext4.Template}
	 * @abstract
	 * @template
	 * @protected
	 */
	,createTemplate: function() {
		throw new Error('Abstract method');
	}

	/**
	 * Marks the {@link Eoze.util.RenderableValue#autoTpl automatically generated template} as
	 * out of date. If {@link Eoze.util.RenderableValue#tpl} is specified, this method won't
	 * have any effect.
	 *
	 * @protected
	 */
	,invalidTemplate: function() {
		delete this.autoTpl;
	}

	/**
	 * Gets the {@link Ext.Template template} to use for rendering the value to
	 * a string.
	 *
	 * @return {Ext4.Template}
	 * @protected
	 */
	,getTemplate: function() {
		var tpl = this.tpl,
			autoTpl = this.autoTpl;

		if (tpl) {
			if (Ext.isString(tpl)) {
				tpl = this.tpl = new Ext4.Template(tpl);
			}
			return tpl;
		} else {
			if (!autoTpl) {
				autoTpl = this.autoTpl = this.createTemplate();
			}
			return autoTpl;
		}

		return tpl;
	}

	/**
	 * @return {String}
	 */
	,toString: function() {
		return this.getTemplate().apply(this);
	}
});
