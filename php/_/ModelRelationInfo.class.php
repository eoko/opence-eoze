<?php

use eoko\cqlix\FieldMetadata;

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
 * @property ModelTable $targetTable
 * @property ModelTable $localTable
 */
abstract class ModelRelationInfo extends ModelFieldBase {

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
	 * @return ModelRelation
	 */
	public function createRelation(Model $parentModel) {
		throw new UnsupportedOperationException(
			'Not supported yet: ' . $this . '->createRelation()'
		);
	}

	public function select(Query $query) {
		throw new UnsupportedOperationException;
	}

	protected $selectable = false;

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

	public function getNameClause(ModelTableQuery $query, $relationName = null, $alias = null) {
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
		if (!$this->selectable) return $query;

		if ($relationName === null) $relationName = $this->name;
		if ($alias === null) $alias = $relationName;
		
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

	public function selectId(Query $query, $alias = null) {
		if (!$this->selectable) return $query;

		$this->parseSelectJoinAlias($alias, $joinAlias, $leftAlias);
//		if ($alias === null) $alias = $this->name;

//		dumpl(array(
//			$this, $alias, $joinAlias, $leftAlias
//		));

		if ($this->targetTable->hasPrimaryKey()) {
			$join = $query->join($this, $joinAlias, $leftAlias);
			$join->select(array($alias => $this->targetTable->getPrimaryKeyName()));
		} else {
			return $query;
		}
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
	 * @param ModelTableQuery $query	the {@link Query} on which to operate
	 *		selection
	 * @param mixed $field	the name of one of this relation's fields,
	 *		an array of field, or an associative indicating both aliases and
	 *		field names ({@see ModelTableQuery::select() for the complete syntax)
	 * @param string $aliasPrefix	a prefix to be applied to all selected field's
	 *		names or alias
	 * @see ModelTableQuery::select() for the complete syntax of the field and
	 *		alias params
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

	protected function canSelectFields(ModelTableQuery $query, $field_s) {
		return $this instanceof ModelRelationInfoHasOne;
	}

	protected $relationInstances = array();

	/**
	 * @return ModelRelationInfo
	 */
	public function getRelationInfo($name) {
		if (isset($this->relationInstances[$name])) return $this->relationInstances[$name];

		// $name must be splitted here, to get the full chain (ie. we cannot
		// just get the partial chain from the target table, because its name
		// params will be incorrect...)
		$relationChain = array($this);
		$hasMany = $this instanceof ModelRelationInfoHasMany;
		$relation = $this;
		foreach (explode('->', $name) as $relationName) {
			$relation = $relation->targetTable->getRelationInfo($relationName);
			$relationChain[] = $relation;
			if ($relation instanceof ModelRelationInfoHasMany) $hasMany = true;
		}

		if ($hasMany) return new ModelRelationInfoChainHasMany($relationChain);
		else return new ModelRelationInfoChainHasOne($relationChain);
	}

	/**
	 * @return QueryJoin
	 */
	public function createJoin(ModelTableQuery $query, $alias = null, $leftAlias = null) {
		if ($alias === null) $alias = $this->name;
		return $this->addJoinWhere(
			$this->doCreateJoin($query, $alias, $leftAlias)
		);
	}

	/**
	 * Add custom application's where clause to the join, at its creation time.
	 * This method must return the resulting join. The addition of the extra
	 * where clause can be handled by the ModelRelationInfo implementation
	 * when it creates the join, instead of relying on the base class for calling
	 * this method; in this case this method should just return the passed join.
	 * @return QueryJoin
	 * @see ModelRelationInfo::createJoin()
	 */
	protected function addJoinWhere(QueryJoin $join) {
		throw new UnsupportedOperationException("$this::addJoinWhere()");
	}

	/**
	 * @return QueryJoin
	 */
	protected function doCreateJoin(ModelTableQuery $query, $alias = null, $leftAlias = null) {
		throw new UnsupportedOperationException("$this::createJoin()");
	}

	public function orderClause($dir, $tableAlias = null) {
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
	 * @return ModelColumn
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
//			'fieldType' => 'hasOne',
			'fieldType' => $this instanceof ModelRelationInfoHasOne ? 'hasOne' : 'hasMany',
			'type' => $this->getType(),
			'allowNull' => $this->isNullable(),
			'hasDefault' => $referenceField->hasDefault(),
			'allowBlank' => $referenceField->isNullable() || $referenceField->hasDefault(),
			'defaultValue' => $referenceField->getDefault(),
// not relevant			'length' => $referenceField->length,
			'primaryKey' => $referenceField->isPrimary(),
		);

		if (null !== $controller = $this->targetTable->getDefaultController()) {
			$r['controller'] = $controller;
		}

		foreach (array('label', 'internal') as $meta) {
			if ($referenceField->meta->$meta !== null) {
				$r[$meta] = $referenceField->meta->$meta;
			}
		}

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
		return $this->info->targetTable->getField($this->fieldName, true)->getType();
	}
	
	public function getSqlType() {
		return $this->info->targetTable->getField($this->fieldName, true)->getSqlType();
	}

	public function isNullable() {
		return $this->info->targetTable->getField($this->fieldName, true)->isNullable();
	}
	
	public function getMeta() {
		return $this->info->targetTable->getField($this->fieldName, true)->getMeta();
	}
	
	public function __call($method, $args) {
		$field = $this->info->targetTable->getField($this->fieldName, true);
		if (method_exists($field, $method)) {
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

	function  __construct($name, ModelTableProxy $localTable, ModelTableProxy $targetTableProxy, $referenceField) {
		parent::__construct($name, $localTable, $targetTableProxy);
		$this->referenceField = $referenceField;
	}
	
	protected function addJoinWhere(QueryJoin $join) {
		
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
		
		return $join;
	}
}

abstract class ModelRelationInfoHasReference extends ModelRelationInfoByReference {

	// OnDeleteAction
	const ODA_SET_NULL = 0;
	const ODA_DELETE   = 1;
	const ODA_NOTHING  = 2;

	/**
	 * What to do when the refered model is deleted?
	 * @internal Each relation kind can specify its default onDeleteAction,
	 * in getDefaultOnDeleteAction()
	 * @var int NULL means default behaviour, this is dependant on the type and
	 * properties of the relation involved
	 */
	public $onDeleteAction = null;

	/**
	 * @return ModelTableQuery
	 */
	public function createFindTargetQuery($targetPkValue, $ignoreAssocWhere = false) {
		$query = $this->localTable->createQuery();
		$where = $query->createWhere(
			$query->getQualifiedName($this->referenceField) . '=?',
			$targetPkValue
		);
		if (!$ignoreAssocWhere) {
			$where = $this->localTable->addAssocWhere($where);
		}
		return $query->where($where);
	}

	/**
	 * Dispatches on delete processing of a record being refered by other tables.
	 * This method could be overriden to implement custom onDelete action.
	 * @param mixed $targetPkValue the value of the primary key of the record
	 * being removed
	 * @return mixed
	 */
	public function onTargetDelete($targetPkValue) {
		switch ($this->getOnDeleteAction()) {
			case self::ODA_SET_NULL: return $this->onTargetDelete_setNull($targetPkValue);
			case self::ODA_DELETE: return $this->onTargetDelete_delete($targetPkValue);
			case self::ODA_NOTHING: return 0;
			default: throw new IllegalArgumentException();
		}
	}

	/**
	 * Gets the current onDelete action.
	 * @return const<ODA>
	 */
	private function getOnDeleteAction() {
		return $this->onDeleteAction !== null ?
				$this->onDeleteAction : $this->getDefaultOnDeleteAction();
	}

	/**
	 * Gets the current default onDelete action. This method will be called
	 * when the relation is notified other side's deletion, and no explicit
	 * onDelete action has been set. The method is called at the time it is
	 * needed, so it can access variables in the context to dynamically decide
	 * what the onDelete action should be.
	 * @internal The determining of the default onDelete action is mostly
	 * dependant on the kind of relation and the properties of the tables and
	 * reference fields. It cannot be determined in the constructor of the
	 * relation though, because trying to access the ModelTable instances from
	 * there would most oftenly result in infinite loops at the Relation
	 * building stage...
	 * @return const<ODA>
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
		return $this->localTable->getColumn($this->referenceField);
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
	 * @param Query $query
	 * @return QueryJoin
	 */
	protected function doCreateJoin(ModelTableQuery $query, $alias = null, $leftAlias = null) {
		return new QueryJoinLeft(
			$query,
			$this->getTargetTable(),
			$this->referenceField, 
			$this->rightField,
			$alias,
			$leftAlias !== null ? $leftAlias : $this->localTable,
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
	
	protected function addJoinWhere(QueryJoin $join) {
		parent::addJoinWhere($join);
		$this->targetTable->addJoinWhere($join);
		return $join;
	}

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
	}

	public function notifyDeleteToRefering($deletedValue) {
		if (null !== $reciproque = $this->findReciproque()) {
			$reciproque->onTargetDelete(
				$deletedValue instanceof Model ?
					$deletedValue->getPrimaryKeyValue() : $deletedValue
			);
		} else {
			Logger::get($this)->warn('Cannot find reciproque: ' . $this);
		}
	}

	/**
	 * @todo Test
	 */
	public function mergeDoublon($srcId, $destId, $context = null) {
		return $this->targetTable->createQuery()
			->whereContext($context)
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
			$leftAlias !== null ? $leftAlias : $this->localTable,
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
	 * Callback for creating the {@link QueryJoinLeft} ON clause.
	 * @param array $leftField
	 * @param string $rightField
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
class ModelRelatinInfoReferedByOneOnMultipleFields extends ModelRelationInfoReferedByOne {

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
	 * Callback for creating the {@link QueryJoinLeft} ON clause.
	 * @param array $leftField
	 * @param string $rightField
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
			$leftAlias !== null ? $leftAlias : $this->localTable
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
				$leftAlias !== null ? $leftAlias : $this->localTable
		);
	}

	public function getNameClause(ModelTableQuery $query, $relationName = null, $alias = null) {

		if (!$this->localTable->hasPrimaryKey()) {
			$tableName = get_class($this->localTable);
			throw new UnsupportedOperationException(
				"Cannot natively select name from table $tableName with no primary key.",
				$previous
			);
		}

		$this->parseSelectJoinAlias($relationName, $joinAlias, $leftAlias);
		$join = $query->join($this, $joinAlias, $leftAlias);

		$q = $this->targetTable->createQuery($query->context);
		$localField = $this->localTable->getPrimaryKeyName();
		
		$leftField = $join->getQualifiedName(
				$this->localTable->getPrimaryKeyName(), QueryJoin::TABLE_LEFT);
		
		$q->where(
			$this->targetTable->addAssocWhere(
				$q->createWhere("`$this->referenceField`=$leftField")
				,$q
			)
		);
		$q->count();

		return new QuerySelectSub($q, $alias !== null ? $alias : $this->name); // <= rev492
	}

	public function selectName(ModelTableQuery $query, $alias = null, $relationName = null) {
		if ($alias === null) $alias = $this->name;
		$query->select(
			$this->getNameClause(
				$query,
				$alias !== null ? $alias : $this->name,
				$relationName
//				$this->name // TODO check
			)
		);
	}

	public function createLoadQueryFor($pkValue, array $context = array(), $joinAlias = null) {
		$query = $this->targetTable->createLoadQuery(ModelSet::ONE_PASS, $context);
		return $query->where(
			$this->targetTable->addAssocWhere(
				$query->createWhere(
					"$this->referenceField=?",
					$pkValue
				)
				,$query
			)
		);
	}

	/**
	 * Select fields from this relation, considering it as an associative
	 * relation {@internal (effectively bypassing the canSelectFields() test)}.
	 * @param ModelTableQuery $query
	 * @param mixed $field_s
	 * @return ModelTableQuery
	 */
	public function selectAssocFields(ModelTableQuery $query, $field_s) {
		return $this->doSelectFields($query, $field_s);
	}

}

/**
 * @var ModelTable
 */
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
			$leftTableAlias === null ? $this->localTable : $leftTableAlias,
			$this->targetTable,
			$this->assocTable,
			$this->localForeignKey,
			$this->otherForeignKey,
			$alias !== null ? $alias : $this->name,
			$this->localField
		);
	}

	public function addJoinWhere(QueryJoin $join) {
		$this->assocTable->addJoinWhere($join);
		return $join;
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
				"Cannot natively select name from table $tableName with no primary key.",
				$previous
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

		$assocQuery = $this->assocTable->createQuery($query->context);
		$assocQuery->setTableAlias($assocAlias);
		$assocQuery->where(
			$this->assocTable->addAssocWhere(
				$assocQuery->createWhere(
					"`$leftTableName`.`$leftPK` = `$assocAlias`.`$this->localForeignKey`"
				)
				,$assocQuery
			)
		)
		->count();

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

		if ($alias === null) $alias = $this->name;

		$assocTable = $this->assocTable->getDBTableName();

		$idField = $query->getQualifiedName($pk);

		$assocQuery = $this->assocTable->createQuery($query->context);
		$query->select(
			new QuerySelectSub(
				$assocQuery
				->select(QuerySelectRaw::create("GROUP_CONCAT(`$this->otherForeignKey`)"))
				->where(
					$this->assocTable->addAssocWhere(
						$assocQuery->createWhere(
							"`$this->localForeignKey` = $idField"
						)
						,$assocQuery
					)
				)
				, $this->name
			)
		);

		// <editor-fold defaultstate="collapsed" desc="Alternative Query">
//		$query
//			->select(new QuerySelectRaw("@{$this->name}Ids as $alias"))
//			->andWhere(
//				"(@{$this->name}Ids:=(SELECT GROUP_CONCAT(`$this->localForeignKey`) "
//				. "FROM `$assocTable` WHERE `$this->localForeignKey` = $idField))"
//			);
		// </editor-fold>
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
		$query = $this->createLoadQuery($context, $joinAlias, $join);
		return $query->where(
			$this->assocTable->addAssocWhere(
				$query->createWhere(
					$join->getQualifiedName($this->localForeignKey, QueryJoin::TABLE_FOREIGN) . ' = ?',
					$pkValue
				)
				,$join
			)
		);
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
		return $this->targetRelation->getNameClause(
			$query,
			$relationName !== null ? $relationName : $this->name,
			$alias !== null ? $alias : $this->name
		);
	}

	// overriden so that the target relation (ie the last one in the chain) 
	// uses the correct $alias and not its own one
	public function selectId(Query $query, $alias = null) {
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