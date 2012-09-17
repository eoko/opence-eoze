Ext.ns('Oce.win');

// ----<<  TemplatePage  >>-----------------------------------------------------

Oce.win.TemplatePage = Ext.extend(Ext.Panel, {

	constructor: function(config) {
		Ext.applyIf(config, {
			title: config.tabName || ('' + undefined)
			,tabName: (config.title || ('' + undefined)).toLowerCase()
			,autoScroll: true
			,defaults:{anchor:'-20'}
			,layout: 'fit'
		});
		Oce.win.TemplatePage.superclass.constructor.apply(this, arguments);
	}

	,onRender: function() {
		Oce.win.TemplatePage.superclass.onRender.apply(this, arguments);

		var win = this.findParentBy(function(p){return p instanceof Oce.FormWindow || p instanceof Oce.FormWindowPanel})
			,formPages = win.formPages
			;

		if (this.el) {
			this.addClass('oce-form-info');
		}

		var updateFn = function() {
			if (!this.el) return; // if the panel has already been closed
			var formPages = win.formPages; // !!! Must be read again, may have changed

			if (!formPages) return;
			
			if (!this.page) {
				throw new Error('Missing page information: ' + this.page);
			}
			if (this.page in formPages) {
				this.el.update(formPages[this.page]);
			} else {
				this.el.update('Élément manquant'); // i18n
			}
		}.createDelegate(this)

		// The form will be refreshed the first time it is
		// rendered
		win.formRefreshers.push(updateFn);

		if (formPages !== null) {
			// Means the window has already been rendered,
			// we must do the first update ourselves
			updateFn();
		}
	}
})

Ext.reg('oce.wintpl', Oce.win.TemplatePage);


//Oce.deps.wait('Ext.ux.GroupTabPanel', function() {
//	Oce.win.GroupTabFormPanel = Ext.extend(Ext.ux.GroupTabPanel, {
//
//		constructor: function(config) {
//
//			var items = [];
//
//			if (!Ext.isArray(config.items)) throw new Exception('Illegal argument, items must be an array');
//			Ext.each(config.items, function(item) {
//				Ext.applyIf(item, {
//					expanded: true
//
//					,deferredRender:false
//					,hideMode:'offsets'
//
//					,defaults:{
//						 layout:'form'
//						,autoScroll: true
//						,defaultType:'textfield'
//						,bodyStyle:'padding:10px;  background:transparent;'
//					}
//
//					//,items: iterateTabItems(groupTabConfig)
//				});
//			})
//
//			delete config.items;
//
//			Ext.applyIf(config, {
//	//			 xtype:'grouptabpanel'
//
//				tabWidth: 130
//				,activeGroup: 0
//
//	//			,width: tabConfig.windowWidth
//	//			,height: tabConfig.windowHeight
//
//				// Is this necessary with GroupTab ??
//				// this line is necessary for anchoring to work at
//				// lower level containers and for full height of tabs
//				,anchor:'100% 100%'
//
//				,items: tabPanelItems
//			});
//
//			Oce.win.GroupTabFormPanel.superclass.constructor.call(config);
//		}
//	})
//
//	Ext.reg('oce.grouptabform', Oce.win.GroupTabFormPanel);
//});
