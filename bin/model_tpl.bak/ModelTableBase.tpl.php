require_once MODEL_QUERY_PATH . '<?php echo $modelName ?>Query.class.php';

/**
 * Base of the <?php echo $modelName ?> Table.
 * @package <?php echo $package ?> 
 * @subpackage models_base
 * @version <?php echo date('Y-m-d h:i:s') ?> 
 *
<?php foreach ($fields as $field): ?>
 * @method mixed findBy<?php echo ucfirst($field->getVarName()) ?>($<?php echo $field->getVarName() ?>)
 * @method mixed findFirstBy<?php echo ucfirst($field->getVarName()) ?>($<?php echo $field->getVarName() ?>)
<?php endforeach ?>
 */
abstract class <?php echo $className ?>Base extends myModelTable {

	private static $singleton = null;

	public $modelName = '<?php echo $modelName ?>';
	public $tableName = '<?php echo $className ?>';
	public $dbTableName = '<?php echo $tableName ?>';

	protected function __construct() {
		$cols = array(
<?php $comma = "\t\t\t\t"; ?>
<?php foreach ($fields as $field): ?>
<?php echo "$comma'{$field->getName()}' => {$field->getDeclaration($className)}" ?>
<?php $comma = ",\n\t\t\t\t"; ?>
<?php endforeach ?>

		);
		$relations = array(
<?php $comma = "\t\t\t\t"; ?>
<?php foreach ($relations as $relation): ?>
<?php echo "$comma'{$relation->getName()}' => " . $relation->getInfoDeclaration() ?>
<?php // echo "$comma'{$relation->getName()}' => '" . $relation->getClass() . "'" ?>
<?php $comma = ",\n\t\t\t\t"; ?>
<?php endforeach ?>

		);
		parent::__construct($cols, $relations);
	}

	/**
	 * @return <?php echo $className ?> 
	 */
	public static function getInstance() {
		if (self::$singleton == null) self::$singleton = new <?php echo $className ?>();
		return self::$singleton;
	}

	/**
	 * Get the name of this Table's Model.
	 * @return String
	 */
	public static function getModelName() {
		return '<?php echo $modelName ?>';
	}
	
	/**
	 * @return String
	 */
	public static function getTableName() {
		return '<?php echo $className ?>';
	}

	/**
	 * @return String
	 */
	public static function getDBTable() {
		return '<?php echo $tableName ?>';
	}

	/**
	 * Get whether the given object is an instance of this table's model
	 * @return Bool TRUE if $obj is an instance of <?php echo $modelName ?> 
	 */
	public static function isInstanceOfModel($obj) {
		return $obj instanceof <?php echo $modelName ?>;
	}

	/**
	 * @return <?php echo $modelName ?>Query
	 */
	public static function createQuery(array $params = array(), &$getQuery = null) {
		require_once MODEL_QUERY_PATH . '<?php echo $modelName ?>Query.class.php';
		$getQuery = <?php echo $modelName ?>Query::create(<?php echo $className ?>::getInstance(), $params);
		return $getQuery;
	}

	/**
	 * @return <?php echo $modelName ?>Query
	 */
	public static function createQueryGet(&$getQuery, array $params = array()) {
		return self::createQuery($params, $getQuery);
	}

	/**
	 * Create a new <?php echo $modelName ?>
	 *
	 * The new reccord will be considered new until its primary key is set.
	 *
	 * An array of values can be given to initialize the reccord's fields. It
	 * is not required for all model's fields to have a value in the given
	 * array; the absent fields will be set to NULL.
	 *
	 * @param array $initValues an array containing values with which the
	 * Model's fields will be initialized.
	 *
	 * @param Bool $strict if set to TRUE, then all field of the model will be
	 * required to be set in $initValues, or an IllegalArgumentException will
	 * be thrown

	 * @return <?php echo $modelName ?> 
	 */
	public static function createModel($initValues = null, $strict = false, array $params = array()) {
		return <?php echo $modelName ?>::create($initValues, $strict, $params);
	}

	/**
	 * @return Bool
	 */
	public static function hasPrimaryKey() {
		return <?php echo $primaryColName !== null ? 'true' : 'false' ?>;
	}

<?php if ($primaryColName !== null): ?>
	/**
	 * Get the name of the primary key field.
	 * @return String
	 */
	public static function getPrimaryKeyName() {
		return '<?php echo $primaryColName ?>';
	}

	/**
	 * Get the column representing the primary key.
	 * @return ModelColumn
	 */
	public static function getPrimaryKeyColumn() {
		return self::getInstance()->cols['<?php echo $primaryColName ?>'];
	}

	/**
	 * Load a <?php echo $modelName ?> reccord from the database, selected by
	 * its primary key
	 *
	 * @param mixed $<?php echo $primaryField->getVarName(false) ?> 
	 * @return Model the data Model from the loaded reccord, or null if no
	 * reccord matching the given primary key has been found
	 */
	public static function loadModel($<?php echo $primaryField->getVarName(false) ?>, array $context = array()) {
		return <?php echo $modelName ?>::load($<?php echo $primaryField->getVarName(false) ?>, $context);
	}

	/**
	 *
	 * @return <?php echo $modelName ?>
	 */
	public static function findByPrimaryKey($<?php echo $primaryField->getVarName(false) ?>, array $context = array()) {
		return <?php echo $modelName ?>::load($<?php echo $primaryField->getVarName(false) ?>, $context);
	}

	/**
	 *
	 * @return <?php echo $modelName ?> 
	 */
	public static function findFirstByPrimaryKey($<?php echo $primaryField->getVarName(false) ?>, array $context = array()) {
		return <?php echo $modelName ?>::load($<?php echo $primaryField->getVarName(false) ?>, $context);
	}
<?php else: ?>
	/**
	 * This method always throws an UnsupportedOperationException because
	 * <?php echo $tableName ?> doesn't have a primary key.
	 */
	public static function getPrimaryKeyName() {
		throw new UnsupportedOperationException('The model <?php echo $modelName ?> has no primary key');
	}

	/**
	 * This method always throws an UnsupportedOperationException because
	 * <?php echo $tableName ?> doesn't have a primary key.
	 */
	public static function getPrimaryKeyColumn() {
		throw new UnsupportedOperationException('The model <?php echo $modelName ?> has no primary key');
	}

	/**
	 * This method always throws an UnsupportedOperationException because
	 * <?php echo $tableName ?> doesn't have a primary key.
	 */
	public static function loadModel($ignored) {
		throw new UnsupportedOperationException('The model <?php echo $modelName ?> has no primary key');
	}

	/**
	 * This method always throws an UnsupportedOperationException because
	 * <?php echo $tableName ?> doesn't have a primary key.
	 */
	public static function findByPrimaryKey($ignored) {
		throw new UnsupportedOperationException('The model <?php echo $modelName ?> has no primary key');
	}

	/**
	 * This method always throws an UnsupportedOperationException because
	 * <?php echo $tableName ?> doesn't have a primary key.
	 */
	public static function findFirstByPrimaryKey($ignored) {
		throw new UnsupportedOperationException('The model <?php echo $modelName ?> has no primary key');
	}
<?php endif ?>

	/**
	 *
	 * @return ModelSet
	 */
	public static function findAll(array $context = null, $mode = ModelSet::ONE_PASS) {
		if ($context === null) $context = array();
		return self::findWhere('1', null, $mode, $context);
	}

	/**
	 * Create a new <?php echo $modelName ?> reccord initialized by the given $data
	 * array. All the model's fields must have a value set in the $data array.
	 * The <?php echo $modelName ?> reccord will be considered loaded and not-new.
	 * @param array $data
	 */
	public static function loadModelFromData(array $data, array $context = array()) {
		return <?php echo $modelName ?>::loadFromData($data, $context);
	}

<?php
foreach ($proxyMethods as $method) {
	echo $method;
}
?>

}