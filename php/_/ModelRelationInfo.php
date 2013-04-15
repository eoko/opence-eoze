<?php

use eoko\cqlix\FieldMetadata;
use eoko\cqlix\Aliaser;
use eoko\cqlix\Model\Relation\Cardinality as RelationCardinality;

require_once __DIR__ . '/../eoko/cqlix/Model/Relation/Cardinality.php';

interface ModelRelationInfoHasOne extends ModelRelationMarkerHasOne {}
interface ModelRelationInfoHasMany extends ModelRelationMarkerHasMany {}

class ModelRelationReciproqueFactory {

	/** @var ModelRelationInfo */
	protected $relationInfo;
	/** @var Model */
	protected $reciproqueModel;

	function __construct(ModelRelationInfo $relationInfo, Model $reciproqueModel) {
		$this->relationInfo = $relationInfo;
		$this->reciproqueModel = $reciproqueModel;
	}

	/**
	 * @param Model $parentModel
	 * @return ModelRelation
	 */
	public function init(Model $parentModel) {
		$relation = $this->relationInfo->createRelation($parentModel);
		$relation->setFromModel($this->reciproqueModel);
		$parentModel->getInternal()->setRelation($relation);
	}
}

/**
 * RelationInfo represents abstract informations about a category of relations
 * (as opposed to {@link ModelRelation} which represent concrete informations
 * bound to a specific data reccord -- it is the same difference as between
 * ModelTable and Model).
 * @property myModelTable $targetTable
 * @property myModelTable $localTable
 */
abstract class ModelRelationInfo extends ModelFieldBase implements RelationCardinality {

	/** @var string */
	public $name;
	/** @var ModelTable */
	protected $localTable;
	/** @var ModelTable */
	public $targetTable;

	/** @var string */
	public $reciproqueName;

	protected $virtualVariables;

	public $buildJoinOnClauseFn = null;
//	public $buildFindModelCondition = null;
//	public $initCreatedModel = null;

	/**
	 * @var FieldMetadata
	 */
	protected $metaConfig;

	public function __construct($name, ModelTableProxy $localTable, ModelTableProxy $targetTableProxy) {

		$localTable->attach($this->localTable);
		$targetTableProxy->attach($this->targetTable);

		// configure
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				if (property_exists($this, $k)) {
					$this->$k = $v;
				} else {
					throw new IllegalArgumentException('Invalid config option: ' . $k);
				}
			}
		} else {
			$this->name = $name;
		}

		$this->configure();

		$this->virtualVariables = array(
			'localTable' => $this->localTable,
			'targetTable' => $this->targetTable
		);
	}

	/**
	 * @inheritdoc
	 */
	public function getCardinality() {
		if ($this instanceof ModelRelationInfoHasOne) {
			return self::ONE_TO_ONE;
		} else if ($this instanceof ModelRelationInfoHasMany) {
			return self::ONE_TO_MANY;
		}
	}

	public function configureMeta(array $config = null) {
		if ($this->metaConfig) {
			throw new IllegalStateException('Already configured');
		}
		$this->metaConfig = new FieldMetadata($config);
	}

	public function getMeta() {
		return $this->metaConfig;
	}

	public function __toString() {
		return get_class($this) . '{' . "$this->name: " . $this->localTable->getModelName() . ' => '
				. $this->targetTable->getModelName() . '}';
	}

	protected function configure() {}

	/**
	 * Get the name of the relation; which is also the name of the field in the
	 * {@link ModelTable} relations.
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get the target (foreign) table of the relation. The $targetTable variable
	 * must not be accessed directly
	 * @return ModelTable
	 */
	public function getTargetTable() {
		return $this->targetTable->getInstance();
	}

	/**
	 * @return ModelTableProxy
	 */
	public function getTargetTableProxy() {
		return $this->targetTable;
	}

	/**
	 * Get the local table (that is, the one that is the left one in the query
	 * join).
	 * {@internal The $localTable variable must no be accessed directly as a
	 * Modeltable, because it is a {@link ModelTableProxy proxy}. It can however
	 * be passed to methods waiting for a proxy.}
	 * @return ModelTable
	 */
	public function getLocalTable() {
		return $this->localTable->getInstance();
	}

	/**
	 * @return ModelTableProxy
	 */
	public function getLocalTableProxy() {
		return $this->localTable;
	}

	/**
	 *
	 * @param Model $parentModel
	 * @throws UnsupportedOperationException
	 * @return ModelRelation
	 */
	public function createRelation(/** @noinspection PhpUnusedParameterInspection */ Model $parentModel) {
		throw new UnsupportedOperationException(
			'Not supported yet: ' . $this . '->createRelation()'
		);
	}

	public function select(/** @noinspection PhpUnusedParameterInspection */ Query $query) {
		throw new UnsupportedOperationException;
	}

	protected $selectable = false;

	/** @noinspection PhpInconsistentReturnPointsInspection */
	public function getType() {
		if ($this->selectable) {
			if ($this instanceof ModelRelationInfoHasOne) {
				return ModelField::T_STRING;
			} else if ($this instanceof ModelRelationInfoHasMany) {
				return ModelField::T_INT;
			}
		} else {
			throw new UnsupportedOperationException;
		}
	}

	public function getSqlType() {
		return $this->getType();
	}

	public function castValue($value) {
		if ($value === null) {
			return null;
		}
		switch ($this->getType()) {
			case ModelField::T_STRING: return (string) $value;
			case ModelField::T_INT: return (int) $value;
		}
		throw new UnsupportedOperationException;
	}

	public function isNullable() {
		return true;
	}

//	protected function parseSelectJoinAlias(&$relationName, &$joinAlias, &$leftAlias) {
//
//		if ($relationName === null) $relationName = $this->name;
//
//		$joinAlias = $relationName;
//
//		if (count($parts = explode('->', $relationName)) > 1) {
//			array_pop($parts);
//			$leftAlias = implode('->', $parts);
//		} else {
//			$leftAlias = $this->localTable->getDBTableName();
//		}
//	}
	protected function parseSelectJoinAlias(&$relationName, &$joinAlias, &$leftAlias) {

		if ($relationName === null) $relationName = $this->name;

//		if (count($parts = preg_split('/->/', $relationName, 2)) == 2) {
//			$leftAlias = $parts[0];
//			$joinAlias = $relationName;
		if (count($parts = explode('->', $relationName)) > 1) {
			array_pop($parts);
			$leftAlias = implode('->', $parts);
			$joinAlias = $relationName;
		} else {
			$joinAlias = $relationName;
			$leftAlias = $this->localTable->getDBTableName();
		}
	}

	/**
	 * @internal this code is legacy from ModelTableQuery::getOrderFieldAlias() v5.0
	 *
	 * @param Aliaser $aliaser
	 * @return string
	 */
	protected function doGetSortClause(Aliaser $aliaser) {
		$query = $aliaser->getQuery();
		$fieldName = $this->getTargetTable()->getNameFieldName();
		return $query->join($this)->alias($fieldName);
	}

	public function getNameClause(
			/** @noinspection PhpUnusedParameterInspection */
			ModelTableQuery $query, $relationName = null, $alias = null) {

		if (!$this->selectable) return null;

		$this->parseSelectJoinAlias($relationName, $joinAlias, $leftAlias);

		if (null !== $labelFormat = $this->targetTable->getLabelSelectFormatString()) {
			$join = $query->join($this, $joinAlias, $leftAlias);
			return QueryFormattedSelect::createClause($query, $labelFormat, $join->foreignTableAlias);
		} else if ($this->targetTable->hasName()) {
			$join = $query->join($this, $joinAlias, $leftAlias);
//			$join->select(array($alias => $this->targetTable->getNameFieldName()));
			return $join->getQualifiedName($this->targetTable->getNameFieldName());
		} else {
			return $query;
		}
	}

//r	public function selectName(ModelTableQuery $query, $alias = null, $sqlAlias = false) {
	public function selectName(ModelTableQuery $query, $alias = null, $relationName = null) {

		if (!$this->selectable) {
			return $query;
		}

		if ($relationName === null) {
			$relationName = $this->name;
		}
		if ($alias === null) {
			$alias = $relationName;
		}

		$this->parseSelectJoinAlias($relationName, $joinAlias, $leftAlias);

//r		$alias = is_string($sqlAlias) ? $sqlAlias : $alias;

		// TODO getLabelSelectFormatString is app specific (implemented in
		// myTable)
		if (null !== $labelFormat = $this->targetTable->getLabelSelectFormatString()) {
			$join = $query->join($this, $joinAlias, $leftAlias);
			$join->selectFormatted($alias, $labelFormat);
		} else if ($this->targetTable->hasName()) {
			$join = $query->join($this, $joinAlias, $leftAlias);
			$join->select(array($alias => $this->targetTable->getNameFieldName()));
		} else {
			return $query;
		}

		return $query;
	}

	public function selectId(ModelTableQuery $query, $alias = null) {
		if (!$this->selectable) {
			// TODO a warning should be issued here, no?
			return;
		}

		$this->parseSelectJoinAlias($alias, $joinAlias, $leftAlias);

		if ($this->targetTable->hasPrimaryKey()) {
			$join = $query->join($this, $joinAlias, $leftAlias);
			$join->select(array($alias => $this->targetTable->getPrimaryKeyName()));
		}

		// TODO a warning should be issued here, no?
	}

	/**
	 * Select one or more field from this relation.
	 *
	 * This method add to the given query the selection of one or more of this
	 * relation's fields.
	 *
	 * If a single field name is given as a string, and neither an alias nor
	 * an alias prefix is specified for it, then the default alias
	 * `RelationName->fieldName` will be used.
	 *
	 * @param ModelTableQuery $query    the {@link Query} on which to operate
	 *        selection
	 * @param string|string[] $field_s The name of one of this relation's fields,
	 *        an array of field, or an associative indicating both aliases and
	 *        field names ({@see ModelTableQuery::select() for the complete syntax)
	 *
	 * @throws UnsupportedOperationException
	 *
	 * @return \ModelTableQuery
	 *
	 * @see ModelTableQuery::select() for the complete syntax of the field and
	 *        alias params
	 */
	public function selectFields(ModelTableQuery $query, $field_s) {
//		if (false === $this instanceof ModelRelationInfoHasOne) {
		if (!$this->canSelectFields($query, $field_s)) {
			throw new UnsupportedOperationException($this . '::selectField()'
					. ' Field(s) can only be selected from relations of type "has one"');
		} else {
			return $this->doSelectFields($query, $field_s);
		}
	}

	final protected function doSelectFields(ModelTableQuery $query, $field_s) {

		$this->parseSelectJoinAlias($this->name, $joinAlias, $leftAlias);
		$join = $query->join($this, $joinAlias, $leftAlias);
		$join->select($field_s);

		return $query;
	}

	protected function canSelectFields(
			/** @noinspection PhpUnusedParameterInspection */
			ModelTableQuery $query, $field_s) {

		return $this instanceof ModelRelationInfoHasOne;
	}

	protected $relationInstances = array();

	/**
	 * @param string $name
	 * @return ModelRelationInfo
	 */
	public function getRelationInfo($name) {

		if (isset($this->relationInstances[$name])) {
			return $this->relationInstances[$name];
		}

		$relationNames = explode('->', $name);
		$lastName = array_pop($relationNames);

		// $name must be splitted here, to get the full chain (ie. we cannot
		// just get the partial chain from the target table, because its name
		// params will be incorrect...)
		$relationChain = array($this);
		$hasMany = $this instanceof ModelRelationInfoHasMany;
		$lastRelation = $this;
		foreach ($relationNames as $relationName) {
			$lastRelation = $lastRelation->targetTable->getRelationInfo($relationName);
			$relationChain[] = $lastRelation;
			if ($lastRelation instanceof ModelRelationInfoHasMany) {
				$hasMany = true;
			}
		}

		// Last name
		// This may be either a field (column or virtual), or a relation.
		if (!$lastRelation->targetTable->hasColumn($lastName)
				&& !$lastRelation->targetTable->hasVirtual($lastName)) {
			$lastRelation = $lastRelation->targetTable->getRelationInfo($lastName);
			$relationChain[] = $lastRelation;
			if ($lastRelation instanceof ModelRelationInfoHasMany) {
				$hasMany = true;
			}
		}

		if ($hasMany) {
			return new ModelRelationInfoChainHasMany($relationChain);
		} else {
			return new ModelRelationInfoChainHasOne($relationChain);
		}
	}

	/**
	 * @param ModelTableQuery $query
	 * @param string $alias
	 * @param string $leftAlias
	 * @return QueryJoin
	 */
	public function createJoin(ModelTableQuery $query, $alias = null, $leftAlias = null) {
		if ($alias === null) {
			$alias = $this->name;
		}
		$join = $this->doCreateJoin($query, $alias, $leftAlias);
		$this->addJoinWhere($join);
		return $join;
	}

	/**
	 * Add custom application's where clause to the join, at its creation time.
	 * This method must return the resulting join. The addition of the extra
	 * where clause can be handled by the ModelRelationInfo implementation
	 * when it creates the join, instead of relying on the base class for calling
	 * this method; in this case this method should just return the passed join.
	 *
	 * @param QueryJoin $join
	 * @throws UnsupportedOperationException
	 * @return QueryJoin
	 *
	 * @see ModelRelationInfo::createJoin()
	 */
	protected function addJoinWhere(/** @noinspection PhpUnusedParameterInspection */ QueryJoin $join) {
		throw new UnsupportedOperationException("$this::addJoinWhere()");
	}

	/**
	 * @param ModelTableQuery $query
	 * @param string $alias
	 * @param string $leftAlias
	 * @throws UnsupportedOperationException
	 * @return QueryJoin
	 */
	protected function doCreateJoin(
			/** @noinspection PhpUnusedParameterInspection */
			ModelTableQuery $query, $alias = null, $leftAlias = null) {

		throw new UnsupportedOperationException("$this::createJoin()");
	}

	public function orderClause(
			/** @noinspection PhpUnusedParameterInspection */
			$dir, $tableAlias = null) {

		$dir = Query::protectDir($dir);

		return new SqlVariable("`$this->name` $dir");
	}

	public function getField($name) {
		if ($this->targetTable->hasRelation($name)) {
			return $this->getRelationInfo($name);
		}
		return new ModelRelationInfoField($this, "$this->name->$name", $name);
	}

	/**
	 * @return ModelField
	 * @todo Finish implem
	 */
	public function getReferenceField() {
		//throw new UnsupportedOperationException(get_class($this) . '->getReferenceField()');
		Logger::get($this)->warn('Unsupported operation: ' . get_class($this) . '->getReferenceField()');
		return null;
	}

	/**
	 * @todo Finish implem -- not possible right now: we need a controller
	 * to autocreate the form field. Currently, controllers are user generated,
	 * so we are out of luck ...
	 */
	public function createCqlixFieldConfig() {
		// TODO
		// for now, getReferenceField() will return null for unsupported
		// relation types (ie indirect ones).
		if (null === $referenceField = $this->getReferenceField()) {
			return null;
		}
		$r = array(
			'name' => $this->name,
			'fieldType' => $this instanceof ModelRelationInfoHasOne ? 'hasOne' : 'hasMany',
			'type' => $this->getType(),
			'allowNull' => $this->isNullable(),
			'hasDefault' => $referenceField->hasDefault(),
			'allowBlank' => $referenceField->isNullable() || $referenceField->hasDefault(),
			'defaultValue' => $referenceField->getDefault(),
			'primaryKey' => $referenceField->isPrimary(),
		);

		if (null !== $controller = $this->targetTable->getDefaultController()) {
			$r['controller'] = $controller;
		}

		foreach (array('label', 'internal') as $meta) {
			if ($referenceField->getMeta()->$meta !== null) {
				$r[$meta] = $referenceField->getMeta()->$meta;
			}
		}

		$r['meta'] = $referenceField->getMeta()->toArray();

		return $r;
	}
}

class ModelRelationInfoField extends ModelFieldBase {

	/**@var ModelRelationInfo */
	protected $info;
	protected $fieldName;
	protected $name;

	public function __construct(ModelRelationInfo $info, $name, $fieldName) {
		$this->info = $info;
		$this->fieldName = $fieldName;
		$this->name = $name;
	}

	public function orderClause($dir) {
		$dir = Query::protectDir($dir);
		$relationName = $this->info->name;
		return new SqlVariable("`$relationName->$this->fieldName` $dir");
	}

	public function getName() {
		return $this->name;
	}

	public function getType() {
		return $this->getActualField()->getType();
	}

	public function getSqlType() {
		return $this->getActualField()->getSqlType();
	}

	public function isNullable() {
		return $this->getActualField()->isNullable();
	}

	public function getMeta() {
		return $this->getActualField()->getMeta();
	}

	public function getLength() {
		return $this->getActualField()->getLength();
	}

	/**
	 * @inheritdoc
	 */
	public function getSortClause($dir, Aliaser $aliaser) {
		$join = $aliaser->getQuery()->join($this->info);
		$field = $this->getActualField();
		return $field->getSortClause($dir, $join);
	}

	public function getActualField() {
		return $this->info->targetTable->getField($this->fieldName, true);
	}

	public function __call($method, $args) {
		$field = $this->getActualField();
		if (method_exists($field, $method)) {
			// Replaces Aliaser arguments with join aliasers
			foreach ($args as &$arg) {
				if ($arg instanceof Aliaser) {
					$arg = $arg->getQuery()->join($this->info);
				}
			}
			return call_user_func_array(array($field, $method), $args);
		} else {
			throw new IllegalStateException('Call to undefined method ' . get_class($this)
					. "::$method()");
		}
	}

}

abstract class ModelRelationInfoByReference extends ModelRelationInfo {

	public $referenceField;
	public $prefix;

	protected $uniqueBy;

	protected $whereJoin;

	function  __construct($name, ModelTableProxy $localTable, ModelTableProxy $targetTableProxy, $referenceField) {
		parent::__construct($name, $localTable, $targetTableProxy);

		if (is_array($referenceField)) {
			// Extract join where
			if (isset($referenceField['where'])) {
				$this->whereJoin = $referenceField['where'];
			}

			// Extract reference field name
			if (isset($referenceField['name'])) {
				$referenceField = $referenceField['name'];
			} else if (isset($referenceField['field'])) {
				$referenceField = $referenceField['field'];
			} else {
				throw new IllegalArgumentException('Reference field must contain "name" or "field".');
			}
		}

		$this->referenceField = $referenceField;
	}

	protected function addJoinWhere(QueryJoin $join) {

		$this->targetTable->addJoinWhere($join);

		if ($this->whereJoin) {
			$join->andWhere($this->whereJoin);
		}

		if ($this->uniqueBy) {
			foreach ($this->uniqueBy as $foreign => $local) {
				if (is_array($local)) {
					if (isset($local['value'])) {
						$join->whereAssoc($foreign, $local['value']);
					} else {
						throw new UnsupportedOperationException();
					}
				} else {
					$join->andWhere(
						$join->getQualifiedName($local, QueryJoin::TABLE_LOCAL)
						. ' = '
						. $join->getQualifiedName($foreign, QueryJoin::TABLE_FOREIGN)
					);
				}
			}
		}
	}
}

abstract class ModelRelationInfoHasReference extends ModelRelationInfoByReference {

	// OnDeleteAction
	const ODA_SET_NULL = 0;
	const ODA_DELETE   = 1;
	const ODA_NOTHING  = 2;
	const ODA_RESTRICT = 3;

	/**
	 * What to do when the refered model is deleted?
	 * @internal Each relation kind can specify its default onDeleteAction,
	 * in getDefaultOnDeleteAction()
	 * @var int NULL means default behaviour, this is dependant on the type and
	 * properties of the relation involved
	 */
	public $onDeleteAction = null;

	/**
	 * @param $targetPkValue
	 * @param bool $ignoreAssocWhere
	 * @return ModelTableQuery
	 */
	public function createFindTargetQuery($targetPkValue, $ignoreAssocWhere = false) {
		$query = $this->localTable->createQuery();
		$where = $query->createWhere(
			$query->getQualifiedName($this->referenceField) . '=?',
			$targetPkValue
		);
		if (!$ignoreAssocWhere) {
			$this->localTable->addAssocWhere($where, $query);
		}
		return $query->where($where);
	}

	/**
	 * Dispatches on delete processing of a record being refered by other tables.
	 *
	 * If the table's database engine support automatic cascadding, then nothing
	 * will be done here.
	 *
	 * @param mixed $targetPkValue the value of the primary key of the record
	 * being removed
	 *
	 * @throws IllegalArgumentException
	 * @throws SqlUserException
	 * @return mixed
	 */
	final public function onTargetDelete($targetPkValue) {
		if (!$this->localTable->isAutomaticCascadeEngine()) {
			switch ($this->getOnDeleteAction()) {
				case self::ODA_SET_NULL:
					return $this->onTargetDelete_setNull($targetPkValue);
				case self::ODA_DELETE:
					return $this->onTargetDelete_delete($targetPkValue);
				case self::ODA_NOTHING:
					return 0;
				case self::ODA_RESTRICT:
					throw new SqlUserException(
						null, 
						"Cet enregistrement ne peut pas être supprimé car il est référencé par "
						. "d'autres enregistrements.",
						"Contrainte d'intégrité"
					);
				default:
					throw new IllegalArgumentException();
			}
		}
		return null;
	}

	/**
	 * Gets the current onDelete action.
	 *
	 * @return string
	 */
	private function getOnDeleteAction() {
		return $this->onDeleteAction !== null
				? $this->onDeleteAction
				: $this->getDefaultOnDeleteAction();
	}

	/**
	 * Gets the current default onDelete action. This method will be called
	 * when the relation is notified other side's deletion, and no explicit
	 * onDelete action has been set. The method is called at the time it is
	 * needed, so it can access variables in the context to dynamically decide
	 * what the onDelete action should be.
	 *
	 * @internal The determining of the default onDelete action is mostly
	 * dependant on the kind of relation and the properties of the tables and
	 * reference fields. It cannot be determined in the constructor of the
	 * relation though, because trying to access the ModelTable instances from
	 * there would most oftenly result in infinite loops at the Relation
	 * building stage...
	 *
	 * @return string
	 */
	protected function getDefaultOnDeleteAction() {
		return self::ODA_SET_NULL;
	}

	protected function onTargetDelete_setNull($targetPkValue) {
		return $this->createFindTargetQuery($targetPkValue, true)
			->set($this->referenceField, null)
			->executeUpdate();
	}

	protected function onTargetDelete_delete($targetPkValue) {
		return $this->localTable->deleteWhereIs(array(
			$this->referenceField => $targetPkValue
		));
	}

	public function getReferenceField() {
		return $this->localTable->getField($this->referenceField);
	}

	public function configureMeta(array $config = null) {
		if ($this->metaConfig !== null) {
			throw new IllegalStateException('Already configured');
		}
		if ($config === null) {
			$this->metaConfig = false;
		} else {
			$this->metaConfig = new FieldMetadata($config);
		}
	}

	public function getMeta() {
		if ($this->metaConfig === false) {
			return $this->localTable->getColumn($this->referenceField)->getMeta();
		} else {
			return $this->metaConfig;
		}
	}

}

class ModelRelationInfoReferencesOne extends ModelRelationInfoHasReference
		implements ModelRelationInfoHasOne {

	protected $rightField;

//	protected $uniqueBy;

	function  __construct($name, ModelTableProxy $localTable, ModelTableProxy $targetTableProxy, $referenceField, $rightField = null) {
		parent::__construct($name, $localTable, $targetTableProxy, $referenceField, $rightField);
		$this->selectable = true;
		$this->rightField = $rightField;
	}

	public function getUniqueBy() {
		return $this->uniqueBy;
	}

	protected function getDefaultOnDeleteAction() {
		// If the reference field must be unique (that is, what define the
		// relation as referencesOne, as opposed to referencesMany) AND the
		// said field cannot be NULL, then the default onDelete action should
		// be DELETE. If the field can be NULL, dupplicate NULL value would be
		// allowed though.
		$refField = $this->localTable->getColumn($this->referenceField);
		if (!$refField->isNullable() && $refField->isUnique()) {
			return self::ODA_DELETE;
		} else {
			return parent::getDefaultOnDeleteAction();
		}
	}

	public function createRelation(Model $parentModel) {
		return new ModelRelationReferencesOne($this, $parentModel);
	}

//	protected function addJoinWhere(QueryJoin $join) {
//
////		if ($this->uniqueBy) {
////			foreach ($this->uniqueBy as $foreign => $local) {
////				if (is_array($local)) {
////						if (isset($local['value'])) {
////							$join->whereAssoc($foreign, $local['value']);
////						} else {
////							throw new UnsupportedOperationException();
////						}
////				} else {
////					$join->andWhere(
////						$join->getQualifiedName($local, QueryJoin::TABLE_LOCAL)
////						. ' = '
////						. $join->getQualifiedName($foreign, QueryJoin::TABLE_FOREIGN)
////					);
////				}
////			}
////		}
//
//		return $join;
//	}

	/**
	 * Create the left join representing this relation, using the given
	 * {@link Query}. Note: the join is <b>not</b> registered in the Query by
	 * this method.
	 *
	 * @param \ModelTableQuery|\Query $query
	 * @param string $alias
	 * @param string $leftAlias
	 * @return QueryJoin
	 */
	protected function doCreateJoin(ModelTableQuery $query, $alias = null, $leftAlias = null) {
		return new QueryJoinLeft(
			$query,
			$this->getTargetTable(),
			$this->referenceField, 
			$this->rightField,
			$alias,

			// Left table
			$this->localTable, $leftAlias,

			$this->buildJoinOnClauseFn
		);
	}
}

class ModelRelationInfoIsRefered extends ModelRelationInfoByReference {

	function __construct($name, ModelTableProxy $localTable, 
			ModelTableProxy $targetTableProxy, $referenceField, $reciproqueName = null) {

		parent::__construct($name, $localTable, $targetTableProxy, $referenceField);
		$this->reciproqueName = $reciproqueName;
	}

//	protected function addJoinWhere(QueryJoin $join) {
//		parent::addJoinWhere($join);
//		$this->targetTable->addJoinWhere($join);
//		return $join;
//	}

	/**
	 * @return ModelRelationInfoHasReference
	 */
	public function findReciproque() {
		$localTableName = $this->localTable->getTableName();
		foreach ($this->targetTable->getRelationsInfo() as $relInfo) {
			if ($relInfo instanceof ModelRelationInfoHasReference
					&& $relInfo->targetTable->getTableName() === $localTableName) {

				return $relInfo;
			}
		}
		return null;
	}

	public function notifyDeleteToRefering($deletedValue) {
		if (null !== $reciproque = $this->findReciproque()) {
			if ($deletedValue instanceof Model) {
				$deletedValue = $deletedValue->getPrimaryKeyValue();
			}
			$reciproque->onTargetDelete($deletedValue);
		} else {
			Logger::get($this)->warn('Cannot find reciproque: ' . $this);
		}
	}

	/**
	 * @todo Test
	 */
	public function mergeDoublon($srcId, $destId, $context = null) {
		return $this->targetTable->createQuery()
			->whereContext($context) // 13/12/11 04:22 that should probably be changed to applyAssocWhere
			->set(
				$this->referenceField,
				$destId
			)
			->where(
				"$this->referenceField = ?",
				$srcId
			)
			->executeUpdate();
	}

	public function getReferenceField() {
		return $this->targetTable->getColumn($this->referenceField);
	}
}

class ModelRelationInfoReferedByOne extends ModelRelationInfoIsRefered 
		implements ModelRelationInfoHasOne {

	protected function configure() {
		$this->selectable = true;
	}

	public function createRelation(Model $parentModel) {
		return new ModelRelationReferedByOne($this, $parentModel);
	}

	/**
	 * @internal
	 * Create the reference field has it will be passed for the QueryJoin
	 * creation. This method is intended for overriding by subclasses.
	 * @return String|QueryJoinField
	 */
	protected function getJoinReferenceField() {
		return $this->referenceField;
	}

	protected function doCreateJoin(ModelTableQuery $query, $alias = null, $leftAlias = null) {
		$join = new QueryJoinLeft(
			$query,
			$this->targetTable,
			$this->localTable->getPrimaryKeyName(),
			$this->getJoinReferenceField(),
			$alias !== null ? $alias : $this->name,
			// Left table
			$this->localTable, $leftAlias,

			$this->buildJoinOnClauseFn
		);
		return $join;
	}

//	public function selectFields(ModelTableQuery $query, $fields = '*') {
//		$query->join($this)->select($fields, $this->name . '->');
//	}

}

class ModelRelationInfoReferredByOneAssoc extends ModelRelationInfoReferedByOne {

	/** @var ModelRelationInfoIndirectHasOne */
	public $assocRelationInfo;

	public function __construct(ModelRelationInfoIndirectHasOne $info, $name = null) {
		$this->doConstruct($info, $name, $info->localForeignKey);
	}

	protected final function doConstruct($info, $name, $referenceField) {
		if ($name === null) $name = "$info->name*";
		$this->assocRelationInfo = $info;
		parent::__construct(
			$name, // name
			$info->localTable, // local table
			$info->assocTable, // target table
			$referenceField, // reference field
			null // $reciproque name // TODO
		);
	}

	public function createRelation(Model $parentModel) {
		return new ModelRelationReferredByOneAssoc($this, $parentModel);
	}
}

class ModelRelationInfoReferredByOneAssocMirror extends ModelRelationInfoReferredByOneAssoc {

	public function __construct(ModelRelationInfoIndirectHasOneMirror $info, $name = null) {
		$this->doConstruct(
			$info, 
			$name, 
			array($info->localForeignKey, $info->otherForeignKey)
		);

		$this->buildJoinOnClauseFn = array($this, 'buildOnJoinClause');
	}

	/**
	 * @internal
	 *
	 * Callback for creating the {@link QueryJoinLeft} ON clause.
	 *
	 * @param array $leftField
	 * @param array|string $rightField
	 * @return string
	 */
	public function buildOnJoinClause($leftField, array $rightField) {
		$clauses = array();
		foreach ($rightField as $f) {
			$clauses[] = "$leftField = $f";
		}
		$clauses = trim(implode(' OR ', $clauses));
		return "($clauses)";
	}

	protected function getJoinReferenceField() {
		return new QueryJoinField_Multiple($this->referenceField);
	}

	public function getReferenceField() {
		return $this->targetTable->getColumn($this->referenceField[0]);
	}
}

/**
 * Relation for which the target Model can refer the parent Model by any of
 * multiple field. Concretely, that means that the target Model will be accessed
 * through a left join on (parentModelPk = refField1 OR parentModelPk = refField2 
 * OR etc.).
 * 
 * All the reference fields are considered semantically equivalent; that is, no
 * further meaning is given to the fact that the Model is referred by, for
 * example, refField2 rather than refField1. As a consequence, when target Models
 * are created, they will be indiferently linked by any of the refField (though,
 * in practice, the first one in the list will be most oftenly picked).
 */
class ModelRelationInfoReferedByOneOnMultipleFields extends ModelRelationInfoReferedByOne {

	public function __construct($name, ModelTableProxy $localTable, ModelTableProxy $targetTableProxy,
			$referenceField, $reciproqueName = null) {

		if (!is_array($referenceField)) {
			throw new IllegalArgumentException(
				'$referenceField should be an array. Use ModelRelationReferedByOne for '
				. 'reference on a single field.'
			);
		}

		parent::__construct(
			$name, $localTable, $targetTableProxy, $referenceField,
			$reciproqueName
		);

		$this->buildJoinOnClauseFn = array($this, 'buildOnJoinClause');
	}

	/**
	 * @internal
	 *
	 * Callback for creating the {@link QueryJoinLeft} ON clause.
	 *
	 * @param array $leftField
	 * @param array|string $rightField
	 * @return string
	 */
	public function buildOnJoinClause($leftField, array $rightField) {
		$clauses = array();
		foreach ($rightField as $f) {
			$clauses[] = "$leftField = $f";
		}
		$clauses = trim(implode(' OR ', $clauses));
		return "($clauses)";
	}

	protected function getJoinReferenceField() {
		return new QueryJoinField_Multiple($this->referenceField);
	}

	public function createRelation(Model $parentModel) {
		return new ModelRelationReferedByOneOnMultipleFields($this, $parentModel);
	}

	public function mergeDoublon($srcId, $destId, $context = null) {
		throw new UnsupportedOperationException(get_class($this) . '::mergeDoublon()');
	}
}

class ModelRelationInfoReferencesMany extends ModelRelationInfoHasReference
		implements ModelRelationInfoHasMany {

	protected function doCreateJoin(ModelTableQuery $query, $alias = null, $leftAlias = null) {
		return new QueryJoinLeft(
			$query,                  // Query
			$this->targetTable,      // foreign table
			$this->referenceField,   // left field
			$this->targetTable->getPrimaryKeyName(), // right field
			$alias === null ? $this->name : $alias,  // alias
			// Left table
			$this->localTable, $leftAlias
		);
	}
}

class ModelRelationInfoReferedByMany extends ModelRelationInfoIsRefered
		implements ModelRelationInfoHasMany {

	protected $selectable = true;

	public function createRelation(Model $parentModel) {
		return new ModelRelationReferedByMany($this, $parentModel);
	}

	protected function doCreateJoin(ModelTableQuery $query, $alias = null, $leftAlias = null) {
		return new QueryJoinLeft(
				$query,
				$this->targetTable,
				$this->localTable->getPrimaryKeyName(),
				$this->referenceField,
				$alias === null ? $this->name : $alias,  // alias
				// Left table
				$this->localTable, $leftAlias
		);
	}

	public function getNameClause(ModelTableQuery $query, $relationName = null, $alias = null) {

		if (!$this->localTable->hasPrimaryKey()) {
			$tableName = get_class($this->localTable);
			throw new UnsupportedOperationException(
				"Cannot natively select name from table $tableName with no primary key."
			);
		}

		$this->parseSelectJoinAlias($relationName, $joinAlias, $leftAlias);
		$join = $query->join($this, $joinAlias, $leftAlias);

		$leftField = $join->getQualifiedName(
				$this->localTable->getPrimaryKeyName(), QueryJoin::TABLE_LEFT);

		$q = $this->targetTable->createQuery($query->context)
				->applyAssocWhere($this->targetTable, "`$this->referenceField`=$leftField")
				->count();

		// This method must **NOT** append an alias if not is provided, because this clause
		// may be used in WHERE, not necessarilly in SELECT. That is the duty of the code
		// that calls this method to append the alias if needed.
		return new QuerySelectSub($q, $alias);
	}

	public function selectName(ModelTableQuery $query, $alias = null, $relationName = null) {
		if ($alias === null) $alias = $this->name;
		$query->select(
			$this->getNameClause(
				$query,
				$relationName,
				$alias !== null ? $alias : $this->name
			)
		);
	}

	public function createLoadQueryFor(
			/** @noinspection PhpUnusedParameterInspection */
			$pkValue, array $context = array(), $joinAlias = null) {

		return $this->targetTable
				->createLoadQuery(ModelSet::ONE_PASS, $context)
				->applyAssocWhere($this->targetTable, "$this->referenceField=?", $pkValue);
	}

	/**
	 * Select fields from this relation, considering it as an associative
	 * relation {@internal (effectively bypassing the canSelectFields() test)}.
	 *
	 * @param ModelTableQuery $query
	 * @param mixed $field_s
	 * @return ModelTableQuery
	 */
	public function selectAssocFields(ModelTableQuery $query, $field_s) {
		return $this->doSelectFields($query, $field_s);
	}

}

abstract class ModelRelationInfoByAssoc extends ModelRelationInfo {

	/**
	 * @var ModelTable
	 */
	public $assocTable;
	public $localForeignKey;
	public $otherForeignKey;

	protected $localField, $otherField;
	/** 
	 * @var string name of the target model(s)' assoc relation
	 */
	public $assocRelationName;

	/** @var ModelRelationInfo */
	private $assocRelationInfo = null;

	protected $whereAssoc;

	function  __construct(
		$name,
		ModelTableProxy $localTable, ModelTableProxy $targetTableProxy,
		ModelTableProxy $assocTableProxy,
		$localForeignKey, $otherForeignKey,
		$reciproqueRelationName = null, $assocRelationName = null
	) {

		parent::__construct($name, $localTable, $targetTableProxy);

		$this->assocTable = $assocTableProxy->attach($this->assocTable);

		$this->localForeignKey = $localForeignKey;
		$this->otherForeignKey = $otherForeignKey;

		$this->localField = $this->localTable->getPrimaryKeyName();
		$this->otherField = $this->targetTable->getPrimaryKeyName();

		$this->virtualVariables['assocTable'] = $this->assocTable;

		$this->reciproqueName = $reciproqueRelationName;
		$this->assocRelationName = $assocRelationName;
	}

	/**
	 * @return ModelRelationInfoReferedByMany
	 * @throws IllegalStateException
	 */
	public function getAssocRelationInfo() {
		if (!$this->assocRelationInfo) {
			if (!$this->assocRelationName) throw new IllegalStateException(
				'Missing information: assoc relation name'
			);
			$this->assocRelationInfo = $this->localTable->getRelationInfo($this->assocRelationName);
		}
		return $this->assocRelationInfo;
	}

	/**
	 * @return ModelTable
	 */
	public function getAssocTable() {
		return $this->assocTable;
	}

	protected function doCreateJoin(ModelTableQuery $query, $alias = null, $leftTableAlias = null) {
		return new QueryJoinAssoc(
			$query,
			$this->localTable, $leftTableAlias,
			$this->targetTable,
			$this->assocTable,
			$this->localForeignKey,
			$this->otherForeignKey,
			$alias !== null ? $alias : $this->name,
			$this->localField
		);
	}

	protected function addJoinWhere(QueryJoin $join) {

		$this->assocTable->addJoinWhere($join);

		if ($this->whereAssoc) {
			foreach ($this->whereAssoc as $field => $value) {
				$join->whereAssoc($field, $value);
			}
		}
	}

	public function getReferenceField() {
		return $this->assocTable->getColumn($this->otherForeignKey);
	}

//	public function createCqlixFieldConfig() {
//		$cfg = parent::createCqlixFieldConfig();
//		$cfg['fieldType'] = 'hasMany';
//		return $cfg;
//	}
}

class ModelRelationInfoIndirectHasOne extends ModelRelationInfoByAssoc
		implements ModelRelationInfoHasOne {

	protected function configure() {
		$this->selectable = true;
	}

	public function createRelation(Model $parentModel) {
		return new ModelRelationIndirectHasOne($this, $parentModel);
	}
}

class ModelRelationInfoIndirectHasOneMirror extends ModelRelationInfoIndirectHasOne {

	public function  __construct(
		$name,
		ModelTableProxy $localTable, ModelTableProxy $targetTableProxy, ModelTableProxy $assocTableProxy,
		$localForeignKey, $otherForeignKey,
		$assocRelationName
	) {
		parent::__construct(
			$name,
			$localTable, $targetTableProxy, $assocTableProxy,
			$localForeignKey, $otherForeignKey,
			$name, $assocRelationName
		);
	}

	public function createRelation(Model $parentModel) {
		return new ModelRelationIndirectHasOneMirror($parentModel, $this);
	}

	protected function doCreateJoin(ModelTableQuery $query, $alias = null, $leftTableAlias = null) {
		return new QueryJoinAssocMirror(
			$query,
			$this->targetTable,
			$this->assocTable,
			$this->localForeignKey,
			$this->otherForeignKey,
			$alias !== null ? $alias : $this->name,
			$leftTableAlias
		);
	}
}

class ModelRelationInfoIndirectHasMany extends ModelRelationInfoByAssoc
		implements ModelRelationInfoHasMany {

	protected $selectable = true;

	/**
	 *
	 * @param Model $parentModel
	 * @return ModelRelationIndirectHasMany
	 */
	public function createRelation(Model $parentModel) {
		return new ModelRelationIndirectHasMany($this, $parentModel);
	}

	public function getNameClause(ModelTableQuery $query, $relationName = null, $alias = null) {
		$localTable = $this->getLocalTable();

		if (!$localTable->hasPrimaryKey()) {
			$tableName = get_class($localTable);
			throw new UnsupportedOperationException(
				"Cannot natively select name from table $tableName with no primary key."
			);
		}

		$this->parseSelectJoinAlias($relationName, $joinAlias, $leftTableName);

//r		dumpl(array(
//d			$alias, $joinAlias, $leftTableName
//		));
//		dump_trace(false);

		$assocDBTable = $this->assocTable->getDBTableName();
		$assocAlias = $query->getNextJoinAlias($assocDBTable);

//r		$leftTableName = $localTable->getDBTableName();
		$leftPK = $localTable->getPrimaryKeyName();

		$assocQuery = $this->assocTable->createQuery($query->context)
				->setTableAlias($assocAlias)
				->applyAssocWhere(
					$this->assocTable, 
					"`$leftTableName`.`$leftPK` = `$assocAlias`.`$this->localForeignKey`"
				)
				->count();

//		$assocQuery->where(
//			$this->assocTable->addAssocWhere(
//				$assocQuery->createWhere(
//					"`$leftTableName`.`$leftPK` = `$assocAlias`.`$this->localForeignKey`"
//				)
//				,$assocQuery
//			)
//		)
//		->count();

		return $assocQuery->getSql(
			true,
			'(',
			')' . ($alias !== null ? " AS `$alias`" : null)
		);
	}

	public function selectName(ModelTableQuery $query, $alias = null, $relationName = null) {
		$query->select(
			new SqlVariable($this->getNameClause(
				$query, $relationName,
				$alias !== null ? $alias : $this->name
			))
		);
	}

	public function selectId(ModelTableQuery $query, $alias = null) {

		$pk = $this->getLocalTable()->getPrimaryKeyName();

		// TODO $alias ?
		if ($alias === null) {
			$alias = $this->name;
		}

		$idField = $query->getQualifiedName($pk);

		$assocQuery = $this->assocTable
				->createQuery($query->context)
				->select(QuerySelectRaw::create("GROUP_CONCAT(`$this->otherForeignKey`)"))
				->applyAssocWhere($this->assocTable, "`$this->localForeignKey` = $idField");

		$query->select(new QuerySelectSub($assocQuery, $this->name));
	}

	public function createLoadQuery(array $context = array(), $joinAlias = null, &$join = null) {

		if ($joinAlias === null) $joinAlias = $this->assocRelationName ?
				$this->assocRelationName : $this->name . 'Assoc';

		$assocRelation = new ModelRelationInfoReferedByMany(
			$joinAlias,                 // name
			$this->targetTable,         // local table
			$this->assocTable,          // target table
			$this->otherForeignKey      // reference field
		);

		$query = $this->targetTable->createLoadQuery(
			ModelSet::ONE_PASS,
			$context !== null ? $context : array()
		);

		$join = $query->join($assocRelation);

		return $query;
	}

	public function createLoadQueryFor($pkValue, array $context = array(), $joinAlias = null) {
		/** @var QueryJoin $join */
		$query = $this->createLoadQuery($context, $joinAlias, $join);

		$where = $query->createWhere(
			$join->getQualifiedName($this->localForeignKey, QueryJoin::TABLE_FOREIGN) . ' = ?',
			$pkValue
		);
		$this->assocTable->addAssocWhere($where, $join);

		if (!$where->isNull()) {
			$query->where($where);
		}

		return $query;
	}

}

abstract class ModelRelationInfoChain extends ModelRelationInfo {

	/** @var array[ModelRelationInfo] */
	protected $relationChain;
	/** @var ModelRelationInfo */
	protected $targetRelation;
	/** @var string */
	public $name;

	public function __construct(array $relationChain) {
		if (count($relationChain) < 1) throw new IllegalArgumentException();
		$this->relationChain = $relationChain;

		$this->targetRelation = $relationChain[count($relationChain)-1];

		$names = array();
		foreach ($relationChain as $info) $names[] = $info->name;

		$this->selectable = $this->targetRelation;

		parent::__construct(
			implode('->', $names)			// name
			,$relationChain[0]->localTable	// $localTable,
			,$this->targetRelation->targetTable					// $targetTableProxy
		);
	}

	public function createJoin(ModelTableQuery $query, $alias = null, $leftAlias = null) {
		$name = $this->relationChain[0]->name;
		$this->parseSelectJoinAlias($name, $joinAlias, $leftAlias);
		$join = $query->join($this->relationChain[0], $joinAlias, $leftAlias);
		foreach (array_slice($this->relationChain, 1) as $rel) {
			$name .= "->$rel->name";
			$this->parseSelectJoinAlias($name, $joinAlias, $leftAlias);
			$join = $query->join($rel, $joinAlias, $leftAlias);
		}
		return array($join);
	}

	// overriden so that the target relation (ie the last one in the chain) 
	// uses the correct $alias and not its own one
	public function selectName(ModelTableQuery $query, $alias = null, $relationName = null) {

		// Ensure the base joins exist
		$this->createJoin($query);

		$this->targetRelation->selectName(
			$query,
			$alias !== null ? $alias : $this->name,
			$relationName !== null ? $relationName : $this->name
		);
	}

	public function getNameClause(ModelTableQuery $query, $relationName = null, $alias = null) {
		// This method must **NOT** append an alias if not is provided, because this clause
		// may be used in WHERE, not necessarilly in SELECT. That is the duty of the code
		// that calls this method to append the alias if needed.
		return $this->targetRelation->getNameClause(
			$query,
			$relationName !== null ? $relationName : $this->name
		);
	}

	// overriden so that the target relation (ie the last one in the chain) 
	// uses the correct $alias and not its own one
	public function selectId(ModelTableQuery $query, $alias = null) {
		parent::selectId(
			$query,
			$alias !== null ? $alias : $this->name
		);
	}

	public function getType() {
		return $this->targetRelation->getType();
	}

	public function getSqlType() {
		return $this->targetRelation->getSqlType();
	}

	public function getMeta() {
		return $this->targetRelation->getMeta();
	}
}

class ModelRelationInfoChainHasOne extends ModelRelationInfoChain
		implements ModelRelationInfoHasOne {

}

class ModelRelationInfoChainHasMany extends ModelRelationInfoChain
		implements ModelRelationInfoHasMany {

}
