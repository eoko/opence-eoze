Ext.ns('Ext.ux.menu');

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 6 janv. 2012
 */
Ext.ux.menu.TooltipPlugin = Ext.extend(Ext.menu.Menu, {
	
	init: function(menu) {
		menu.on('afterrender', this.afterRender, menu);
	}
	
	,afterRender: function() {
		var menu = this;
		this.tip = new Ext.ToolTip({
			target: this.getEl().getAttribute('id'),
			renderTo: document.body,
			delegate: '.x-menu-item',
			title: 'dummy title',
			listeners: {
				beforeshow: function updateTip(tip) {
					var mi = menu.findById(tip.triggerElement.id),
						tt = mi && mi.initialConfig.tooltip;
					if(!tt) {
						return false;
					} 
					else if (Ext.isObject(tt)) {
						tip.header.dom.firstChild.innerHTML = tt.title || '';
						tip.body.dom.innerHTML = tt.text;
					} 
					else {
						tip.header.dom.firstChild.innerHTML = '';
						tip.body.dom.innerHTML = tt;
					}
				}
			}
		});
	}
});