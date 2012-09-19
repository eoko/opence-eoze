/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 17 juil. 2012
 */

Ext.ns('eo.toolbar');

/**
 * A toolbar item displaying a help tooltip on hovering.
 */
eo.toolbar.HelpItem = Ext.extend(Ext.toolbar.TextItem, {
    
    /**
     * @cfg {String} help
     * The help message HTML content.
     */
    
    /**
     * @cfg {String} iconCls
     */
    
    helpWidth: 300
    
    ,onRender: function(ct, position) {
        this.autoEl = {cls: 'xtb-text xtb-help', html: this.text || ''};
        Ext.toolbar.TextItem.superclass.onRender.call(this, ct, position);
        if (this.iconCls) {
            this.el.addClass('x-icon');
            this.el.addClass(this.iconCls);
        }
        var help = this.help;
        if (help) {
			new Ext.ToolTip({
				html: help
				,cls: 'eo-help'
				,showDelay: 0
				,dismissDelay: 0
				,trackMouse: false
				,target: this.el
                // From Ext.QuickTip: The maximum supported value is 500
                ,width: Math.min(500, this.helpWidth)
			});
        }
    }
});

Ext.reg('tbhelp', eo.toolbar.HelpItem);
