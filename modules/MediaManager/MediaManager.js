(function() {

var NS = Ext.ns("Oce.Modules.MediaManager");
    
Oce.Modules.MediaManager.MediaManager = Ext.extend(Oce.Modules.MediaManager.MediaManagerBase, {

    constructor: function() {
        spp.constructor.apply(this, arguments);
        this.addEvents("open");
    }
    
    ,open: function(destination) {
        if (!destination) destination = Oce.mx.application.getMainDestination();
        var p = this.mediaPanel || this.createMediaPanel();
        destination.add(p);
        p.show();
        this.fireEvent("open", this, destination);
    }
    
    ,moduleActions: {
        open: function(cb, scope, args) {
            this.on({
                single: true
                ,open: cb
                ,scope: scope
            });
            return this.open.apply(this, args);
        }
    }

    ,createMediaPanel: function() {
        
        var viewToggleGroup = Ext.id(null, 'view-toggle-group');
        
        var mediaPanel = this.mediaPanel = new eo.MediaPanel({
            title: "Media Manager" // i18n
            ,closable: true
            ,tbar: new Ext.Toolbar({
                items: [this.uploadFormPanel = Ext.widget({
                    xtype: 'form'
                    ,fileUpload: true
//                    ,padding: 0
//                    ,margin: 0
                    ,border: false
                    ,bodyStyle: 'background: none;'
                    ,hideLabels: true
                    ,itemCls: 'no-margin'
                    ,items: this.uploadButton = Ext.widget({
                        xtype: 'fileuploadfield'
                        ,name: 'image'
                        ,buttonOnly: true
//                        ,buttonText: 'Envoyer un fichier'
                        ,buttonCfg: {
                            text: "Envoyer un fichier" // i18n
                            ,iconCls: 'ico add'
                        }
                        ,listeners: {
                            fileselected: this.uploadFile
                            ,scope: this
                        }
                    })
                }), '-', {
                    xtype: 'button'
                    ,text: "Supprimer" // i18n
                    ,iconCls: 'ico cross'
                    ,handler: this.deleteSelected.createDelegate(this)
                }, '-', {
                    xtype: 'button'
                    ,text: "Télécharger" // i18n
                    ,iconCls: 'ico download'
                    ,handler: this.downloadSelected.createDelegate(this)
                }, '->', {
                    xtype: 'button'
                    ,iconCls: 'ico application_view_tile'
                    ,tooltip: "Affichage en icônes" // i18n
                    ,allowToggle: true
                    ,allowDepress: false
                    ,toggleGroup: viewToggleGroup
                    ,viewType: 'icons'
                    ,pressed: true
                    ,scope: this
                    ,toggleHandler: this.onViewChange
                }, {
                    xtype: 'button'
                    ,iconCls: 'ico application_view_list'
                    ,tooltip: "Affichage en liste"
                    ,allowToggle: true
                    ,allowDepress: false
                    ,toggleGroup: viewToggleGroup
                    ,viewType: 'list'
                    ,scope: this
                    ,toggleHandler: this.onViewChange
                }]
            })
            ,listeners: {
                close: function() {
                    delete this.mediaPanel;
                }
                ,scope: this
            }
            ,onDelete: this.deleteSelected.createDelegate(this)
        });
        
        this.uploadForm = this.uploadFormPanel.getForm();

        return this.mediaPanel;
    }
    
    /**
     * Handler for view change buttons.
     * @private
     */
    ,onViewChange: function(button, state) {
        var mp = this.mediaPanel;
        if (mp && state) {
            mp.setViewType(button.viewType);
        }
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
    
    ,downloadSelected: function() {
        var mp = this.mediaPanel,
            sr = mp.getSelectedRecord();
        if (!sr) return;
        window.open(sr.json.url);
    }

    ,uploadFile: function(fileSelector, v, path) {
        this.uploadForm.submit({
            url: "api"
            ,params: {
                controller: "media.grid" // TODO modularize
                ,action: "upload"
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
//        this.uploadButton.disable();
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

    ,xls: function(record) {
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

var spp = Oce.Modules.MediaManager.MediaManager.superclass;

})(); // closure
