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
 * Fixes ext3 tooltip by accepting ext3 namespace ext:qtip, along with ext4 ones
 * (data-qtip).
 *
 * @since 2013-01-29 16:38
 */
Ext4.define('Eoze.Ext3.tip.QuickTip', {
	override: 'Ext4.tip.QuickTip'

	/**
	 * @inheritdoc
	 */
	,getTipCfg: function(e) {
		var t = e.getTarget(),
			titleText = t.title,
			cfg;

		if (this.interceptTitles && titleText && Ext.isString(titleText)) {
			t.qtip = titleText;
			t.removeAttribute("title");
			e.preventDefault();
			return {
				text: titleText
			};
		}
		else {
			cfg = this.tagConfig;
			t = e.getTarget('[' + cfg.namespace + cfg.attribute + ']');
			if (t) {
				return {
					target: t,
					text: t.getAttribute(cfg.namespace + cfg.attribute)
				};
			}

			// rx+ // Catch Ext3 tooltips
			return this.getExt3TipCfg(e);
		}
	}

	// rx+ // private
	,getExt3TipCfg: function(e) {
		var t = e.getTarget(),
			cfg = this.tagConfig,
			ext3Ns = Ext.QuickTip.prototype.tagConfig.namespace,
			text;
		if (cfg.attribute === 'qtip' && (text = t.qtip)) {
			return {
				target: t
				,text: text
			};
		} else if ((text = Ext.fly(t).getAttribute(cfg.attribute, ext3Ns))) {
			return {
				target: t
				,text: text
				,namespace: ext3Ns.namespace
			};
		}
	}

	// private
	,onTargetOver : function(e){
		var me = this,
			target = e.getTarget(me.delegate),
			hasShowDelay,
			delay,
			elTarget,
			cfg,
			ns,
			tipConfig,
			autoHide,
			targets, targetEl, value, key;

		if (me.disabled) {
			return;
		}

		// TODO - this causes "e" to be recycled in IE6/7 (EXTJSIV-1608) so ToolTip#setTarget
		// was changed to include freezeEvent. The issue seems to be a nested 'resize' event
		// that smashed Ext.EventObject.
		me.targetXY = e.getXY();

		// If the over target was filtered out by the delegate selector, or is not an HTMLElement, or is the <html> or the <body>, then return
		if(!target || target.nodeType !== 1 || target == document.documentElement || target == document.body){
			return;
		}

		if (me.activeTarget && ((target == me.activeTarget.el) || Ext.fly(me.activeTarget.el).contains(target))) {
			me.clearTimer('hide');
			me.show();
			return;
		}

		if (target) {
			targets = me.targets;

			for (key in targets) {
				if (targets.hasOwnProperty(key)) {
					value = targets[key];

					targetEl = Ext.fly(value.target);
					if (targetEl && (targetEl.dom === target || targetEl.contains(target))) {
						elTarget = targetEl.dom;
						break;
					}
				}
			}

			if (elTarget) {
				me.activeTarget = me.targets[elTarget.id];
				me.activeTarget.el = target;
				me.anchor = me.activeTarget.anchor;
				if (me.anchor) {
					me.anchorTarget = target;
				}
				hasShowDelay = Ext.isDefined(me.activeTarget.showDelay);
				if (hasShowDelay) {
					delay = me.showDelay;
					me.showDelay = me.activeTarget.showDelay;
				}
				me.delayShow();
				if (hasShowDelay) {
					me.showDelay = delay;
				}
				return;
			}
		}

		// Should be a fly.
		elTarget = Ext.fly(target, '_quicktip-target');
		cfg = me.tagConfig;
		// rx- // ns = cfg.namespace;
		tipConfig = me.getTipCfg(e);

		if (tipConfig) {
			ns = tipConfig.namespace; // rx+

			// getTipCfg may look up the parentNode axis for a tip text attribute and will return the new target node.
			// Change our target element to match that from which the tip text attribute was read.
			if (tipConfig.target) {
				target = tipConfig.target;
				elTarget = Ext.fly(target, '_quicktip-target');
			}
			autoHide = elTarget.getAttribute(ns + cfg.hide);

			me.activeTarget = {
				el: target,
				text: tipConfig.text,
				width: +elTarget.getAttribute(ns + cfg.width) || null,
				autoHide: autoHide != "user" && autoHide !== 'false',
				title: elTarget.getAttribute(ns + cfg.title),
				cls: elTarget.getAttribute(ns + cfg.cls),
				align: elTarget.getAttribute(ns + cfg.align)

			};
			me.anchor = elTarget.getAttribute(ns + cfg.anchor);
			if (me.anchor) {
				me.anchorTarget = target;
			}
			hasShowDelay = Ext.isDefined(me.activeTarget.showDelay);
			if (hasShowDelay) {
				delay = me.showDelay;
				me.showDelay = me.activeTarget.showDelay;
			}
			me.delayShow();
			if (hasShowDelay) {
				me.showDelay = delay;
			}
		}
	}

	// Fixes null element error in parent method
	,cancelShow: function(el){
		if (Ext4.get(el)) {
			this.callParent(arguments);
		}
	}

});
