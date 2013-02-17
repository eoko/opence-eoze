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
 * @since 2012-12-12 18:23
 */
Ext.define('Eoze.GridModule.multisort.Pill', {
	extend: 'Ext.SplitButton'

	/**
	 * @inheritdoc
	 */
	,cls: 'eo-grid-multisort-toolbar'

	/**
	 * @inheritdoc
	 */
	,initComponent: function() {
		var template = Eoze.GridModule.multisort.Pill.template;

		if (!template) {
			template = new Ext.XTemplate(
				'<div id="{4}" style="padding-left: 5px;">',
					'<div class="x-btn pill {3}">',
						'<div class="{1}">',
							'<em class="{2}" unselectable="on"><button type="{0}"></button></em>',
						'</div>',
					'</div>',
				'</div>'
			);
			template.compile();
			Eoze.GridModule.multisort.Pill.template = template;
		}

		this.template = template;

		this.callParent(arguments);
	}

	/**
	 * @inheritdoc
	 */
	,onRender: function() {
		this.callParent(arguments);

		this.el.down('.pill').createChild({
			tag: 'span'
			,cls: 'close-button'
		}).on('click', this.onRemove, this);
	}

	/**
	 * Handlers for close button.
	 *
	 * @private
	 */
	,onRemove: function() {
		this.fireEvent('deleted', this);
	}
});
