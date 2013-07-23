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
 *
 * @since 2013-07-23 15:06
 */
Ext.define('Eoze.CqlixHistory.view.History', {
	extend: 'Ext.view.View'

	,autoScroll: true

	,cls: 'eo-cqlix-history oce-comment-list'

	,itemSelector: 'div.eo-cqlix-history-item'

	,emptyText: "L'historique est vide"

	,tpl: [
'<tpl for=".">',
	'<div class="eo-cqlix-history-item oce-comment-list-item">',
		'<div class="collapse-wrap">',
			'<div class="header">',
				'<div class="collapse-handle"></div>',
				'<p class="eo-comment-author-label">',
					'<span class="author">{user}</span>, {date:dynDate}',
				'</p>',
			'</div>',
			'<div class="oce-markdown oce-comment-body">',
				'<tpl for="entries">',
					'<p>{label}</p>',
					'<tpl if="!Ext.isEmpty(values.deltas)">',
						'<table width="100%">',
							'<thead><tr>',
								'<th>Champ</th>',
								'<th>Ancienne valeur</th>',
								'<th>Nouvelle valeur</th>',
							'</tr></thead>',
							'<tpl for="deltas">',
								'<tr>',
								'<tpl>',
									'<td>{fieldLabel:ifEmpty(values.field)}</td>',
									'<td>{originalValueDisplay:ifEmpty(values.originalValue)}</td>',
//									'<td>{newValueDisplay:ifEmpty(values.newValue)}</td>',
									'<td>{[this.formatCqlixValue(values.newValueDisplay, values.newValue, values.type)]}</td>',
									'',
								'</tpl>',
								'',
								'</tr>',
							'',
							'</tpl>',
						'</table>',
					'</tpl>',
					'',
				'</tpl>',
			'</div>',
		'</div>',
	'</div>',
'</tpl>',
		{
			formatCqlixValue: function(valueDisplay, value, type) {
				if (Ext.isEmpty(valueDisplay)) {
					var escapeText = this.escapeText,
						formats = Ext.util.Format;
					switch (type) {
						case 'date':
							return formats.date(value);
						default:
							debugger
						case 'text':
							return escapeText(value);
					}
				} else {
					return valueDisplay;
				}
			}
			,escapeText: function(text) {
				return text
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;');
			}
		}]
});
})(window.Ext4 || Ext.getVersion && Ext);
