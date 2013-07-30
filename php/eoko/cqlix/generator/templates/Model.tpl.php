<?php if (isset($modelNamespace)): ?>
namespace <?php echo $modelNamespace ?>;

<?php endif ?>
<?php if (!isset($modelBaseNamespace)): ?>
require_once __DIR__ . '/base/<?php echo $modelName ?>Base.php';

<?php endif ?>
/**
 * @category <?php echo $this->modelCategory, PHP_EOL ?>
 * @package <?php echo $this->modelPackage, PHP_EOL ?>
<?php if ($version): ?>
 * @since <?php echo $version, PHP_EOL ?>
<?php endif ?>
 */
class <?php echo $modelName ?> extends <?php echo $modelBaseClass ?> {

	/**
	 * It is not safe for Model concrete implementations to override their
	 * parent's constructor. They can do initialization job in this initialize()
	 * method.
	 */
	protected function initialize() {
		// initialization ...
	}

}
