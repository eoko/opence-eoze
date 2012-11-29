/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

Oce.LengthyOperation = Ext.extend(Ext.util.Observable, {

	title: "Veuillez patienter" // i18n
	,msg: "Exécution en cours" // i18n
	,progressText: undefined
	,limit: 100

	,constructor: function(config) {
		Oce.LengthyOperation.superclass.constructor.apply(this, arguments);
		Ext.apply(this, config);
	}

	,start: function() {

//		var mb = this.mb = Ext.MessageBox.show({
//			title: this.title,
//			msg: this.msg,
//			progressText: this.progressText,
//			width:300,
//			progress:true,
//			closable:false,
//			//animEl: 'samplebutton',
//			modal: false
//		});
		var me = this,
		
		mb = this.mb = new Ext.Window({
			title: this.title
			,width: 340
			,height: 125
			,layout: {
				type: "vbox"
				,align: "center"
				,padding: 15
			}
			,items: [
				this.progressBox = Ext.widget({
					xtype: "box"
					,html: this.progressText
					,height: 30
					,width: "80%"
				})
				,this.progress = Ext.widget({
					xtype: "progress"
					,width: "80%"
				})
			]
			,fbar: {
				buttons: [
					this.retryButton = Ext.widget({
						xtype: "button"
						,text: "Réessayer" // i18n
						,handler: this.executeNextPass.createDelegate(this)
						,hidden: true
					})
					,this.closeButton = Ext.widget({
						xtype: "button"
						,text: "Fermer" // i18n
						,handler: function() {
							mb.close();
						}
					})
				]
			}
		});
		
		this.closeButton.hide();
		mb.show();
		
		Oce.Ajax.request({
			
			params: this.params

			,onSuccess: function (obj) {
				
				if (obj.text) {
					me.progressBox.update(obj.text);
				}

				me.total = 0;
				Ext.each(obj.counts, function(pass) {
					me.total += pass.count;
				});

				me.passes = obj.counts;

				me.curPass = me.passes.pop();
				me.curPass.start = 0;
				me.curPass.remains = me.curPass.count;
				me.executeNextPass();
			}
			,onFailure: function() {
				// TODO failure message handling
				me.closeButton.show();
				me.progressBox.update("L'opération n'a pas pu être achevée correctement.");
			}
		});
	}

	,executeNextPass: function() {
		
		var me = this
			,cp = this.curPass
			;

		Oce.Ajax.request({
			params: Ext.apply({
				start: this.curPass.start
				,limit: this.limit
			}, cp.params, this.params)

			,onSuccess: function (obj) {

				var n = parseInt(obj.processed);

				cp.remains -= n;

				if (obj.text) {
					me.progressBox.update(obj.text);
				}

				me.progress.updateProgress(
					(me.total - cp.remains) / me.total,
					obj.progressText
				);
				
				if (cp.remains > 0) {
					cp.start += n;
				} else if (me.passes.length > 0) {
					me.curPass = me.passes.pop();
					me.curPass.start = me.limit;
					me.curPass.remains = me.curPass.count;
				} else {
					return me.finish(obj);
				}

				return me.executeNextPass();
			}

			,onFailure: function() {
				// TODO failure message handling
				me.closeButton.show();
				if (me.retryOnFailure) me.retryButton.show();
				me.progressBox.update("L'opération n'a pas pu être achevée correctement.");
			}
		});
	}

	,finish: function(obj) {
		this.closeButton.show();
		this.progressBox.update("L'opération a été complétée avec succès.");
	}
})