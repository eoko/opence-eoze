//Oce.Modules.media.media = Ext.extend(Oce.Modules.media.mediaBase, {
//
//	createAddWindow: function(callback) {
//		return new Ext.Window({
//			width: 320
//			,height: 160
//			,constrainHeader: true
//		});
//	}
//});
Ext.ns('Oce.Modules.MediaManager').MediaManager = eo.Class({

	open: function(destination) {
		if (!destination) destination = Oce.mx.application.getMainDestination();
		var p = this.mediaPanel || this.createMediaPanel();
		destination.add(p);
		p.show();
	}

	,createMediaPanel: function() {
		this.mediaPanel = new eo.MediaPanel({
			title: "Media Manager"
			,closable: true
			,tbar: new Ext.Toolbar({
				items: this.uploadFormPanel = Ext.create({
					xtype: "form"
					,fileUpload: true
//					,padding: 0
//					,margin: 0
					,border: false
					,bodyStyle: "background: none;"
					,hideLabels: true
					,itemCls: "no-margin"
					,items: this.uploadButton = Ext.create({
						xtype: "fileuploadfield"
						,name: "image"
						,buttonOnly: true
						,buttonText: "Envoyer un fichier"
						,listeners: {
							fileselected: this.uploadFile
							,scope: this
						}
					})
				})
			})
			,listeners: {
				close: function() {
					delete this.mediaPanel;
				}
				,scope: this
			}
		});
		
		this.uploadForm = this.uploadFormPanel.getForm();

		return this.mediaPanel;
	}

	,uploadFile: function(fileSelector, v) {
		this.uploadForm.submit({
			url: "index.php"
			,params: {
				controller: "media.grid"
				,action: "upload"
			}
			,success: function(form, o) {
				var obj = Ext.util.JSON.decode(o.response.responseText);
				if (obj.success) {
					this.mediaPanel.reload();
				} else {
					Ext.Msg.alert(
						"Erreur",
						obj.errorMsg || "L'image n'a pas pu être uploadée."
					);
				}
				this.uploadButton.enable();
			}
			,failure: function() {
				Ext.Msg.alert(
					"Erreur",
					"Désolé, l'image n'a pas pu être uploadée."
				);
				this.uploadButton.enable();
			}
			,scope: this
		});
//		this.uploadButton.disable();
	}

});

Oce.deps.wait('Ext.ux.form.HtmlEditor.Image', function() {
	Ext.override(Ext.ux.form.HtmlEditor.Image, {
		selectImage: function() {
			eo.MediaManager.selectImage(function(img) {
				this.insertImage(img.data);
			}, this);
		}
		,insertImage: function(img) {
			this.cmp.insertAtCursor(String.format('<img src="{0}" alt="{1}" />', img.url, img.name));
		}
	});
});
