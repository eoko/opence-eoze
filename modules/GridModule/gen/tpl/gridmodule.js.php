(function() {

var deferedRegistering = false;

/*
 * Defered registering
 * ===================
 * 
 * This facility allows children classes to have async implementation (that is, that have
 * to asynchronously require other classes, or whatever).
 * 
 * Children classes which implementation may require async loadings, must call the
 * deferRegistering() method to prevent post class creation operations to happen just after
 * the implementation file is included. This call must, of course, be done outside of the
 * aynchronously called functions.
 * 
 * When everything is loaded and the child class declaration has been done, then the function
 * completeRegistering() must be called.
 */
var deferRegistering = function() {
	deferedRegistering = true;
};

/**
 * @internal
 * Will be called immediatly after the child class file has been included, or when it is
 * called in the child class implementation if the code in the class file has already
 * called the deferRegistering() function.
 */
var completeRegistering = function() {
	// Copy renderers
	<?php echo $namespace ?>.<?php echo $controller ?>.<?php echo $name ?>.renderers =
	<?php echo $namespace ?>.<?php echo $controller ?>.<?php echo $name ?>Base.renderers;

	// Register dependency key
	Oce.deps.reg('<?php echo $namespace ?>.<?php echo $controller ?>.<?php echo $name ?>');
};

<?php if (isset($uses)): ?>
if (!Oce.deps.wait(<?php echo $uses ?>, function() {
<?php endif // uses ?>

Ext.namespace('<?php echo $namespace ?>.<?php echo $controller ?>');

<?php echo $namespace ?>.<?php echo $controller ?>.<?php echo $name ?>Base = (function(renderers) {

var moduleClass = Ext.extend(<?php echo $superclass ?>, {

	my: <?php echo $my . "\n" ?>
	
	,modelName: <?php echo $modelName . "\n" ?>
	,tableName: <?php echo $tableName . "\n" ?>

	,constructor: function(config) {
		<?php echo $namespace ?>.<?php echo $controller ?>.<?php echo $name ?>Base.superclass.constructor.call(this, config);
	}

	,extra: <?php echo $extra ?>

	,renderers: renderers
	
	,templates: {
		<?php echo $templates ?>
	}

	,columns: [
<?php $columns->render() ?>
	]

<?php if (isset($forms)): ?>
	,forms: <?php echo $forms ?>
<?php endif // forms ?>


<?php if (isset($tabs)): ?>
	,tabs: <?php echo $tabs ?>
<?php endif // tabs ?>

<?php /*
<?php if (isset($enumsConfig)): ?>
	,enums: new eo.cqlix.Enums(<?php echo $enumsConfig ?>)
<?php endif ?>
 */ ?>
	
<?php if (isset($i18n)): ?>
	,i18n: <?php echo $i18n ?>
<?php endif ?>

<?php if (isset($modelConfig)): ?>
	,model: new eo.cqlix.Model(<?php echo $modelConfig ?>)
<?php endif ?>

<?php if (isset($extraModels)): ?>
	,modelRelations: eo.cqlix.Model.createBunch(<?php echo $extraModels ?>)
<?php endif ?>
});

moduleClass.renderers = renderers;

return moduleClass;

})( // Closure args (renderers)
{
<?php // --- Renderers --- ?>
<?php $comma = '' ?>
<?php foreach ($renderers as $prefix => $renderer): ?>
<?php $default = isset($renderer['default']) ? $renderer['default'] : null ?>
		<?php echo $comma.$prefix ?>: (function() {
			var fn = function(val){
				switch (parseInt(val)) {
<?php foreach ($renderer['values'] as $k => $v):?>
<?php if ($default === $k): ?>
					default:
<?php endif ?>
					case <?php echo $k ?>:
<?php if (isset($v['class'])): ?>
						return '<span class="<?php echo isset($v['class']) ? $v['class'] : $v ?>">&nbsp;</span>';
<?php elseif (isset($v['text'])): ?>
						return "<?php echo str_replace('"', '\\"', $v['text']) ?>";
<?php endif ?>
<?php endforeach ?>
				}
			}
			fn.data = [<?php $sep = "\n" ?>
<?php if (isset($renderer['allowNull']) && $renderer['allowNull']): ?>
<?php echo $sep; $sep = ",\n" ?>
				[null, "—"]<?php ?>
<?php endif ?>
<?php foreach ($renderer['values'] as $k => $v): ?>
<?php echo $sep; $sep = ",\n" ?>
				[<?php echo $k ?>, "<?php echo isset($v['text']) ? $v['text'] : '' ?>"]<?php ?>
<?php endforeach ?>

			];
			return fn;
		})()
<?php $comma = ',' ?>
<?php endforeach ?>

}); // closure

<?php if (isset($extraJS)): ?>
<?php echo $extraJS ?>
; // in case the last line in the extra js is not terminated (by a semicolon)
<?php else: ?>
<?php echo $namespace ?>.<?php echo $controller ?>.<?php echo $name ?> = <?php echo $namespace ?>.<?php echo $controller ?>.<?php echo $name ?>Base;
<?php endif ?>

if (!deferedRegistering) {
	completeRegistering();
}

<?php if (isset($uses)): ?>
}) // Oce.deps.wait 
){ // if must wait, trigger dep loading
	Oce.getModules(<?php echo $uses ?>);
}
<?php endif ?>
})(); // closure