<?php if (isset($uses)): ?>
if (!Oce.deps.wait(<?php echo $uses ?>, function() {
<?php endif // uses ?>
<?php echo $namespace ?>.<?php echo $controller ?>.<?php echo $name ?>Base = (function(renderers) {

var moduleClass = Ext.extend(<?php echo $superclass ?>, {

	my: <?php echo $my ?> 

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
<?php echo $extraJS ?>;
<?php else: ?>
<?php echo $namespace ?>.<?php echo $controller ?>.<?php echo $name ?> = <?php echo $namespace ?>.<?php echo $controller ?>.<?php echo $name ?>Base;
<?php endif ?>

<?php echo $namespace ?>.<?php echo $controller ?>.<?php echo $name ?>.renderers =
<?php echo $namespace ?>.<?php echo $controller ?>.<?php echo $name ?>Base.renderers;

Oce.deps.reg("<?php echo $namespace ?>.<?php echo $controller ?>.<?php echo $name ?>");

<?php if (isset($uses)): ?>
}) // Oce.deps.wait 
){ // if must wait, trigger dep loading
	Oce.getModules(<?php echo $uses ?>);
}
<?php endif ?>
