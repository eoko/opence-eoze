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
 * Requires Eoze overrides.
 *
 * @since 2013-03-26 10:33
 */
Ext4.define('Eoze.Ext.OverridesLoader', {
	singleton: true

	,requires: [
		// adds support for lazy instance creation by xclass
		'Eoze.Ext.ComponentManager',
		// adds animOpen & animClose options
		'Eoze.Ext.window.Window',
		// Formats
		'Eoze.Ext.util.Format',
		// Data types
		'Eoze.Ext.data.Types',
		// Date
		'Eoze.Ext.Date',
		// Model
		'Eoze.Ext.data.Model',
		'Eoze.Ext.data.Field',
		'Eoze.Ext.data.model.DirtyEvents',
		'Eoze.Ext.data.association.HasOne',
		// Component
		'Eoze.Ext.AbstractComponent',
		// fix ComponentQuery to accept square brackets in attributes query: mytype[attribute[x]=value]
		'Eoze.Ext.ComponentQuery.AcceptingSquareBrackets',

		// Grid
		// Expandable dataIndex
		'Eoze.Ext.view.Table.UsingGetterInsteadOfDataObject',
		'Eoze.Ext.data.model.ExpandableFieldNames',

		// Tree with hideable nodes
//		'Eoze.Ext.tree.HideableNodeOverride',
		'Eoze.Ext.ux.GroupTabPanel',

		// Fields
		'Eoze.Ext.form.field.Trigger',
		'Eoze.Ext.form.field.Text',
		// File upload field, fixes rendering
		'Eoze.Ext.form.field.File',

		// ux
		'Eoze.Ext.ux.grid.FiltersFeature'
	]
});
