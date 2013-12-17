Ext.ns("eo");

eo.MediaPanel = Ext.extend(Ext.Panel, {

    constructor: function(config) {

        var me = this;

        this.initTemplate();

        var dirTree = this.dirTree = new eo.MediaPanel.TreePanel({
            region: "west"
            ,split: true
//            ,collapsible: true
            ,collapseMode: "mini"
//            ,header: false
            ,title: "Navigation" // i18n
            ,width: 150
            ,flex: 1

            ,loadParams: {
                controller: "media.grid"
                ,action: "getMediaDirectories"
            }
        });

        var onDelete = config.onDelete || this.onDelete;
        
        // Store
        var store = new Ext.data.JsonStore({
            url: "api"
            ,baseParams: {
                controller: "media.grid"
                ,action: "load"
            }
            ,root: 'data'
            ,totalProperty: 'count'
            ,fields: [
                "filename", "url", "imageUrl", "bytesize", "size", "extension", "filemtime", 
                "hsFilemtime", "mime", "type"
            ]
            ,createSortFunction: function(field, direction) {
                var createSortFunction = Ext.data.JsonStore.prototype.createSortFunction,
                    baseFn = createSortFunction.call(this, field, direction);

                // Account for virtual items (.. directory)
                var defaultFn = function(r1, r2) {
                    var v1 = r1.data.virtual,
                        v2 = r2.data.virtual;
                    return v1
                            ? (v2 ? baseFn(r1, r2) : -1)
                            : (v2 ? 1 : baseFn(r1, r2));
                };
                    
                switch (field) {
                    case 'size':
                        baseFn = createSortFunction.call(this, 'bytesize', direction);
                    case 'type':
                        return function(r1, r2) {
                            var m1 = r1.data.mime === 'folder',
                                m2 = r2.data.mime === 'folder';
                            return m1
                                    ? (m2 ? defaultFn(r1, r2) : -1)
                                    : (m2 ? 1 : defaultFn(r1, r2));
                            
                        };
                    default:
                        return defaultFn;
                }
            }
            ,loadRecords: function(o, options, success) {
                if (this.isDestroyed === true) {
                    return;
                }
                if (!o || success === false){
                    if(success !== false){
                        this.fireEvent('load', this, [], options);
                    }
                    if(options.callback){
                        options.callback.call(options.scope || this, [], options, false, o);
                    }
                    return;
                }
                // add parent directory item (if not root node)
                var sn = dirTree.getSelectionModel().getSelectedNode(),
                    root = !sn || sn.isRoot;
                if (!root) {
                    var r = o.records;
                    r.unshift(
                        new this.recordType({
                            virtual: true
                            ,filename: ".."
                            ,mime: 'folder'
                            ,type: 'Dossier'})
                    );
                    o.totalRecords = r.length;
                }
                Ext.data.JsonStore.prototype.loadRecords.apply(this, arguments);
            }
        });
        
        // Context menu
        var contextMenu = new Ext.menu.Menu();
        if (onDelete) {
            contextMenu.add({
                text: "Supprimer"
                ,iconCls: "ico cross"
                ,handler: onDelete
            })
        }
        
        // MediaPanel
        this.view = this.iconPanel = new eo.MediaPanel.ImageView({
            region: "center"
            ,store: store
            ,listeners: {
                selectionchange: {
                    fn: this.showDetails
                    ,scope: this
                    ,buffer: 100
                }
                ,dblclick: function(view, index, node, e) {
                    me.onRecordDblClick(view.getRecord(node));
                    e.preventDefault();
                }
                ,contextmenu: function(view, index, node, event) {
                    view.select(node, false);
                    var r = view.getRecord(node);
                    if (r && !r.data.virtual) {
                        contextMenu.showAt([
                            event.browserEvent.clientX
                            ,event.browserEvent.clientY
                        ]);
                    }
                    event.preventDefault();
                }
            }
        });
        
        // List Grid
        var grid = this.listGrid = new Ext.grid.GridPanel({
            
            cls: 'x-list-view'
            
            ,border: false
            
            ,columns: [{
                dataIndex: 'filename'
                ,id: 'filename'
                ,header: "Nom" // i18n
                ,width: 200
                ,sortable: true
                ,renderer: function(value, md, record) {
                    return '<span class="mime-16 ' + record.data.mime + '"></span>'
                            + value;
                }
            },{
                dataIndex: 'size'
                ,header: "Taille" // i18n
                ,width: 50
                ,sortable: true
            },{
                dataIndex: 'type'
                ,header: "Type" // i18n
                ,width: 80
                ,sortable: true
            },{
                dataIndex: 'hsFilemtime'
                ,header: "Date de modification" // i18n
                ,width: 80
                ,sortable: true
            }]
        
            ,sm: new Ext.grid.RowSelectionModel({
                singleSelect: true
                ,listeners: {
                    selectionchange: {
                        fn: this.showDetails
                        ,scope: this
                        ,buffer: 100
                    }
                }
            })
            
            ,stripeRows: true
            ,autoExpandColumn: 'filename'
            ,viewConfig: {
                autoFill: true
            }
        
            ,store: store
            ,loadMask: {
                msg: "Chargement..." // i18n
            }
            
            ,listeners: {
                scope: this
                ,rowcontextmenu: function(grid, row, e) {
                    // Select row
                    var r = grid.store.getAt(row);
                    grid.select(r);
                    
                    // Show menu
                    if (r && !r.data.virtual) {
                        contextMenu.showAt([
                            e.browserEvent.clientX
                            ,e.browserEvent.clientY
                        ]);
                    }
                    
                    e.preventDefault();
                }
                ,rowdblclick: function(grid, row, e) {
                    this.onRecordDblClick(grid.store.getAt(row));
                    e.preventDefault();
                }
            }
        
            ,getSelectedRecord: function() {
                return this.getSelectionModel().getSelected();
            }
            
            ,select: function(record) {
                this.getSelectionModel().selectRecords([record])
            }
        });
        
        // Detail panel
        var detailPanel = this.detailPanel = new Ext.Panel({
            region: "east"
            ,split: true
            ,collapsible: true
            ,collapseMode: "mini"
            ,header: false
            ,width: 200
            ,flex: 1
            ,border: false
            ,height: 250
            ,cls: "img-chooser-dlg"
        });

        var leftPane = new Ext.Panel({
            region: "west"
            ,layout: {
                type: "vbox"
                ,align: "stretch"
            }
            
            ,items: [detailPanel, dirTree]

            ,width: 250
            ,header: false
            ,border: false
            ,split: true
            ,collapsible: true
            ,collapseMode: "mini"
        })

        config = Ext.apply({}, config, {
            layout: "border"
//            ,items: [dirTree, view, detailPanel]
            ,border: false
//            ,items: [leftPane, view]
            ,cls: 'x-eo-media-panel'
            ,items: [leftPane, this.viewCardCt = Ext.widget({
                xtype: 'container'
                ,region: 'center'
                ,layout: 'card'
                ,activeItem: 0
                ,items: [this.iconPanel, this.listGrid]
            })]
        });
        
        eo.MediaPanel.superclass.constructor.call(this, config);

        dirTree.on('selectionchange', function(sm, node) {
            var path = node.attributes.path;
            store.load({
                params: {
                    path: path
                }
                ,callback: function() {
                    me.currentPath = path;
                }
            });
        });

        dirTree.load({expand: true});
    }
    
    // private
    ,onRecordDblClick: function(r) {
        if (r) {
            var d = r && r.data,
                folder = d && d.mime === 'folder' && d.filename,
                dt = this.dirTree;
            if (folder) {
                dt.changeDirectory(folder);
            } else {
                this.fireEvent('dblclick', this, r);
            }
        }
    }
    
    ,setViewType: function(type) {
        var ct = this.viewCardCt,
            layout = ct && ct.getLayout(),
            view,
            sr = this.getSelectedRecord();
        
        switch (type) {
            case 'list':
                view = this.listGrid;
                break;
            case 'icons':
                view = this.iconPanel;
                break;
        }
        
        this.view = view;
        this.view.select(sr);
        
        if (layout) {
            layout.setActiveItem(view.id);
        }
    }

    ,reload: function(reloadDetails) {
        var me = this,
            cb = reloadDetails === false ? Ext.emptyFn 
            : function(r, opts, success) {
                if (success) me.showDetails();
            };
        
        this.view.store.reload({
            callback: cb
        });
    }

    ,getSelectedRecord: function() {
        return this.view.getSelectedRecord();
    }
    
    ,getDirectoryPath: function() {
        var sm = this.dirTree.getSelectionModel(),
            sn = sm.getSelectedNode();
            
        if (!sn) return null;

        if (sn.attributes.path) return sn.attributes.path;
        else return "";
    }

    ,getSelectedItemUrl: function() {
        var records = this.view.getSelectedRecords();
        if (!records.length) return null;
        var r = records[0];
        return r.data.url;
    }

    ,initTemplate: function() {
        this.detailsTemplate = new Ext.XTemplate(
            '<div class="details">',
                '<tpl for=".">',
                    '<div class="details-info-image-ct">',
                        '<div class="ct">',
                            '<img src="{imageUrl}" class="{mime}" />',
                            '<span class="mime-64 mimeIcon {mime}"></span>',
                        '</div>',
                    '</div>',
                    '<div class="details-info">',
                        '<b>Fichier : </b>', // i18n
                        '<span>{filename}</span>',
                        '<b>Taille : </b>', // i18n
                        '<span>{size}</span>',
                        '<b>Dernière modification :</b>', // i18n
                        '<span>{hsFilemtime}</span>',
                    '</div>',
                '</tpl>',
            '</div>'
        );
        this.detailsTemplate.compile();
    }

    ,showDetails: function() {
        var detailEl = this.detailPanel.body,
            r = this.getSelectedRecord();
        if (r) {
            detailEl.hide();
            this.detailsTemplate.overwrite(detailEl, r.data);
            detailEl.slideIn('l', {stopFx: true, duration:.2});
        } else {
            detailEl.update("<br/>")
        }
    }

}); // eo.MediaPanel

/**
 * MediaPanel's view.
 */
eo.MediaPanel.ImageView = Ext.extend(Ext.DataView, {
    
    /**
     * @cfg {Integer}
     * Maximum label height. If the actual height of the rendered label would 
     * exceed that height, then the text will be truncated and an ellipsis
     * will be appended to its end.
     */
    maxLabelHeight: 40

    ,constructor: function(config) {

//        var prepareData = function(data) {
//            var mtime = Date.parseDate(data.filemtime, "Y-m-d H:i");
//            data.dateString = mtime ? mtime.format("d/m/Y H:i") : "Inconnue"; // i18n
//            return data;
//        };

        config = Ext.apply({}, config, {
            tpl: this.createTemplate()
            ,cls: 'img-chooser-view'
            ,overClass:'x-view-over'
            ,itemSelector: 'div.thumb-wrap'
            ,singleSelect: true
            ,emptyText : '<div style="padding:10px;">Dossier vide</div>'
//            ,prepareData: prepareData.createDelegate(this)
        });

        eo.MediaPanel.ImageView.superclass.constructor.call(this, config);
    }
    
    ,getSelectedRecord: function() {
        var records = this.getSelectedRecords();
        if (!records.length) return null;
        return records[0];
    }
    
    ,onBeforeLoad: function() {
        eo.MediaPanel.ImageView.superclass.onBeforeLoad.apply(this, arguments);
        this.el.mask("Chargement", "x-mask-loading"); // i18n
    }

    ,createTemplate: function() {
        var tpl = new Ext.XTemplate(
            '<tpl for=".">',
                '<div class="thumb-wrap {mime}" id="{nodeId}">',
                    '<div class="thumb">',
                        '<div class="ct">',
                            '<img src="{imageUrl}" title="{filename}" class="{mime}" />',
                            '<span class="mime-64 {mime}"></span>',
                        '</div>',
                    '</div>',
                    '<span class="label">{filename}</span>',
                '</div>',
            '</tpl>'
        );
        tpl.compile();
        return tpl;
    }
    
    // private
    //
    // overridden to implement maxLabelHeight
    //
    ,refresh: function() {
        eo.MediaPanel.ImageView.superclass.refresh.apply(this, arguments);
        var mh = this.maxLabelHeight;
        if (mh) {
            Ext.each(this.el.query('.thumb-wrap > span'), function(span) {
                var t = span.innerText,
                    w = span.offsetWidth;
                while (span.offsetHeight > mh) {
                    t = t.substr(0, t.length-1);
                    span.innerText = t + '\u2026';
                }
            });
        }
    }
});

eo.MediaPanel.TreePanel = Ext.extend(Ext.tree.TreePanel, {

    constructor: function(config) {
        config = Ext.apply({}, config, {
            useArrows: true
            ,autoScroll: true
            ,animate: true
            ,containerScroll: true
            ,border: false
            ,width: 200
            ,root: {
                nodeType: 'node'
                ,text: 'Media'
//                ,singleClickExpand: true
//                ,expandable: true
            }
        });

        eo.MediaPanel.TreePanel.superclass.constructor.call(this, config);

        var sm = this.getSelectionModel();
        this.relayEvents(sm, ["selectionchange"]);

        sm.on("selectionchange", function(me, node, old) {
            if (old && !old.isExpanded() && old.iconElement) {
                old.iconElement.removeClass("open");
            }
            if (node && node.iconElement) {
                node.expand();
                node.iconElement.addClass("open");
            }
        });
    }

    ,loadDirs: function(dirs, expand) {

        var pushDirs = function(node, dirs) {
            Ext.each(dirs, function(dir) {
                var child = new Ext.tree.TreeNode({
                    text: dir.name
                    ,directory: dir.name
                    ,path: dir.path
                    ,iconCls: "img-chooser-tree-folder"
//                    ,expandable: true
//                    ,singleClickExpand: true
                    ,listeners: {
                        beforecollapse: function() {this.iconElement.removeClass("open")}
                        ,beforeexpand: function() {this.iconElement.addClass("open")}
                    }
                });
				var ui = child.getUI()
				ui.render = Ext.Function.createSequence(ui.render, function() {
                    child.iconElement = new Ext.Element(this.getIconEl());
                });

                node.appendChild(child);
                if (dir.children) {
                    pushDirs(child, dir.children);
                }
            });
        };

        var root = this.getRootNode();
        pushDirs(root, dirs);
        if (expand) root.expand();
    }

    ,load: function(opts, callback) {
        if (!opts) opts = {};
        var me = this;
        Oce.Ajax.request(Ext.apply(opts, {
            params: Ext.apply(opts.params || {}, this.loadParams)
            ,onSuccess: function(o) {
                me.loadDirs(o.dirs, opts.expand);
                me.getRootNode().select();
            }
        }));
    }
    
    /**
     * Changes the current selected directory to the specified one, relative
     * to the currently selected node.
     * @param {String} directory
     */
    ,changeDirectory: function(directory) {
        var sm = this.getSelectionModel(),
            node = sm.getSelectedNode(),
            targetNode;
        if (directory === '..') {
            targetNode = node.parentNode;
        } else {
            targetNode = node.findChild('directory', directory);
        }
        if (targetNode) {
            targetNode.select();
        }
    }
});


eo.MediaManager = {

    selectImage: function(callback, scope) {
        var win,
            mp = new eo.MediaPanel({
                listeners: {
                    dblclick: function(mp, img) {
                        callback.call(scope || this, img);
                        win.close();
                    }
                }
            });
        win = new Ext.Window({
            width: 640
            ,height: 480
            ,constrainHeader: true
            ,layout: "fit"
            ,items: mp
            ,title: "Sélectionnez une image" // i18n
            ,buttons: [{
                text: "Ok" // i18n
                ,handler: function() {
                    var r = mp.getSelectedRecord();
                    if (r) callback.call(scope || this, r);
                    win.close();
                }
                ,scope: this
            }, {
                text: "Annuler" // i18n
                ,handler: function() {
                    win.close();
                }
            }]
        });
        win.show();
    }
}
function testMediaWindow() {
    var win = new Ext.Window({
        width: 640
        ,height: 480
        ,layout: "fit"
        ,items: new eo.MediaPanel
    })

    win.show();
}
