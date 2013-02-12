/**
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 19 sept. 2012
 */
Ext.ns('eo.modules.TabModule');

/**
 * Base class for TabModule modules.
 */
eo.modules.TabModule = Ext.extend(Oce.BaseModule, {
	
	/**
	 * @cfg {String} controller
	 */
	/**
	 * @cfg {Object} config
	 */
	
	constructor: function(config) {
		
		this.addEvents('open', 'close');
		
		Ext.apply(this, config);
		
		this.callParent(arguments);
	}
	
	/**
	 * True if this component fires an "open" event. Read-only.
	 * @type Boolean
	 * @property openEvent
	 */
	,openEvent: true
	/**
	 * True if this component is opened. Read-only.
	 * @type Boolean
	 * @property opened
	 */
	,opened: false

	,open: function(destination) {
		if (!this.tab) {
			destination = destination || Oce.mx.application.getMainDestination();
			destination.add(this.createTab());
		}
		this.tab.show();
		this.afterOpen();
	}
	
	,baseModuleActions: {
		open: function(cb, scope, args) {
			this.on({
				single: true
				,open: cb
				,scope: scope
			});
			return this.open.apply(this, args);
		}
	}
	
	,moduleActions: ["open"]
	
	/**
	 * Protected method. Must be called when the module is opened by children
	 * classes, <b>only</b> if they completly replace the open() method (that
	 * is, override id and do not call the superclass open() method).
	 */
	,afterOpen: function() {
		this.opened = true;
		this.fireEvent("open", this);
	}

	,createTab: function() {
		var tab = this.createTabConfig();
		if (!(tab instanceof Ext.Component)) {
			if (tab.xclass) {
				tab = Ext.create(tab);
			} else {
				tab = Ext.widget(tab);
			}
		}
		this.tab = tab;
		tab.on('close', this.onClose, this);
        tab.module = this;
        tab.getActiveModule = function() {
            return this.module;
        };
		return tab;
	}

	// private
	,onClose: function() {
		delete this.tab;
		if (this.opened) {
			this.opened = false;
			this.fireEvent("close", this);
		}
	}

	,createTabConfig: function() {
		var config = {
			xtype: "panel"
			,border: false
			,closable: true
			,iconCls: this.getIconCls()
			,title: this.getTitle()
		};
		
		var child = this.tabChild;
		if (child) {
			config = Ext.apply({
				layout: 'fit'
				,items: [child]
			}, config);
		}
		
		return config;
	}
	
	,getTitle: function() {
		return this.title;
	}
	
	,getIconCls: function(action) {
		var c = this.iconCls;
		if (c) {
			return c + (action ? ' ' + action : '');
		}
	}
});
	
Oce.deps.reg('eo.modules.TabModule');