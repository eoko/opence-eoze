Ext.ns("eo");

eo.MediaPanel = Ext.extend(Ext.Panel, {

	constructor: function(config) {

		var me = this;

		this.initTemplate();

		var dirTree = this.dirTree = new eo.MediaPanel.TreePanel({
			region: "west"
			,split: true
//			,collapsible: true
			,collapseMode: "mini"
//			,header: false
			,title: "Navigation" // i18n
			,width: 150
			,flex: 1

			,loadParams: {
				controller: "media.grid"
				,action: "getMediaDirectories"
			}
		});

		var store;
		var view = this.view = new eo.MediaPanel.ImageView({
			region: "center"
			,store: store = new Ext.data.JsonStore({
				url: "index.php"
				,baseParams: {
					controller: "media.grid"
					,action: "load"
				}
				,root: 'data'
				,totalProperty: 'count'
				,fields: [
					"filename", "url", "size", "extension", "filemtime", "mime"
				]
			})
			,listeners: {
				selectionchange: {
					fn: this.showDetails
					,scope: this
					,buffer: 100
				}
				,dblclick: function(view, index, node, e) {
					me.fireEvent("dblclick", me, view.getRecord(node));
				}
				,contextmenu: function(view, index, node, event) {
					view.select(node, false);
					var menu1 = new Ext.menu.Menu({
						items: [{
							text: 'Delete'
						}]
					});
					menu1.showAt([
		                event.browserEvent.clientX
				        ,event.browserEvent.clientY
					]);
					event.preventDefault();
				}
			}
		});

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
//			,items: [dirTree, view, detailPanel]
			,border: false
			,items: [leftPane, view]
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

	,reload: function() {
		this.view.store.reload();
	}

	,getSelectedRecord: function() {
		var records = this.view.getSelectedRecords();
		if (!records.length) return null;
		return records[0];
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
							'<img src="{url}">',
						'</div>',
					'</div>',
					'<div class="details-info">',
						'<b>Fichier : </b>', // i18n
						'<span>{filename}</span>',
						'<b>Taille : </b>', // i18n
						'<span>{size}</span>',
						'<b>Dernière modification :</b>', // i18n
						'<span>{dateString}</span>',
					'</div>',
				'</tpl>',
			'</div>'
		);
		this.detailsTemplate.compile();
	}

	,showDetails: function() {
		var selNode = this.view.getSelectedNodes();
		var detailEl = this.detailPanel.body;
		if (selNode && selNode.length) {
			selNode = selNode[0];
			var data = this.view.lookup[selNode.id];
			detailEl.hide();
			this.detailsTemplate.overwrite(detailEl, data);
			detailEl.slideIn('l', {stopFx: true, duration:.2});
		}
	}

}); // eo.MediaPanel

eo.MediaPanel.ImageView = Ext.extend(Ext.DataView, {

	constructor: function(config) {

		var me = this;

		var stringEllipse = function(s, maxLength) {
			if(s.length > maxLength){
				return s.substr(0, maxLength-3) + '...';
			}
			return s;
		};

		var lookupNeedsUpdate = false;
		this.lookup = {};

		var prepareData = function(data) {

			if (lookupNeedsUpdate) {
				this.lookup = {};
				lookupNeedsUpdate = false;
			}

			data.shortName = stringEllipse(data.filename, 15);
			var nodeId = data.nodeId = this.getId() + "_" + data.filename;
			this.lookup[nodeId] = data;

			var mtime = Date.parseDate(data.filemtime, "Y-m-d H:i");
			data.dateString = mtime ? mtime.format("d/m/Y H:i") : "Inconnue"; // i18n

			return data;
		}

		config = Ext.apply({}, config, {
			tpl: this.createTemplate()
			,cls: 'img-chooser-view'
			,overClass:'x-view-over'
			,itemSelector: 'div.thumb-wrap'
			,singleSelect: true
			,emptyText : '<div style="padding:10px;">Dossier vide</div>'
			,prepareData: prepareData.createDelegate(this)
		});

		eo.MediaPanel.ImageView.superclass.constructor.call(this, config);

		this.store.on({
			scope: this
			,beforeload: function() {
				lookupNeedsUpdate = true;
				this.el.mask("Chargement", "x-mask-loading"); // i18n
			}
		});
	}

	,createTemplate: function() {
		var tpl = new Ext.XTemplate(
			'<tpl for=".">',
				'<div class="thumb-wrap" id="{nodeId}">',
					'<div class="thumb">',
						'<div class="ct">',
							'<img src="{url}" title="{filename}" class="{mime}" />',
							'<span class="{mime}"></span>',
						'</div>',
					'</div>',
					'<span>{shortName}</span>',
				'</div>',
			'</tpl>'
		);
		tpl.compile();
		return tpl;
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
//				,singleClickExpand: true
//				,expandable: true
			}
		});

		eo.MediaPanel.TreePanel.superclass.constructor.call(this, config);

		var sm = this.getSelectionModel();
		this.relayEvents(sm, ["selectionchange"]);

		sm.on("selectionchange", function(me, node, old) {
			if (old && !old.isExpanded() && old.iconElement) old.iconElement.removeClass("open");
			if (node && node.iconElement) node.iconElement.addClass("open");
		});
	}

	,loadDirs: function(dirs, expand) {

		var pushDirs = function(node, dirs) {
			Ext.each(dirs, function(dir) {
				var child = new Ext.tree.TreeNode({
					text: dir.name
					,path: dir.path
					,iconCls: "img-chooser-tree-folder"
//					,expandable: true
//					,singleClickExpand: true
					,listeners: {
						beforecollapse: function() {this.iconElement.removeClass("open")}
						,beforeexpand: function() {this.iconElement.addClass("open")}
					}
				});
				child.getUI().render = child.getUI().render.createSequence(function() {
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