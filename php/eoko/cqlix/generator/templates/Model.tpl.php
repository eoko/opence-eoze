<?php if (isset($namespace)): ?>
namespace <?php echo $namespace ?>;

<?php endif ?>
require_once __DIR__ . '/<?php echo $modelName ?>Base.php';

/**
 * @category <?php echo $this->modelCategory, PHP_EOL ?>
 * @package <?php echo $this->modelPackage, PHP_EOL ?>
<?php if ($version): ?>
 * @since <?php echo $version, PHP_EOL ?>
<?php endif ?>
 */
class <?php echo $modelName ?> extends <?php echo $modelName ?>Base {

	/**
	 * It is not safe for Model concrete implementations to override their
	 * parent's constructor. They can do initialization job in this initialize()
	 * method.
	 */
	protected function initialize() {
		// initialization ...
	}

}
