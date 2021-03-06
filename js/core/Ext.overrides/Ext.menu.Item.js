/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 sept. 2012
 */

/**
 * @class Ext.menu.Item
 * 
 * @override Adds support for tooltips.
 * 
 * @cfg {String/Object} tooltip The tooltip for the button - can be a string to 
 * be used as innerHTML (html tags are accepted) or QuickTips config object
 */
Ext.override(Ext.menu.Item, {
    
    /**
     * @cfg {String} tooltipType
     * The type of tooltip to use. Either 'qtip' (default) for QuickTips or 'title' for title attribute.
     */
    tooltipType: 'qtip'

    // private
    ,onRender: Ext.Function.createSequence(Ext.menu.Item.prototype.onRender, function() {
      if (this.tooltip) {
            this.setTooltip(this.tooltip, true);
        }
    })
    
    /**
     * Sets the tooltip for this Button.
     * @param {String/Object} tooltip. This may be:<div class="mdesc-details"><ul>
     * <li><b>String</b> : A string to be used as innerHTML (html tags are accepted) to show in a tooltip</li>
     * <li><b>Object</b> : A configuration object for {@link Ext.QuickTips#register}.</li>
     * </ul></div>
     * @return {Ext.Button} this
     */
    ,setTooltip: function(tooltip, /* private */ initial) {
		var el = this.el,
            w = this.wrap,
            labels = w && w.query('label'),
            target = labels ? [el].concat(labels) : el;
        if (this.rendered && target) {
            if (!initial) {
                this.clearTip();
            }
            // If we have multiple target, it will be required to use
            // QuikTips.register(). So, if the tooltip is given as a 
            // String, we must convert to a config object (less intrusive
            // mofification).
            if (Ext.isArray(target)) {
                if (Ext.isString(tooltip)) {
                    tooltip = {
                        text: tooltip
                    };
                }
            }
            if (Ext.isObject(tooltip)) {
                Ext.QuickTips.register(Ext.apply({
                      target: target
                }, tooltip));
                this.tooltip = tooltip;
            } else {
                target.dom[this.tooltipType] = tooltip;
            }
        } else {
            this.tooltip = tooltip;
        }
        return this;
    }
    
    ,clearTip: function() {
        var el = this.el,
            w = this.wrap,
            labels = w && w.query('label'),
            target = labels ? [el].concat(labels) : el;
        if (el && Ext.isObject(this.tooltip)) {
            Ext.QuickTips.unregister(target);
        }
    }
});
