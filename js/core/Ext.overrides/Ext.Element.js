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
 * @since 2013-01-28 00:22
 */
Ext.override(Ext.Element, {

	// this is Ext4 implementation
	scrollIntoView: function(container, hscroll, animate) {
		container = Ext.getDom(container) || Ext.getBody().dom;
		var el = this.dom,
			offsets = this.getOffsetsTo(container),
		// el's box
			left = offsets[0] + container.scrollLeft,
			top = offsets[1] + container.scrollTop,
			bottom = top + el.offsetHeight,
			right = left + el.offsetWidth,
		// ct's box
			ctClientHeight = container.clientHeight,
			ctScrollTop = parseInt(container.scrollTop, 10),
			ctScrollLeft = parseInt(container.scrollLeft, 10),
			ctBottom = ctScrollTop + ctClientHeight,
			ctRight = ctScrollLeft + container.clientWidth,
			newPos;

		if (el.offsetHeight > ctClientHeight || top < ctScrollTop) {
			newPos = top;
		} else if (bottom > ctBottom) {
			newPos = bottom - ctClientHeight;
		}
		if (newPos != null) {
			Ext.get(container).scrollTo('top', newPos, animate);
		}

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
		return this;
	}
});
