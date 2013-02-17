<?php if (isset($namespace)): ?>
namespace <?php echo $namespace ?>;

<?php endif ?>
require_once __DIR__ . '/<?php echo $className ?>Base.php';

/**
 *
 * @category <?php echo $this->modelCategory, PHP_EOL ?>
 * @package <?php echo $this->modelPackage, PHP_EOL ?>
 * @subpackage <?php echo $this->tableSubPackage, PHP_EOL ?>
<?php if ($version): ?>
 * @since <?php echo $version, PHP_EOL ?>
<?php endif ?>
 */
class <?php echo $className ?> extends <?php echo $className ?>Base {

	/**
	 * It is not safe for ModelTable concrete implementations to override their
	 * parent's constructor. They can do initialization job in this configure()
	 * method.
	 */
	protected function configure() {
		// initialization ...
	}

}
