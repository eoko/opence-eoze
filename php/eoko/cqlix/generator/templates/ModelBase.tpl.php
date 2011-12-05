<?php use \ModelColumn ?>
/**
 * Base of the <?php echo $modelName ?> Model.
 *
 * @package <?php echo $package ?>

 * @subpackage models_base
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
	const <?php echo self::makeEnumConstName($field, $enumCode) ?> = <?php echo $enumVal === null ? 'null' : $enumVal ?>;
<?php endforeach ?>
<?php endif ?>
<?php endforeach ?>

	public static $enumLabels = array(
<?php foreach ($enumLabels as $fieldName => $codeLabels): ?>
		'<?php echo $fieldName ?>' => array(
<?php foreach ($codeLabels as $code => $label): ?>
			self::<?php echo self::makeEnumConstName($fieldName, $code) ?> => '<?php echo str_replace("'", "\'", $label) ?>',
<?php endforeach ?>
		),
<?php endforeach ?>
	);
<?php endif ?>

	/**
	 * @param $values an array of values ($fieldName => $value) to initially set
	 * the <?php echo $modelName ?> with
	 */
	protected function __construct($initValues = null, $strict = false, array $params = array()) {

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

		parent::__construct($fields, $initValues, $strict, $params);
	}

	/**
	 * Get the name of this class's Model.
	 * @return string
	 */
	protected function getModelName() {
		return '<?php echo $modelName ?>';
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
	 * Model's fields will be initialized. This
	 * @return <?php echo $modelName?>

	 */
	static function create($initValues = null, $strict = false, array $params = array()) {
		return new <?php echo $modelName ?>($initValues, $strict, $params);
	}

<?php if ($primaryField !== null): ?>

	/**
	 * Set the value of this Reccord's primary key.
	 */
	function setPrimaryKeyValue($value) {
		$this->setPrimaryKeyValueImpl('<?php echo $primaryField->getName(true) ?>', $value);
	}

	/**
	 * @return mixed the value of this Reccord's primary key.
	 */
	function getPrimaryKeyValue() {
		return $this->get<?php echo $primaryField->getVarName(true) ?>();
	}

	/**
	 * Create a new <?php echo $modelName ?>, and load it from the database, according to its
	 * $primaryKey value.
	 * @param mixed $<?php echo $primaryField->getVarName(false) ?> 
	 * @return <?php echo $modelName ?> the data Model from the loaded reccord, or null if no
	 * reccord matching the given primary key has been found
	 */
	public static function load($<?php echo $primaryField->getVarName(false) ?>, array $params = array()) {
		$model = new <?php echo $modelName ?>(array(
			'<?php echo $primaryField->getName() ?>' => $<?php echo $primaryField->getVarName(false) ?>
		), false, $params);

		return $model->doLoad($params);
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
	 * @return <?php echo $modelName ?> 
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
		throw new UnsupportedOperationException('The model <?php echo $modelName ?> has no primary key');
	}

	/**
	 * This method always throw an UnsupportedOperationException because this
	 * Model doesn't have a primary key.
	 */
	function getPrimaryKeyValue() {
		throw new UnsupportedOperationException('The model <?php echo $modelName ?> has no primary key');
	}

	/**
	 * This method always throw an UnsupportedOperationException because this
	 * Model doesn't have a primary key.
	 */
	public static function load($id, array $params = array()) {
		throw new UnsupportedOperationException('The model <?php echo $modelName ?> has no primary key');
	}
<?php endif ?>

	/**
	 * Load a %%Model%% from the given data. All model's fields must have a
	 * set value in the $data array. The model will be considered loaded and
	 * not-new, when being created this way.
	 * @param ModelTable $table
	 * @param array $data
	 * @return %%Model%%
	 * @ignore
	 */
	public static function loadFromData(array $data, array $context = array()) {
//		$model = new <?php echo $modelName ?>();
//		$model->params = $model->context = $context;
//		$model->setAllFieldsFromDatabase($data);
//		return $model;
		return new <?php echo $modelName ?>($data, true, $context);
	}

	/**
	 * @return <?php echo $tableName ?>

	 */
	static function getTable() {
		return <?php echo $tableName ?>::getInstance();
	}
<?php foreach ($fields as $field): $field instanceof ModelColumn; // DBG ?>

<?php if ($field->getType() == ModelColumn::T_BOOL): ?>
	/**
	 * Get the value of the <?php echo $field->getVarName() ?> field.
	 * @return <?php echo $field->getPhpType() ?>

	 */
	function is<?php echo $field->getVarName(true) ?>() {
		$value = $this->getField('<?php echo $field->getName() ?>');
		return $value === null ? null : ($value ? true : false);
	}
<?php elseif ($field->getType() == ModelColumn::T_DATE): ?>
	/**
	 * Get the value of the <?php echo $field->getVarName() ?> field.
	 * @return <?php echo $field->getPhpType() ?>

	 */
	function get<?php echo $field->getVarName(true) ?>($format = DateHelper::SQL_DATE) {
		$datetime = $this->getField('<?php echo $field->getName() ?>');
		return DateHelper::getDateTimeAs($datetime, $format);
	}
<?php elseif ($field->getType() == ModelColumn::T_DATETIME): ?>
	/**
	 * Get the value of the <?php echo $field->getVarName() ?> field.
	 * @return <?php echo $field->getPhpType() ?>

	 */
	function get<?php echo $field->getVarName(true) ?>($format = DateHelper::SQL_DATETIME) {
		$datetime = $this->getField('<?php echo $field->getName() ?>');
		return DateHelper::getDateTimeAs($datetime, $format);
	}
<?php else: ?>
	/**
	 * Get the value of the <?php echo $field->getVarName() ?> field.
	 * @return <?php echo $field->getPhpType() ?>

	 */
	function get<?php echo $field->getVarName(true) ?>() {
		$v = $this->getField('<?php echo $field->getName() ?>');
		return $v === null ? null : <?php echo $field->getPhpConvertTypeString() ?> $v;
	}
<?php endif ?>

	/**
	 * Set the value of the <?php echo $field->getVarName() ?> field.
	 * @param <?php echo $field->getPhpType() ?> $value
	 * @return <?php echo $modelName ?> 

	 */
	function set<?php echo $field->getVarName(true) ?>($<?php echo $field->getVarName() ?>
, <?php echo $field->isNullable() ? '$ignoredArgument' : '$forceAcceptNull' ?> = false) {
		$this->setColumn('<?php echo $field->getName() ?>', $<?php echo $field->getVarName() ?>
, <?php echo $field->isNullable() ? '$ignoredArgument' : '$forceAcceptNull' ?>);
<?php /*
<?php if (!$field->isPrimary()): ?>
		// $this->setField('<?php echo $field->getName() ?>', $<?php echo $field->getVarName() ?>
		
		$this->setColumn('<?php echo $field->getName() ?>', $<?php echo $field->getVarName() ?>
, <?php echo $field->isNullable() ? '$ignoredArgument' : '$forceAcceptNull' ?>);
<?php else: ?>
		$this->setPrimaryKeyValue($<?php echo $field->getVarName() ?>);
<?php endif ?>
 */ ?>
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
	 * @return <?php echo $relation->getTargetType() ?>

	 */
	function get<?php echo $relation->getName() ?>(array $overrideContext = null) {
		return $this->getForeignModel('<?php echo $relation->getName() ?>', $overrideContext);
	}
	/**
	 *
	 * @return <?php echo $relation->getTargetModelName() ?>

	 */
	function set<?php echo $relation->getName() ?>(<?php echo $relation->getTargetType() ?> $<?php echo lcfirst($relation->getName()) ?>) {
		// return $this->getRelation('<?php echo $relation->getName() ?>')->get($this);
		return $this->setForeignModel('<?php echo $relation->getName() ?>', $<?php echo lcfirst($relation->getName()) ?>);
	}

<?php //endif ?>
<?php endforeach ?>
}
