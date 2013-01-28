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

Ext.ns('eo');

/**
 *
 * @since 2013-01-27 20:10
 */
eo.ScrollPanel = Ext.extend(Ext.Panel, {

	autoScroll: true

	,initComponent: function() {

		this.tbar = this.createToolbar();

		eo.ScrollPanel.superclass.initComponent.call(this);

		// Init toolbar items
		var tb = this.getTopToolbar(),
			defaults = {
				scope: this
				,handler: this.onScrollToChild
				,enableToggle: true
				,toggleGroup: this.id + '-nav-tb'
				,allowDepress: false
			};
		this.items.each(function(item, i) {
			var title = item.title;

			if (!title) {
				return;
			}

			var button = tb.addButton(Ext.apply({
				text: title
				,scrollTarget: item
				,itemId: 'nav-' + item.id
			}, defaults));

			if (i === 0) {
				button.toggle(true);
			}
		}, this);
	}

	,afterRender: function() {
		eo.ScrollPanel.superclass.afterRender.apply(this, arguments);

		// Scroll event
		this.body.on({
			buffer: 100
			,scope: this
			,scroll: this.trackScroll
		});
	}

	,trackScroll: function() {
		var container = this.body;

		container = Ext.getDom(container) || Ext.getBody().dom;

		var ctClientHeight = container.clientHeight,
			ctScrollTop = parseInt(container.scrollTop, 10),
			ctScrollLeft = parseInt(container.scrollLeft, 10),
			ctBottom = ctScrollTop + ctClientHeight;

		var lastItem,
			lastCollapsed,
			items = this.items,
			i = items.getCount();

		function setLastItem(item) {
			if (item.collapsed) {
				lastCollapsed = item;
			} else {
				lastItem = item;
			}
		}

		// If the view is stuck at the bottom, then try to select the last ct
		if (ctBottom === container.scrollHeight) {
			setLastItem(items.get(i-1));
		} else {
			while (i--) {
				(function(item) {
					var itemEl = item.el,
						el = itemEl.dom,
						offsets = itemEl.getOffsetsTo(container),
						top = offsets[1] + container.scrollTop,
						mid = top + el.offsetHeight / 2,
						topMargin = itemEl.getMargins('t');

					if (mid > ctScrollTop && mid < ctBottom) {
						setLastItem(item);
					}
				})(items.get(i));
			}
		}

		// Default on last collapsed if no expanded is into view
		lastItem = lastItem || lastCollapsed;

		if (lastItem) {
			var tb = this.getTopToolbar(),
				itemId = 'nav-' + lastItem.id,
				cmp = tb.getComponent(itemId),
				pressed = cmp && cmp.pressed;

			if (!pressed) {
				cmp.toggle(true);
			}
		}
	}

//	,onAdd: function(c) {
//		eo.ScrollPanel.superclass.onAdd.apply(this, arguments);
//
//		if (this.rendered) {
//			var tb = this.getTopToolbar();
//			tb.addButton({
//				text: c.title
//			});
//			tb.doLayout();
//		}
//	}

	,onScrollToChild: function(button) {
//		button.scrollTarget.el.scrollIntoView(this.body, false, true);
		this.scrollToChild(button.scrollTarget, false, true);
	}

	,scrollToChild: function(item, hscroll, animate) {
		var itemEl = item.el,
			container = this.body;

		container = Ext.getDom(container) || Ext.getBody().dom;

		var el = itemEl.dom,
			offsets = itemEl.getOffsetsTo(container),
		// el's box
			left = offsets[0] + container.scrollLeft,
			top = offsets[1] + container.scrollTop,
//			bottom = top + el.offsetHeight,
			right = left + el.offsetWidth,
		// ct's box
//			ctClientHeight = container.clientHeight,
//			ctScrollTop = parseInt(container.scrollTop, 10),
			ctScrollLeft = parseInt(container.scrollLeft, 10),
//			ctBottom = ctScrollTop + ctClientHeight,
			ctRight = ctScrollLeft + container.clientWidth,
			newPos;

		newPos = top;
		newPos -= itemEl.getMargins('t');
		Ext.get(container).scrollTo('top', newPos, animate);

		if (hscroll !== false) {
			newPos = null;
			if (el.offsetWidth > container.clientWidth || left < ctScrollLeft) {
				newPos = left;
			} else if (right > ctRight) {
				newPos = right - container.clientWidth;
			}
			if (newPos != null) {
				Ext.get(container).scrollTo('left', newPos, animate);
			}
		}
	}

	,createToolbar: function() {
		return new Ext.Toolbar({
			defaults: {
				height: 40
			}
			,items: ['->']
		});
	}

});

Ext.reg('eo.scrollpanel', eo.ScrollPanel);
