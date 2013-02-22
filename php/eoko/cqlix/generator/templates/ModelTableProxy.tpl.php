<?php if (isset($proxyNamespace)): ?>
namespace <?php echo $proxyNamespace ?>;
<?php endif ?>

/**
 * Proxy of the <?php echo $modelName ?> Table.
 *
 * @category <?php echo $this->modelCategory, PHP_EOL ?>
 * @package <?php echo $this->modelPackage, PHP_EOL ?>
 * @subpackage <?php echo $this->proxySubPackage, PHP_EOL ?>
<?php if ($version): ?>
 * @since <?php echo $version, PHP_EOL ?>
<?php endif ?>
 */
class <?php echo $proxyName ?> extends \ModelTableProxy {

	private static $tableVars = array();

	public static $tableName = '<?php echo $tableName ?>';
	public static $modelName = '<?php echo $modelName ?>';
	public static $dbTableName = '<?php echo $dbTable ?>';

	private static $instance = null;

	public static function get() {
		if (self::$instance === null) self::$instance = new <?php echo $tableName ?>Proxy;
		return self::$instance;
	}

	/**
	 * @return <?php echo $proxyName, PHP_EOL ?>
	 */
	public static function getInstance() {
		$table = <?php echo $tableClass ?>::getInstance();
		foreach (self::$tableVars as &$pointer) {
			$pointer = $table;
		}
		return $table;
	}

	/**
	 * @param $pointer
	 * @return \ModelTableProxy
	 */
	public function attach(&$pointer) {
		self::$tableVars[] =& $pointer;
		return $pointer = $this;
	}

	public static function __callStatic($name, $arguments) {
		$instance = self::getInstance();
		return call_user_func_array(array($instance, $name), $arguments);
	}

	public function __call($name, $arguments) {
		$instance = self::getInstance();
		return call_user_func_array(array($instance, $name), $arguments);
	}

	public function __isset($name) {
		$instance = self::getInstance();
		return isset($instance->$name);
	}

	public function __get($name) {
		$instance = self::getInstance();
		return $instance->$name;
	}

	public function __set($name, $value) {
		$instance = self::getInstance();
		$instance->$name = $value;
	}

	public static function getTableName() {
		return '<?php echo $tableName ?>';
	}

	public static function getDBTableName() {
		return self::$dbTableName;
	}

	public static function getModelName() {
		return self::$modelName;
	}
<?php
/* The primary key name must be proxyied in order to avoid infinite recursion
 * between two separate tables which have an IndirectHasMany relationship.
 */
?>
	public static function getPrimaryKeyName() {
		return '<?php echo $primaryKeyName ?>';
	}
}
