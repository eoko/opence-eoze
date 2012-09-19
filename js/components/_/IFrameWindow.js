Ext.ns('eoko.ext', 'eoko.help');

eoko.ext.IFramePanel = Ext.extend(Ext.Panel, {

	navigation: true
	,backText: "Back" // i18n
	,forwardText: "Forward" // i18n
	,reloadText: "Reload" // i18n
	,loadingText: "Loading" // i18n
	,showButtonsText: true

	,errorPageTemplate: '<html><head><title>{0}</title><body {2}><h1>{0}</h1>{1}</body></html>'
	,getErrorPage: function(title, errorText, errorClass) {
		if (errorClass) {
			if (Ext.isArray(errorClass)) errorClass = errorClass.join(' ');
			errorClass = String.format('class="{0}', errorClass);
		}
		return String.format(
			this.errorPageTemplate,
			title || "Erreur", // i18n
			errorText || "Désolé cette page n'a pas pu être chargée correctement.", // i18n
			errorClass
		);
	}

	,constructor: function(config) {

		this.addEvents({
			titlechanged: true
		});

		config = config || {};
		Ext.apply(this, config);

		if (!config.url) throw new Error('Required config param: url');

		var cfg = Ext.apply(Ext.apply({}, config), {
			layout: "fit"
			,items: this.iframeCt = new eoko.ext.IFramePanel.IFrameContainer({
			})
		});

		var me = this;
		var historyCfg = config.history || {};

		var back = new Ext.Button(Ext.apply({
			text: this.showButtonsText ? this.backText : undefined
			,tooltip: this.backText
			,disabled: true
			,handler: function() {me.history.back()}
		}, historyCfg.back))
		,forward = new Ext.Button(Ext.apply({
			text: this.showButtonsText ? this.forwardText : undefined
			,tooltip: this.forwardText
			,disabled: true
			,handler: function() {me.history.forward()}
		}, historyCfg.forward))
		,reload = new Ext.Button(Ext.apply({
			text: this.showButtonsText ? this.reloadText : undefined
			,tooltip: this.reloadText
			,handler: function() {me.history.reload()}
		}, historyCfg.reload));

		if (this.navigation) {
			var opt = Ext.isString(this.navigation) ? this.navigation : 'tbar';
			cfg[opt] = {
				items: [
					back, forward, "-", reload
					,"->"
					,this.loader = new Ext.toolbar.TextItem({
						cls: "mini-browser-ajax-loader"
						,hidden: true
						,text: this.showButtonsText ? this.loadingText : undefined // i18n
					})
				]
			}
		}

		eoko.ext.IFramePanel.superclass.constructor.call(this, cfg);

		this.history = {
			currentIndex: -1 // will be set to 0 when the initial page url will be pushed
			,urls: []
			,reset: function() {
				this.urls = [];
				this.currentIndex = -1;
				this.updateButtons();
			}
			,push: function(url) {
				this.currentIndex++;
				this.urls = this.urls.slice(0,this.currentIndex);
				this.urls.push(url);
				this.updateButtons();
			}
			,updateButtons: function() {
				if (this.hasBack()) back.enable(); else back.disable();
				if (this.hasForward()) forward.enable(); else forward.disable();
			}
			,hasBack: function() {
				return this.currentIndex > 0;
			}
			,hasForward: function() {
				return this.currentIndex < this.urls.length-1;
				this.updateButtons();
			}
			,back: function() {
				me.setUrl(this.urls[--this.currentIndex]);
				this.updateButtons();
			}
			,forward: function() {
				me.setUrl(this.urls[++this.currentIndex]);
				this.updateButtons();
			}
			,reload: function() {
				me.setUrl(this.urls[this.currentIndex]);
			}
		};

		this.reload = this.history.reload.createDelegate(this.history);
	}

	,reset: function(url, callback) {
		this.history.reset();
		this.history.push(url);
		this.setUrl(url, callback);
	}

	,getIFrameDocument: function() {
		var dom = this.iframe.dom,
			doc = dom.contentDocument // ff
				|| dom.contentWindow.document // ie5.5/6
				|| dom.document // ie5 (which won't allow the creation of the iframe anyway...)
				;
		if (doc) return doc;
		throw new Error('Cannot get iframe\'s document');
	}

	,setUrl: function(url, callback) {
		this.loader.show();
		this.currentUrl = "" + url;
		
		if (!Ext.isObject(url)) url = parseUri(url);
		this.prepareUrlForProxyRequest(url);

		var anchor = url.anchor;
		url.anchor = "";
		url = this.proxy + url;

		Ext.Ajax.request({
			url: url
			,method: "get"
			,params: {
				min: 1
			}
			,raw: true
			,callback: function(opts, success, response) {
				if (success) {
					this.setIFrameContent(response.responseText, function(doc) {
						this.loader.hide();
						this.iframeCt.el.unmask();
						if (callback) callback();
						if (anchor) {
							this.scrollToAnchor(anchor);
//							// doesn't word, shit! why is this?
//							//this.iframe.dom.contentDocument.location.replace("#" + anchor);
//
//							// erf, anyway, let's scroll by ourselves
//							var doc = this.getIFrameDocument(),
//								target = doc.getElementById(anchor);
//								console.log(anchor);
//							if (!target) target = doc.getElementsByName(anchor);
//
//							if (target) {
//								var yTarget = (function(){
//									var y = target.offsetTop;
//									var node = target;
//									while (node.offsetParent && (node.offsetParent != doc.body)) {
//										node = node.offsetParent;
//										y += node.offsetTop;
//									}
//									return y;
//								})();
//								this.iframe.dom.contentWindow.scrollTo(0, yTarget);
//							}
						} else {
							this.iframe.dom.contentWindow.scrollTo(0,0);
						}
					}.createDelegate(this)); // setIFrameContent callback
				}
			}.createDelegate(this) // AJAX request callback
		});
	}

	,setIFrameContent: function(html, callback) {
		// update content
//		this.iframe.dom.contentDocument.documentElement.innerHTML = html;
		var doc = this.getIFrameDocument();

		html = html.replace(/^<!DOCTYPE\s[^>]*>\n?/i, '')
			// forbid document.write (which would overwrite the whole page
			// since the doc will be closed in the next statement)
			.replace(/document\.write/g, '');

		if (!Ext.isIE) {
			// working in ff/chrome, but not IE
//		var div = doc.documentElement.createElement("div");
//		div.innerHTML = html;
//		doc.documentElement.appendChild(div);
			doc.documentElement.innerHTML = html;
			this.hackLinks(doc);
			callback(doc);
			return;
		} else {
			doc.open();
			doc.write(html);
			doc.close();

			var me = this;
			var interval;
			// wait for the document body to exist
			var fireLoaded = function() {
				var doc = me.getIFrameDocument();
				if (doc && doc.body) {
					if (interval) clearInterval(interval);
					// hack links to be handled with js
					me.hackLinks(doc);
					if (callback) callback(doc);
					return true;
				} else {
					if (trial++ > 50) {
						clearInterval(interval)
						if (callback) callback(null);
					}
					return false;
				}
			};

			if (!fireLoaded()) {
				var trial = 0;
				interval = setInterval(fireLoaded, 100);
			}
		}
	}

	,processDoc: function() {
		var doc = this.iframe.dom.ownerDocument;
//		this.resolveRelativeUrls(doc);
		this.hackLinks(doc);
	}

	,isWhiteListed: function(host) {
		if (!this.whiteList) {
			return true;
		} else {
			var wl = this.whiteList;
			if (Ext.isArray(wl)) {
				for (var i=0,l=wl.length; i<l; i++) {
					if (wl.test(host)) return true;
				}
			} else {
				return wl.test(host);
			}
			return false;
		}
	}

	,scrollToAnchor: function(anchor) {
		anchor = anchor.replace(/^#?(.+)/, '$1');
		var doc = this.getIFrameDocument();

		var target = doc.getElementsByName(anchor);
		if (target && target.length) target = target[0];
		else target = doc.getElementById(anchor);

		// target.scrollIntoView(true) would scroll the parent window as well...
		if (target) {
			var yTarget = (function(){
				var y = target.offsetTop;
				var node = target;
				while (node.offsetParent && (node.offsetParent != doc.body)) {
					node = node.offsetParent;
					y += node.offsetTop;
				}
				return y;
			})();
			this.iframe.dom.contentWindow.scrollTo(0, yTarget);
		}
	}

	,prepareUrlForProxyRequest: function(uri) {}

	,hackLinks: function(doc) {
		var me = this;
		var go = function() {
			var href = this.getAttribute('href');
			if (href) {
//				if (href[0] !== '#') { // we don't want to hack anchors
//				if (false == 'anchor' in this) { // we don't want to hack anchors
				if (!this.anchor) { // we don't want to hack anchors
					try {
						var uri = parseUri(href);
						if (me.isWhiteListed(uri.host)) {
							me.history.push(uri);
							me.setUrl(uri);
						} else {
							// open unexpected page in new window, to avoid
							// cross-site scripting and unexpected rendering
							window.open(this.href, "_blank");
						}
					} catch (e) {
						if (console) {
							if (console.error) console.error(e);
							else if (console.log) console.log(""+e);
						}
						me.setIFrameContent(me.getErrorPage());
					}
				} else {
					// We must emulate anchors, or they will try to load
					// proxyurl#anchor (they don't really where they are
					// because no url has been loaded in the iframe).
					// Also, we must use location.replace in order to avoir
					// the anchor to be added to the main window history.
					
					// this.hash doesn't exist in IE
					//this.ownerDocument.location.replace(this.hash);
					//this.ownerDocument.location.replace(this.anchor);
					me.scrollToAnchor(this.anchor);
				}
			}

			// allways return false, to prevent default link behaviour
			return false;
		};

		Ext.each(doc.getElementsByTagName('a'), function(a) {

			a.onclick = go;

			var href = a.getAttribute('href');
			if (href) {
				if (href[0] === '#') { // we don't want to hack anchors
					// display correct url for anchors (without this fix, they
					// would show as blank_page_url#anchor_name
					a.anchor = href;
					a.href = me.currentUrl + href;
				}
			}
		});

		// retrieve title
		var title = doc.getElementsByTagName('title');
		if (title && title.length > 0) {
			title = title[0].innerHTML;
			this.fireEvent('titlechanged', title);
		}
	}

	,resolveRelativeUrls: function(doc) {
		var rgAbs = /^https?:\/\//;
		
		var me = this;
		var makeAbsolute = function(url) {
			return me.baseUrl + (url[0] === '/' ? '' : '/') + url;
		}

		Ext.each(doc.getElementsByTagName('*'), function(el) {
			if (el.src && !rgAbs.test(el.src)) {
				el.src = makeAbsolute(el.src);
			}
			if (el.href && !rgAbs.test(el.href)) {
				el.href = makeAbsolute(el.href);
			}
		});
	}

});

Ext.reg('iframepanel', eoko.ext.IFramePanel);

eoko.ext.IFramePanel.IFrameContainer = Ext.extend(Ext.Container, {
	htmlFormat: '<iframe id="{2}" src="{0}" width="100%" height = "100%" frameborder="0">{1}</iframe>'

	,noIFrameText: "Votre navigateur ne permet pas d'ouvrir un site distant dans une fenêtre interne."
		+ " Le contenu auquel vous souhaitez accéder est disponible à l'adresse suivante : "
		+ ' <a href="{0}" target="_blank">{0}</a>' // i18n

	,constructor: function(config) {
		config = config || {};
		this.iframeId = Ext.id();
		var cfg = Ext.apply(Ext.apply({}, config), {
			html: String.format(
				this.htmlFormat,
//				config.url || "about:blank",
				// about:blank cannot be used or the #anchors will pause problems
				"proxy.php",
				String.format(config.noIFrameText || this.noIFrameText, config.url),
				this.iframeId
			)
		});
		eoko.ext.IFramePanel.IFrameContainer.superclass.constructor.call(this, cfg)
	}

	,afterRender: function() {
		Ext.Container.superclass.afterRender.apply(this, arguments);
		
		var owner = this.ownerCt;
		var f = owner.iframe = Ext.get(this.iframeId);


//
//		f.on('load', owner.processDoc.createDelegate(owner));

//		this.el.mask();
//		owner.history.push(owner.url);
//		owner.setUrl(owner.url);

		// we must wait for the blank page to be loaded, or an history token
		// will be pushed
		this.el.mask(owner.loadingText, 'x-mask-loading');
		var l;
		f.on('load', l = function() {
			f.un('load', l);
			owner.history.push(owner.url);
			owner.setUrl(owner.url);
		});
	}
});


eoko.ext.IFrameWindow = Ext.extend(Ext.Window, {
	constructor: function(config) {
		var me = this;
		var taskbar = Oce.mx.TaskBar;
		
		var tools = [{
			id: "refresh"
			,handler: function() {
				me.iframePanel.reload()
			}
		}];

		if (taskbar && config.minimizable !== false) {
			tools.push({
				id: 'minimize'
				,handler: function() {
					me.minimize();
				}
			});
		}

		eoko.ext.IFrameWindow.superclass.constructor.call(this, Ext.apply({
			items: this.iframePanel = new eoko.ext.IFramePanel(Ext.apply({
				url: config.url
				,baseUrl: config.baseUrl
				,whiteList: config.whiteList
				,history: config.history || {}
				,listeners: {
					titlechanged: function(title) {
						me.setTitle((me.titlePrefix || "") + title + (me.titleSuffix || ""));
					}
				}
			}, config.panel || {}))
			,layout: "fit"
			,maximizable: true
			,tools: tools
		}, config));

		// --- TaskBar ---
		if (taskbar) {
			taskbar.addWindow(this);
		}
	}
});

Ext.reg('iframewindow', eoko.ext.IFrameWindow);

eoko.help.IFrameFactory = {

	helpWindow: null

	,baseUrl: "http://wiki.eoko-lab.fr/index.php?title="

	,createWindow: function(url) {
		return this.helpWindow = new eoko.ext.IFrameWindow({
			title: "Aide"
			,titlePrefix: "Aide : "
			,width: 400
			,height: 300
			//,collapsible: true
			,url: url
			,whiteList: /[^.]\.eoko(?:-lab)?\.fr$/
			,panel: {
				showButtonsText: false
				,loadingText: "Chargement..."
				,proxy: "proxy.php?url="
				,prepareUrlForProxyRequest: function(uri) {
					uri.queryKey.min = 1;
				}
			}
			,history: {
				back: {iconCls: "fugico_arrow-180", text: ""}
				,forward: {iconCls: "fugico_arrow", text: ""}
				,reload: {iconCls: "arrow-circle-315", text: ""}
			}
//			,url: "proxy.php?url=http://www.google.fr"
			,baseUrl: "http://wiki.eoko-lab.fr"
			,listeners: {
				destroy: function() {
					this.helpWindow = null;
				}.createDelegate(this)
			}
		});
//		win.show();
	}

	,view: function(topic) {
//		var url = this.baseUrl.replace(/\/?$/, topic.replace(/^\/?/, '/'));
		var url = this.baseUrl + topic;
		if (this.helpWindow) {
			this.helpWindow.iframePanel.reset(url,
					this.helpWindow.show.createDelegate(this.helpWindow));
		} else {
			this.createWindow(url).show();
		}
	}
}

