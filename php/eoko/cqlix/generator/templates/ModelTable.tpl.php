<?php if (isset($tableNamespace)): ?>
namespace <?php echo $tableNamespace ?>;

<?php endif ?>
<?php if (!isset($tableBaseNamespace)): ?>
require_once __DIR__ . '/base/<?php echo $className ?>Base.php';

<?php endif ?>
/**
 *
 * @category <?php echo $this->modelCategory, PHP_EOL ?>
 * @package <?php echo $this->modelPackage, PHP_EOL ?>
 * @subpackage <?php echo $this->tableSubPackage, PHP_EOL ?>
<?php if ($version): ?>
 * @since <?php echo $version, PHP_EOL ?>
<?php endif ?>
 */
class <?php echo $className ?> extends <?php echo $tableBaseClass ?> {

	/**
	 * It is not safe for ModelTable concrete implementations to override their
	 * parent's constructor. They can do initialization job in this configure()
	 * method.
	 */
	protected function configure() {
		// initialization ...
	}

}
