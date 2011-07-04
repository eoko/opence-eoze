(function() {

var NS = Ext.ns("Oce.Modules.MediaManager");

NS.MediaManager = eo.Class({

	open: function(destination) {
		if (!destination) destination = Oce.mx.application.getMainDestination();
		var p = this.mediaPanel || this.createMediaPanel();
		destination.add(p);
		p.show();
	}
	
	// protected
	,createToolbar: function(config) {
		return Ext.create(Ext.apply({
			xtype: "toolbar"
			,items: this.createToolbarItems()
		}, config));
	}
	
	// private
	,createUploadButton: function(config) {
		
		config = config || {};
		
		var button,
			opts = { destination: config.destination };
			
		var r = Ext.create({
			xtype: "form"
			,fileUpload: true
			,border: false
			,bodyStyle: "background: none;"
			,hideLabels: true
			,itemCls: "no-margin"
			,items: button = Ext.create({
				xtype: "fileuploadfield"
				,name: "image"
				,buttonOnly: true
				,buttonCfg: {
					text: config.label || "Envoyer un fichier"
					,iconCls: "ico add"
				}
				,listeners: {
					fileselected: this.uploadFile.createDelegate(this, [opts])
					,scope: this
				}
			})
		});
		
		Ext.apply(opts, {
			button: button
			,form: r.getForm()
		});
		
		return r;
	}
	
	,createToolbarItems: function() {
		return [
			this.createUploadButton()
			, "-"
			,{
				xtype: "button"
				,text: "Supprimer"
				,iconCls: "ico cross"
				,handler: this.deleteSelected.createDelegate(this)
			}
		];
	}

	,createMediaPanel: function() {
		this.mediaPanel = new eo.MediaPanel({
			title: "Media Manager"
			,closable: true
			,tbar: this.createToolbar()
			,listeners: {
				close: function() {
					delete this.mediaPanel;
				}
				,scope: this
			}
			,onDelete: this.deleteSelected.createDelegate(this)
		});
		
		return this.mediaPanel;
	}
	
	,deleteSelected: function() {
		var mp = this.mediaPanel,
			sr = mp.getSelectedRecord();
		if (!sr) return;
		Oce.Ajax.request({
			params: {
				controller: "media.grid"
				,action: "delete"
				,path: mp.getDirectoryPath() || ""
				,file: sr.data.filename
			}
			,success: function() {
				mp.reload();
			}
		});
	}

	,uploadFile: function(opts) {
		
		var button = opts.button;

		opts.form.submit({
			url: "index.php"
			,params: {
				controller: "media.grid" // TODO modularize
				,action: "upload"
				,destination: opts.destination
				,path: this.mediaPanel.getDirectoryPath() || ""
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
				button.enable();
			}
			,failure: function() {
				Ext.Msg.alert(
					"Erreur",
					"Désolé, l'image n'a pas pu être uploadée."
				);
				button.enable();
			}
			,scope: this
		});
//		button.disable();
	}

});

NS.MimeTypes = {
	image: function(img) {
		var newImg = new Image();
		newImg.src = img.data.url;

		var ratio = newImg.width / newImg.height;

		var lockButton;

		var fp = new Oce.FormPanel({
			width: 300
			,height: 200
			,items: [{
				xtype: "compositefield"
				,fieldLabel: "Dimensions" // i18n
				,items: [{
					xtype: "numberfield"
					,flex: 1
					,name: "width"
					,allowBlank: false
					,value: newImg.width
					,listeners: {
						blur: function() {
							if (lockButton.pressed) {
								fp.form.findField("height").setValue(
									parseInt(this.getValue() * ratio)
								);
							}
						}
					}
				}, {
					xtype: "displayfield"
					,value: " x "
				}, {
					xtype: "numberfield"
					,flex: 1
					,name: "height"
					,allowBlank: false
					,value: newImg.height
					,listeners: {
						blur: function() {
							if (lockButton.pressed) {
								fp.form.findField("width").setValue(
									parseInt(this.getValue() / ratio)
								);
							}
						}
					}
				}, lockButton = new Ext.Button({
					width: 24
					,height: 24
					,enableToggle: true
					,tooltip: "Lock ratio" // i18n
					,pressed: true
				})]
			}, {
				xtype: "checkbox"
				,name: "lightbox"
				,checked: true
				,fieldLabel: "Lightbox"
			}]
		});
		var win = new Oce.FormWindow({
			title: "Options" // i18n
			,formPanel: fp
			,buttons: [{
				text: "Ok" // i18n
				,handler: function() {
					var form = fp.form;

					if (!form.isValid()) return;

					var w = form.findField("width").getValue(),
						h = form.findField("height").getValue();
	
					this.cmp.insertAtCursor(String.format(
						'<img src="{0}" alt="{1}" width="{2}" height="{3}"/>',
						img.data.url, img.data.name, w, h
					));

					win.close();
				}.createDelegate(this)
			}, {
				text: "Annuler" // i18n
				,handler: function() { win.close() }
			}]
			,layout: "fit"
		});
		win.show();
	}

	,csv: function(record) {
		this.cmp.insertAtCursor(String.format(
			'<a href="{0}">{1}</a>',
			record.data.url, record.data.filename
		));
	}
}

Oce.deps.wait('Ext.ux.form.HtmlEditor.Image', function() {
	Ext.override(Ext.ux.form.HtmlEditor.Image, {
		selectImage: function() {
			eo.MediaManager.selectImage(function(img) {
				var mimeFn = NS.MimeTypes[img.data.mime];
				if (mimeFn) {
					mimeFn.call(this, img);
				} else {
					this.insertImage(img.data);
				}
			}, this);
		}
		,insertImage: function(img) {
			this.cmp.insertAtCursor(String.format('<img src="{0}" alt="{1}" />', img.url, img.name));
		}
	});
});

})(); // closure
