(function() {

var <?php echo $var ?> = eo.Class({

	constructor: function() {
		this.doConstruct();
	}
	
	,doConstruct: function() {}

	,open: function(destination) {
		if (!this.tab) {
			destination = destination || Oce.mx.application.getMainDestination();
			destination.add(this.createTab());
		}
		this.tab.show();
	}

<?php if (isset($config)): ?>
	,config: <?php echo $config ?>

<?php endif ?>

	,createTab: function() {
		var tab = this.tab = Ext.create(this.createTabConfig());
		tab.on('close', function() {
			delete this.tab;
		}, this);
		return tab;
	}

	,createTabConfig: function() {
		return {
			xtype: "panel"
			,closable: true
<?php if ($iconCls): ?>
			,iconCls: "<?php echo $iconCls ?>"
<?php endif ?>
		};
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