
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
 * @since 2013-01-30 14:44
 */
Ext4.define('Eoze.modules.CkEditor.form.field.CkEditor', {
	extend: 'Ext.form.field.TextArea'
	,alias: 'widget.ckeditor'
	,alternateClassName: ['Eoze.form.field.CkEditor', 'Eoze.form.CkEditor']

	,afterRender: function() {
		this.callParent(arguments);

		var editor = CKEDITOR.replace(this.inputEl.id, Ext.apply({
			width: this.width
			,height: this.height

			,toolbarGroups: [
				{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
				{ name: 'paragraph',   groups: [ 'align' ] }
			]

//			,removePlugins: 'about,list,indent,link,dialog,dialogui'
//			,extraPlugins: 'align'
//			,removePlugins: 'about,link,elementspath'
		}, this.config));

		// Store ref
		this.ckEditor = editor;

		editor.on('instanceReady', function() {
			editor.resize(this.width, this.height);
			this.editorReady = true;
			this.setValue(this.value || '');
		}, this);
	}

	// private
	,injectInlineStyle: function(style) {

		function getWin(iframeEl) {
			return Ext4.isIE ? iframeEl.dom.contentWindow : window.frames[iframeEl.dom.name];
		}

		function getDoc(iframeEl) {
			return (!Ext4.isIE && iframeEl.dom.contentDocument) || getWin().document;
		}

		return function (css) {
			var iframeEl = Ext4.get(this.ckEditor.container.$).down('iframe'),
				doc = getDoc(iframeEl),
				head = doc.head || doc.getElementsByTagName('head')[0],
				style = doc.createElement('style');

			style.type = 'text/css';

			if (style.styleSheet){
				style.styleSheet.cssText = css;
			} else {
				style.appendChild(document.createTextNode(css));
			}

			head.appendChild(style);
		};
	}()

	,setValue: function(value) {
		this.callParent(arguments);
		if (this.editorReady) {

			this.ckEditor.setData(value);

			// Inject content style
			if (this.editorReady) {
				this.injectInlineStyle(this.contentStyle);
			}
		}
		return this;
	}

	,getValue: function() {
		return this.ckEditor.getData();
	}

	,afterComponentLayout:function (width, height) {
		this.callParent(arguments);
		if (this.ckEditor && this.ckEditor.container) {
			this.ckEditor.resize(width, height);
		} else {
			this.width = width;
			this.height = height;
		}
	}

});
