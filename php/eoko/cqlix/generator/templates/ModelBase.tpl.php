<?php use \ModelColumn ?>
<?php if (isset($modelBaseNamespace)): ?>
namespace <?php echo $modelBaseNamespace ?>;
<?php endif ?>

/**
 * Base of the <?php echo $modelName ?> Model.
 *
 * @category <?php echo $this->modelCategory, PHP_EOL ?>
 * @package <?php echo $this->modelPackage, PHP_EOL ?>
 * @subpackage <?php echo $this->baseSubPackage, PHP_EOL ?>
<?php if ($version): ?>
 * @since <?php echo $version, PHP_EOL ?>
<?php endif ?>
 *
<?php foreach ($fields as $field): ?>
 * @property $<?php echo $field->getName(), PHP_EOL ?>
<?php endforeach ?>
<?php foreach ($relations as $relation): ?>
 * @property-read $<?php echo lcfirst($relation->getName()), PHP_EOL ?>
<?php endforeach ?>
 */
abstract class <?php echo $modelName ?>Base extends <?php echo $baseModelName ?> {

<?php foreach ($fields as $field): ?>
	const F_<?php echo strtoupper($field->getName()) ?> = '<?php echo $field->getName() ?>';
<?php endforeach ?>
<?php if ($hasEnum): ?>

<?php foreach ($fields as $field): ?>
<?php if ($field->isEnum()): ?>
<?php foreach ($field->getEnumValues() as $enumCode => $enumVal): ?>
	const <?php echo call_user_func($makeEnumConstName, $field, $enumCode) ?> = <?php echo $enumVal === null
			? 'null' 
			: is_string($enumVal) ? "'$enumVal'" : $enumVal ?>;
<?php endforeach ?>
<?php endif ?>
<?php endforeach ?>

	public static $enumLabels = array(
<?php foreach ($enumLabels as $fieldName => $codeLabels): ?>
		'<?php echo $fieldName ?>' => array(
<?php foreach ($codeLabels as $code => $label): ?>
			self::<?php echo call_user_func($makeEnumConstName, $fieldName, $code) ?> => '<?php echo str_replace("'", "\'", $label) ?>',
<?php endforeach ?>
		),
<?php endforeach ?>
	);
<?php endif ?>

	/**
	 * @param array $initValues an array of values ($fieldName => $value) to initially set
	 * the <?php echo $modelName ?> with.
	 * @param bool $strict
	 * @param array $context
	 */
	protected function __construct($initValues = null, $strict = false, array $context = null) {

		$fields = array(
<?php $comma = "\t\t\t\t"; ?>
<?php foreach ($fields as $field): ?>
<?php //REM echo "$comma'{$field->getName()}' => {$field->getTplDefault()}" ?>
<?php echo "$comma'{$field->getName()}' => null" ?>
<?php $comma = ",\n\t\t\t\t"; ?>
<?php endforeach ?>

		);
<?php /*
		$relations = array(
<?php $comma = "\t\t\t\t"; ?>
<?php foreach ($relations as $relation): ?>
<?php echo "$comma'{$relation->getName()}' => null" ?>
<?php $comma = ",\n\t\t\t\t"; ?>
<?php endforeach ?>

		);

		parent::__construct($fields, $relations, $initValues, $strict);
*/ ?>

		parent::__construct($fields, $initValues, $strict, $context);
	}

	/**
	 * Get the name of this class's Model.
	 *
	 * @return string
	 */
	protected function getModelName() {
		return '<?php echo $modelName ?>';
	}

	/**
	 * Create a new <?php echo $modelName ?>
	 *
	 * The new record will be considered new until its primary key is set.
	 *
	 * An array of values can be given to initialize the record's fields. It
	 * is not required for all model's fields to have a value in the given
	 * array; the absent fields will be set to NULL.
	 *
	 * @param array $initValues an array containing values with which the
	 * Model's fields will be initialized. This
	 *
	 * @param bool $strict
	 *
	 * @param array $context
	 *
	 * @return <?php echo $modelClass, PHP_EOL ?>
	 */
	public static function create($initValues = null, $strict = false, array $context = null) {
		return new <?php echo $modelClass ?>($initValues, $strict, $context);
	}

<?php if ($primaryField !== null): ?>

	/**
	 * Set the value of this record's primary key.
	 */
	public function setPrimaryKeyValue($value) {
		$this->setPrimaryKeyValueImpl('<?php echo $primaryField->getName(true) ?>', $value);
	}

	/**
	 * @return mixed the value of this record's primary key.
	 */
	public function getPrimaryKeyValue() {
		return $this->get<?php echo $primaryField->getVarName(true) ?>();
	}

	/**
	 * Create a new <?php echo $modelName ?>, and load it from the database, according to its
	 * $primaryKey value.
	 * 
	 * @param mixed $<?php echo $primaryField->getVarName(false), PHP_EOL ?>
	 *
	 * @param array $context
	 * 
	 * @return <?php echo $modelClass ?> the data Model from the loaded record, or null if no
	 * record matching the given primary key has been found
	 */
	public static function load($<?php echo $primaryField->getVarName(false) ?>, array $context = null) {

		$model = new <?php echo $modelClass ?>(array(
			'<?php echo $primaryField->getName() ?>' => $<?php echo $primaryField->getVarName(false) ?>
		), false, $context);

		return $model->doLoad($context);
	}

	/**
	 * @param boolean $loadFromDB determines how the initial values are acquired.
	 * If TRUE, the data will be forcefully loaded from the datastore, throwing
	 * an Exception if the data corresponding to this model have been removes.
	 * If FALSE, the data will be tentatively retrieved from the initial values
	 * stored in the <?php echo $modelName ?>, throw an Exception if these data
	 * have not been filled up (which depends on the method used to fill in
	 * the model's data). If NULL, initial values will be used if they exist,
	 * else it will be tried to load the model from the datastore, eventually
	 * throwing an Exception if no method works.
	 *
	 * @return <?php echo $modelClass, PHP_EOL ?>
	 */
	public function getStoredCopy($loadFromDB = null) {
		return $this->doGetStoredCopy($loadFromDB);
	}

<?php else: ?>

	/**
	 * This method always throw an UnsupportedOperationException because this
	 * Model doesn't have a primary key.
	 */
	public function setPrimaryKeyValue($value) {
		throw new \UnsupportedOperationException('The model <?php echo $modelName ?> has no primary key');
	}

	/**
	 * This method always throw an UnsupportedOperationException because this
	 * Model doesn't have a primary key.
	 */
	function getPrimaryKeyValue() {
		throw new \UnsupportedOperationException('The model <?php echo $modelName ?> has no primary key');
	}

	/**
	 * This method always throw an UnsupportedOperationException because this
	 * Model doesn't have a primary key.
	 */
	public static function load($id, array $context = null) {
		throw new \UnsupportedOperationException('The model <?php echo $modelName ?> has no primary key');
	}
<?php endif ?>

	/**
	 * Load a %%Model%% from the given data. All model's fields must have a
	 * set value in the $data array. The model will be considered loaded and
	 * not-new, when being created this way.
	 *
	 * @param array $data
	 * @param array $context
	 * @return <?php echo $modelClass, PHP_EOL ?>
	 * @ignore
	 */
	public static function loadFromData(array $data, array $context = null) {
		return new <?php echo $modelClass ?>($data, true, $context);
	}

	/**
	 * @return <?php echo $tableClass, PHP_EOL ?>
	 */
	public static function getTable() {
		return <?php echo $tableClass ?>::getInstance();
	}
<?php foreach ($fields as $field): /** @var \ModelColumn $field */ ?>

<?php if ($field->getType() == ModelColumn::T_BOOL): ?>
	/**
	 * Get the value of the <?php echo $field->getVarName() ?> field.
	 *
	 * @return <?php echo $field->getPhpType(), PHP_EOL ?>
	 */
	public function is<?php echo $field->getVarName(true) ?>() {
		$value = $this->getField('<?php echo $field->getName() ?>');
		return $value === null ? null : ($value ? true : false);
	}
<?php elseif ($field->getType() == ModelColumn::T_DATE): ?>
	/**
	 * Get the value of the <?php echo $field->getVarName() ?> field.
	 *
	 * @return <?php echo $field->getPhpType(), PHP_EOL ?>
	 */
    public function get<?php echo $field->getVarName(true) ?>($format = DateHelper::SQL_DATE) {
		$datetime = $this->getField('<?php echo $field->getName() ?>');
		return DateHelper::getDateTimeAs($datetime, $format);
	}
<?php elseif ($field->getType() == ModelColumn::T_DATETIME): ?>
	/**
	 * Get the value of the <?php echo $field->getVarName() ?> field.
	 *
	 * @return <?php echo $field->getPhpType(), PHP_EOL ?>
	 */
	public function get<?php echo $field->getVarName(true) ?>($format = DateHelper::SQL_DATETIME) {
		$datetime = $this->getField('<?php echo $field->getName() ?>');
		return DateHelper::getDateTimeAs($datetime, $format);
	}
<?php else: ?>
	/**
	 * Get the value of the <?php echo $field->getVarName() ?> field.
	 *
	 * @return <?php echo $field->getPhpType(), PHP_EOL ?>
	 */
	public function get<?php echo $field->getVarName(true) ?>() {
		$v = $this->getField('<?php echo $field->getName() ?>');
		return $v === null ? null : <?php echo $field->getPhpConvertTypeString() ?> $v;
	}
<?php endif ?>

	/**
	 * Set the value of the <?php echo $field->getVarName() ?> field.
	 *
	 * @param <?php echo $field->getPhpType() ?> $<?php echo $field->getVarName(), PHP_EOL ?>
	 * @param <?php echo $field->isNullable() ? '$ignoredArgument' : '$forceAcceptNull', PHP_EOL ?>
	 * @return <?php echo $modelClass, PHP_EOL ?>
	 */
	public function set<?php echo $field->getVarName(true) ?>($<?php echo $field->getVarName() ?>
, <?php echo $field->isNullable() ? '$ignoredArgument' : '$forceAcceptNull' ?> = false) {
		$this->setColumn('<?php echo $field->getName() ?>', $<?php echo $field->getVarName() ?>
, <?php echo $field->isNullable() ? '$ignoredArgument' : '$forceAcceptNull' ?>);
		return $this;
	}
<?php endforeach ?>

<?php
foreach ($proxyMethods as $method) {
	echo $method;
}
?>
<?php foreach ($relations as $relation): ?>
<?php //if ($relation instanceof ModelRelationReferencingHasOne): ?>
	/**
	 *
	 * @param array $overrideContext
	 * @return <?php echo $relation->getTargetType('get') . PHP_EOL ?>
	 */
	public function get<?php echo $relation->getName() ?>(array $overrideContext = null) {
		return $this->getForeignModel('<?php echo $relation->getName() ?>', $overrideContext);
	}
	/**
	 *
	 * @param <?php echo $relation->getTargetType('set') . ' $' . lcfirst($relation->getName()) . PHP_EOL ?>
	 * @return <?php echo $relation->getTargetType('set') . PHP_EOL ?>
	 */
	public function set<?php echo $relation->getName() ?>(<?php echo $relation->getTargetType('set', true) ?> $<?php echo lcfirst($relation->getName()) ?>) {
		// return $this->getRelation('<?php echo $relation->getName() ?>')->get($this);
		return $this->setForeignModel('<?php echo $relation->getName() ?>', $<?php echo lcfirst($relation->getName()) ?>);
	}

<?php //endif ?>
<?php endforeach ?>
}
