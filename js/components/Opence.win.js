// Probably not used anymore...

Ext.namespace('Oce');

Oce.DefaultWin = Ext.extend(Ext.Window, {
	constructor : function(config) {
		Ext.apply(config, {
			plain : false,
			autoDestroy  : true,
			constrainHeader : true
//			,
		})
		Ext.applyIf(config, {
//			width : 500,
	//		width : 640,
	//		height : 480,
			autoScroll:true,
			closable : true,
			resizable : true,
			maximizable: true,
			footer : true,
			collapsible : true
		})

		Oce.DefaultWin.superclass.constructor.call(this, config);
	}
});




Oce.FormWin = Ext.extend(Ext.Window, {


//	initconf : [{
//		autoScroll:true,
//		closable : true,
//		resizable : true,
//		collapsible : true,
//		plain : false,
//		maximizable: true,
//		footer : true,
//		autoDestroy  : true,
//		constrainHeader : true,
//		width : 640
//	}],

	constructor : function(config) {
		Ext.apply(config, {
			autoScroll:true,
			closable : true,
			resizable : true,
			collapsible : true,
			plain : false,
			maximizable: true,
			footer : true,
			autoDestroy  : true,
			constrainHeader : true
//			,width : 640
		});
//		Ext.apply(config, this.initconf[0]);
		Oce.FormWin.superclass.constructor.apply(this,arguments);
	}
});


Oce.LoadWin = Ext.extend(Ext.Window, {

	constructor : function(config) {

		Ext.apply(config, {
			autoScroll:true,
			closable : true,
			resizable : true,
			collapsible : true,
			plain : false,
			maximizable: true,
			footer : true,
			autoDestroy  : true,
			constrainHeader : true,
			width : 640,
			height : 480,
			tools:[
				{
					id: 'refresh',
//					text:'Refresh',
					handler: this.refresh.createDelegate(this)
				}
			]
		})

		Oce.LoadWin.superclass.constructor.call(this, config);
	},

	refresh : function() {

		this.update();

		 // call base class
		this.getUpdater().update({
			url: this.autoLoad
		});
	}
	
});

