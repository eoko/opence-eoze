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
 * This override is part of {@link Eoze.Ext.tree.HideableNodeOverride}.
 *
 * It implements the actual hiding behaviour, by adding the style `display: none` to the
 * node's element if the record has its `hidden` field set to `true`.
 *
 * @since 2013-03-27 16:46
 */
Ext4.define('Eoze.Ext.tree.Column', {
	override: 'Ext.tree.Column'

	,treeRenderer: function(value, metaData, record, rowIdx, colIdx, store, view) {

		if (record.get('hidden')) {
			metaData.style = metaData.style || '';
			metaData.style += 'display: none;';
		}

		return this.callParent(arguments);
	}
});
