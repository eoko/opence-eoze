Oce.deps.wait('<?php echo $parentClass ?>', function() {

var NS = Ext.ns('<?php echo $namespace ?>.<?php echo $module ?>');

NS.<?php echo $module ?> = Ext.extend(<?php echo $parentClass ?>, {
	controller: '<?php echo $module ?>'
<?php foreach ($properties as $name => $value): ?>
	,<?php echo json_encode($name) ?>: <?php echo json_encode($value) ?><?php echo PHP_EOL ?>
<?php endforeach ?>
});

Oce.deps.reg('<?php echo "$namespace.$module.$module" ?>');

}); // deps