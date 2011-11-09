(function() {

var sp = Oce.BaseModule,
	sppp = sp.prototype;

var <?php echo $var ?> = Ext.extend(sp, {

	constructor: function(config) {
		sppp.constructor.call(this, config);
		this.addEvents("open", "close");
		this.doConstruct();
	}
	
	,doConstruct: function() {}
	
	,controller: "<?php echo $module ?>"
	
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

<?php if (isset($config)): ?>
	,config: <?php echo $config ?>

<?php endif ?>

	,createTab: function() {
		var tab = this.createTabConfig();
		if (!(tab instanceof Ext.Component)) {
			tab = Ext.create(tab);
		}
		this.tab = tab;
		tab.on('close', this.onClose, this);
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
		return {
			xtype: "panel"
			,border: false
			,closable: true
			,iconCls: this.getIconCls()
		};
	}
	
	,getTitle: function() {
		return "<?php echo $title ?>";
	}
	
	,getIconCls: function(action) {
<?php if ($iconCls): ?>
		return "<?php echo $iconCls ?>" + (action ? ' ' + action : '');
<?php endif ?>
	}
});
	
<?php if (isset($main)): ?>

var spp = <?php echo $var ?>.prototype;
<?php echo $var ?> = <?php echo $var ?>.extend({});

// === Auto included part ======================================================

<?php echo $main ?>

<?php endif ?>

Ext.ns("<?php echo $namespace ?>.<?php echo $module ?>").<?php echo $module ?> = <?php echo $var ?>;
Oce.deps.reg('<?php echo "$namespace.$module.$module" ?>');

})(); // closure