/**
 * @author Éric Ortéga
 */

Oce.RibbonButton = Ext.extend(Ext.Button, {
	constructor: function(config) {

		Ext.applyIf(config, {
			width: '48'
			,iconAlign: 'top'
			,scale: 'large'
			,arrowAlign: 'bottom'
		})

		Oce.RibbonButton.superclass.constructor.call(this, config);
	}
})
Ext.reg('oce.rbbutton', Oce.RibbonButton)
