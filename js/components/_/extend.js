Ext.namespace('Oce');

Oce.rootController = 'root';

Oce.AutoloadPanel = Ext.extend(Ext.Panel, {

	constructor: function(config) {

		if ('name' in config == false) {
			throw new Error('MissingRequiredParam');
		}

		var idPrefix = 'idPrefix' in config ? config.idPrefix : 'm_';

		var params = {
			action: config.action !== undefined && config.action || 'get_html'
			,name: config.name
			,page: config.name
			,file: config.name
			,rawFragment: true
		}

		if (config.controller !== undefined)
			params.controller = config.controller;

		Ext.applyIf(config, {

			controller: Oce.rootController,
			id: idPrefix + config.name,
			
			autoLoad: {
				 url: 'index.php'
				,params: Ext.apply(params, config.params)
				,raw: true

// TODO that is needed to inject scripts sometimes, but it creates big problems
// at application opening
				,scripts: true

//				params: {
//					controller: config.controller,
//					action: config.action !== undefined && config.action || 'get_html',
//					file: config.name
//				}
			}
		});
		
		Ext.apply(this, {
			collapsible:true,
			border:false,
			autoScroll:true,
			titleCollapse: true,
			closable: true
		});

		Oce.AutoloadPanel.superclass.constructor.call(this, config);

		this.refresh = function() {
			this.getUpdater().update(config.autoLoad);
			// TODO: error callback (refresh menu)
//				callback : function(response){
//					if (!response) {
//						Ext.MessageBox.alert('Erreur','Une erreur est survenue lors de l\'actualisation du menu.<br\>Veuillez contacter votre prestataire.<br/>Erreur:<br/>'+response)
//					}
//				}});
		}
	}
});

Ext.reg('oce.autoloadpanel', 'Oce.AutoloadPanel');

Oce.w = Ext.extend(Ext.Window, {
    initComponent: function() {
         Ext.applyIf(this,{
			layout: 'fit',
//			bodyStyle: 'padding:10px;',
			resizable : false,
			closable : true,
			constrainHeader: true
		});
		Oce.w.superclass.initComponent.apply(this, arguments);
    }
});
