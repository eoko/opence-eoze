<?php
/**
 * @package PS-ORM-1
 * @author Éric Ortéga <eric@mail.com>
 * @copyright Copyright (c) 2010, Éric Ortéga
 * @license http://www.planysphere.fr/licenses/psopence.txt
 */

use eoko\config\ConfigManager;

/**
 * Base class of model's tables
 *
 * Each Model's ModelTable (see next paragraph) class is a singleton allowing
 * to perform operations which are tied to a given Model, but not with any
 * particular reccord in this model (eg. find multiple reccords, etc.)
 *
 * An xxxModelTableBase and an xxxModelTable class is created for each of the
 * application Model (which correspond, in actuality to database's tables).
 * The ...Base class will be overwritten each time the application's models are
 * updated, while the xxxModelTable class will be written only once; it is
 * almost empty when it is generated, and is intended to be the place where
 * customizations and configuration can be done on the model (without being
 * lost at each update).
 *
 * All of ModelTable and its subclasses' public methods can be called either
 * statically, or through {@link ModelTable::getInstance() getInstance()}->method().
 * Despite a longer syntax, the second form doesn't offer any advantage other
 * the static one. Yet, it can be useful to call methods this way because it
 * allows to call methods on a ModelTable without knowing its exact Model --
 * this property is heavily used in ModelTable and Model's own generic code --
 * or to pass a ModelTable as a parameter.
 *
 * @internal The methods of the ModelTable class which are protected, non-static, and
 * prefixed with a single underscore (eg _load) will have a static alias created
 * in the generated ModelBase classes. These alias method will consist of a
 * static public method, named after the name of the parent method without the
 * underscore prefix; their execution consists in calling the corresponding
 * parent method from the child class from their static singleton instance.
 *
 * @method ModelTable getInstance()
 * @method String getDBTable()
 * @method Bool hasPrimaryKey()
 * @method String getPrimaryKeyName()
 * @method ModelColumn getPrimaryKeyColumn()
 * @method ModelTableQuery createQuery()
 *
 * @method ModelTableColumn getColumn($name) Get a ModelTableColumn of
 * %%ModelTable%% from its name. This method is static in concrete ModelTable
 * implementations, but it is abstract in ModelTable class.
 * @method Array getColumns($excludeAutoOperation = false, $excludeFinal = false) This
 * method is static in concrete ModelTable implementations, but it is abstract
 * in ModelTable class.
 * 
 * @property public $modelName		name/class of the associated Model
 * @property public $tableName		name/class of this instance
 * @property public $dbTableName	name of the associated database table
 */
abstract class ModelTable extends ModelTableProxy {

	/**
	 * @var array[string]ModelColumn
	 */
	protected $cols;

	/**
	 * @var array[ModelRelationInfo]
	 */
	private $relations;

	/**
	 * @var array[VirtualField]
	 */
	protected $virtuals = array();

	public $renderers = null;

	private $plugins = null;
	
	private $constructed = false;

	/**
	 * Create a new ModelTable
	 *
	 * All ModelTable subclasses are actually intended to be singleton, so this
	 * constructor should never be used (it is called internally).
	 *
	 * Note: it is not safe to override ModelTable and xxxModelTableBase's
	 * constructors. The {@link configure()} method is intended to hold custom
	 * configuration for the singleton.
	 *
	 * @internal The constructor cannot, however, be made private, since the
	 * xxxModelTableBase subclasses need to call their parent's one...
	 *
	 * @param array $cols
	 */
	protected function __construct(&$cols, &$relations) {
		
		$this->cols = $cols;
		$this->relations = $relations;
		
		$this->preConfigure($this->cols, $this->relations, $this->virtuals);
		
		$this->configureBase($this->cols, $this->relations, $this->virtuals);
		
		$this->configure();
		
		$this->constructed = true;
	}
	
	protected function preConfigure(&$cols, &$relations, &$virtuals) {}

	/**
	 * Configuration of the ModelTable
	 *
	 * ModelTable implementations should not define a constructor (it will
	 * never be called). They can do initialization job in this configure
	 * method.
	 * 
	 * This method is called only once, the first time the ModelTable is needed
	 * (ie. the first time the class is used).
	 *
	 * @internal This empty implementation is kept in the base class, in case the
	 * childs ones got deleted...
	 */
	protected function configure() {
		// initialization ...
	}
	
	private $config;
	
	protected function getConfig() {
		return $this->config;
	}

	protected function configureBase() {
		
		$this->config = ConfigManager::get(
			ConfigManager::get('eoze/application/namespace') . "/cqlix/models/$this->dbTableName"
		);
		
		foreach ($this->relations as $name => $relation) {
			$relation->configureMeta(
				isset($this->config['relations'][$name])
					? $this->config['relations'][$name]
					: null
			);
		}

		foreach ($this->virtuals as $name => $virtual) {
			$virtual->configureMeta(
				isset($this->config['virtuals'][$name])
					? $this->config['virtuals'][$name]
					: null
			);
		}
	}

	protected function addVirtual(VirtualField $virtual, $name = null) {
		if ($this->constructed) {
			throw new IllegalStateException('This operation is only allowed during initialization');
		}
		if ($name === null) {
			$name = $virtual->getName();
		}
		$this->virtuals[$name] = $virtual;
	}

	/**
	 * Creates a new Model.
	 *
	 * The new reccord will be considered new until its primary key is set. This
	 * default behaviour can be controlled with the {@link ModelTable::forceNew()
	 * forceNew()} method (if you intend to use forceNew() just after the call
	 * of the present method, both calls can be combined by using the {@link
	 * createNewModel()} method).
	 *
	 * An array of values can be given to initialize the reccord's fields. It
	 * is not required for all model's fields to have a value in the given
	 * array; the absent fields will be set to NULL.
	 *
	 * This method is declared here only as a template marker. Being abstract
	 * and static, it is actually impossible to be accessed; the static modifier
	 * is mandatory though, to allow subclasses to declare a static method with
	 * the same name.
	 *
	 * @internal an abstract static method is quite a nonsense, maybe this
	 * declaration will not remain legal in future PHP versions...
	 *
	 * @param array $initValues an array containing values with which the
	 * Model's fields will be initialized.
	 * @param boolean $strict if set to TRUE, then all field of the model will be
	 * required to be set in $initValues, or an IllegalArgumentException will
	 * be thrown
	 * @return Model
	 */
	abstract static function createModel($initValues = null, $strict = false, array $params = array());

	/**
	 * Creates a new Model instance (see {@link createModel()}), and set the
	 * forceNew flag to TRUE.
	 *
	 * @param array $initValues see {@link createModel()}
	 * @param boolean $strict   see {@link createModel()}
	 * @param array $params     see {@link createModel()}
	 * @return Model
	 */
	protected function _createNewModel($initValues = null, $strict = false, array $params = array()) {
		return $this->createModel($initValues, $strict, $params)->forceNew();
	}

	/**
	 * Load a Model reccord from the database, selected by
	 * its primary key
	 *
	 * This method is declared here only as a template marker. Being abstract
	 * and static, it is actually impossible to be accessed; the static modifier
	 * is mandatory though, to allow subclasses to declare a static method with
	 * the same name.
	 *
	 * @internal an abstract static method is quite a non-sense, maybe this
	 * declaration will not remain legal in future PHP versions...
	 *
	 * @param mixed $primaryKeyValue the value of the primary key of the
	 * reccord to load
	 * @return Model
	 */
	abstract static function loadModel($primaryKeyValue, array $context = array());

	/**
	 * Create a new <?php echo $modelName ?> reccord initialized by the given $data
	 * array. All the model's fields must have a value set in the $data array.
	 * The <?php echo $modelName ?> reccord will be considered loaded and not-new.
	 *
	 * This method is declared here only as a template marker. Being abstract
	 * and static, it is actually impossible to be accessed; the static modifier
	 * is mandatory though, to allow subclasses to declare a static method with
	 * the same name.
	 *
	 * @internal an abstract static method is quite a non-sense, maybe this
	 * declaration will not remain legal in future PHP versions...
	 *
	 * @param array $data
	 * @return Model
	 */
	abstract static function loadModelFromData(array $data, array $params = array());

	protected function _getDBTableName() {
		return $this->getDBTable();
	}

	/**
	 *
	 * @param String $colName
	 * @return ModelColumn
	 */
	abstract static protected function getColumn($colName);

	/**
	 * Get whether the given object is an instance of this table's model
	 * @return Bool TRUE if $obj is an instance of <?php echo $modelName ?>
	 */
	abstract static public function isInstanceOfModel($obj);

	/**
	 * @param String $modelName
	 * @return ModelTable
	 */
	static function getModelTable($modelName) {
		return call_user_func(array($modelName, 'getTable'));
	}

	/**
	 * @param String $modelName
	 * @return ModelTable
	 */
	public static function getTable($tableName) {
		if ($tableName instanceof ModelTable) return $tableName;
		return call_user_func(array($tableName, 'getInstance'));
	}

	/**
	 * @return Query
	 */
	public abstract static function createQuery(array $params = array());

	/**
	 * Gets the default controller for CRUD operation on this table's model.
	 * This information is used by UI generator (like cqlix form generator),
	 * for example to create foreign combo fields.
	 * @return string
	 */
	public static function getDefaultController() {
		return null;
	}

	/**
	 *
	 * @param array $fields
	 * @param Query $query
	 * @return Query
	 */
	public function selectFields($fields, Query $query = null) {

		if ($query === null) $query = $this->createQuery();

		foreach ($fields as $k => $v) {

			if (is_string($k)) {
				$field = $k;
				$alias = $v;
			} else {
				$field = $v;
				$alias = null;
			}

			// Parse relation fields RelationName->fieldName
			if (count($parts = explode('->', $field)) > 1) {
				$field = array_pop($parts);
				$relation = implode('->', $parts);
				$this->selectRelationField($query, $relation, $field, $alias);
			} else {
				$this->selectField($query, $field, $alias);
			}
		}

		return $query;
	}

	protected function selectRelationField(Query $query, $relation, $field, $alias = null) {
		if (is_string($relation)) $relation = $this->getRelationInfo($relation);
		if ($relation->targetTable->hasRelation($field)) {
			$relation->getRelationInfo($field)->selectName($query, $alias);
		} else {
			if ($alias !== null) $field = array($alias => $field);
			$relation->selectFields($query, $field);
		}
	}

	protected function selectField(Query $query, $field, $alias = null) {
		if ($this->hasColumn($field)) {
			$query->select(QuerySelect::create($field, $alias, $this));
		} else if ($this->hasRelation($field)) {
			$this->relations[$field]->selectName($query, $alias);
		} else if (isset($this->virtuals[$field])) {
			$this->virtuals[$field]->select($query, $alias);
		} else if ($field[0] === '_' && isset($this->virtuals[$f = substr($field, 1)])) {
			$this->virtuals[$f]->select($query, $alias);
		} else {
			throw new IllegalArgumentException($this->getTableName() . ' has no field "'
					. $field . '"');
		}
	}

	/**
	 * @ignore
	 *
	 * @param mixed $excludeAutoOperation boolean FALSE or {ModelColumn::OP_CREATE | ModelColumn::OP_UPDATE}
	 * @param Bool $excludeFinal
	 * @return array
	 */
	protected function _getColumns($excludeAutoOperation = false, $excludeFinal = false) {
		// Return whole set if no exclusion
		if (!$excludeAutoOperation || !$excludeFinal) return $this->cols;

		// Else, filter...
		$r = array();
		foreach ($this->cols as $col) {
			$col instanceof ModelColumn;
			if ((!$col->isFinal() || !$excludeFinal) && ($excludeAutoOperation === false || !$col->isAuto($excludeAutoOperation))) {
				$r[] = $col;
			}
		}
		return $r;
	}

	protected function _buildSelectAllColumns($tableName = null, $alias_es = null, $quoteTable = true) {
		$parts = array();
		$qTable = $tableName === null ? null : 
			($quoteTable ? Query::quoteName($tableName) . '.' : "$tableName.");
		if ($alias_es === null) {
			foreach ($this->_getColumns() as $col) {
				$parts[] = "$qTable$col->getName()";
			}
		} else if (is_array($alias_es)) {
			if (count($alias_es) == count($this->cols)) {
				foreach ($this->_getColumns() as $i => $col) {
					$qAlias = Query::quoteName($alias_es[$i]);
					$qName = Query::quoteName($col->getName());
					$parts[] = "$qTable$qName AS $qAlias";
				}
			} else {
				foreach ($this->_getColumns() as $col) {
					$colName = $col->getName();
					$qAlias = array_key_exists($colName, $alias_es) ?
							' AS ' . Query::quoteName($alias_es[$colName]) : null;
					$qName = Query::quoteName($colName);
					$parts[] = "$qTable$qName$qAlias";
				}
			}
		} else {
			foreach ($this->_getColumns() as $col) {
				$colName = $col->getName();
				$parts[] = "$qTable`$colName` AS `$alias_es$colName`";
			}
		}
		return implode(', ', $parts);
	}

	/**
	 *
	 * @param String $name
	 * @return Bool
	 */
	protected function _hasColumn($name) {
		return array_key_exists($name, $this->cols);
	}

	// TODO field/col disambiguation
	/**
	 * Get a ModelTableColumn of %%ModelTable%% from its name
	 * @param string $name
	 * @return ModelColumn the column matching the given field name, or NULL if
	 * this Model have no field matching this name
	 * @ignore
	 */
	protected function _getColumn($name, $require = true) {
		if (isset($this->cols[$name])) {
			return $this->cols[$name];
		} else if (count($parts = explode('->', $name)) > 1) {
			$col = array_pop($parts);
			$relation = $this->getRelationInfo(implode('->', $parts));
			return $relation->targetTable->getColumn($col);
		} else {
			if ($require) {
				throw new IllegalStateException("Model $this->modelName has no column $name");
			} else {
				return null;
			}
		}
	}

	protected function _hasSetter($name) {
		return $this->hasColumn($name)
				|| method_exists($this->modelName, "set$name");
	}

	abstract public static function hasColumn($name);
	
	protected function _hasRelation($name) {
		return array_key_exists($name, $this->relations);
	}

	abstract public static function hasRelation($name);

	abstract public static function hasName();

	protected function _hasName() {
		if (null === $name = $this->getNameFieldName(false)) {
			return false;
		} else {
			return $this->hasColumn($name);
		}
//		return $this->hasColumn();
//		if ($this->hasColumn('name') || $this->hasColumn('nom'))
//				return true;
	}

	abstract public static function getNameFieldName($require = true);

	protected function _getNameFieldName($require = true) {
		if ($this->hasColumn('label')) return 'label';
		else if ($this->hasColumn('name')) return 'name';
		else if ($this->hasColumn('nom')) return 'nom';
		else if ($require) throw new IllegalStateException();
		else return null;
	}
	
	/**
	 * Creates a Query with its WHERE claused configured to match only
	 * 
	 * @param array $context
	 * @return ModelTableQuery 
	 */
	abstract public static function createReadQuery(array $context = array());
	
	protected function _createReadQuery(array $context = array()) {
		
		$query = $this->createQuery($context);
		
		return $query;
	}

	const LOAD_NONE   = 0;
	const LOAD_NAME   = 1;
	const LOAD_ID     = 2;
	const LOAD_FIELD  = 3;
	const LOAD_FULL   = 3;

	/**
	 * @return ModelTableQuery
	 */
	abstract public static function createLoadQuery($relationsMode = ModelTable::LOAD_NAME, array $params = array());
	/**
	 * @return ModelTableQuery
	 */
	protected function _createLoadQuery($relationsMode = ModelTable::LOAD_NAME, array $params = array()) {
		
		$query = $this->createReadQuery($params);

		foreach ($this->getColumns() as $col) {
			$col->select($query);
		}

		$this->applyLoadQueryDefaultOrder($query);

		foreach ($this->virtuals as $virtual) {
			$virtual->select($query);
		}

		if (is_array($relationsMode)) {
			foreach ($relationsMode as $mode => $values) {
				switch ($mode) {
					case self::LOAD_NAME:
						foreach ($values as $relation) {
							$this->getRelationInfo($relation)->selectName($query);
						}
						break;
					case self::LOAD_ID:
						foreach ($values as $relation) {
							$this->getRelationInfo($relation)->selectId($query);
						}
						break;
					case self::LOAD_FULL:
						foreach ($values as $relation => $fields) {
							$this->getRelationInfo($relation)->selectFields($query, $fields);
						}
						break;
				}
			}
		} else {
			switch ($relationsMode) {
				case ModelTable::LOAD_NAME:
					foreach ($this->relations as $relation) {
						$relation->selectName($query);
					}
					break;
				case ModelTable::LOAD_ID:
					foreach ($this->relations as $relation) {
						$relation->selectId($query);
					}
					break;
				case ModelTable::LOAD_NONE: break;
				case ModelTable::LOAD_FULL: throw new UnsupportedOperationException();
				default:
					throw new IllegalArgumentException("Invalid \$relationMode: $relationMode");
			}
		}

		return $query;
	}

	protected function applyLoadQueryDefaultOrder(Query $query) {}

	/**
	 * @return ModelRelationInfo
	 */
	public abstract static function getRelationInfo($name, $requireType = false);

	/**
	 *
	 * @param string $name
	 * @return ModelRelationInfo
	 */
	protected function _getRelationInfo($name, $requireType = false) {

		if (count($parts = explode('->', $name, 2)) == 2) {
			$relation = $this->relations[$parts[0]]->getRelationInfo($parts[1]);
		} else {
			if (!isset($this->relations[$name])) {
				throw new IllegalArgumentException(
					get_class($this) . ' has no relation ' . $name
				);
			}
			$relation = $this->relations[$name];
		}

		if ($requireType === false) {
			return $relation;
		} else {
			if ($requireType === ModelRelation::HAS_MANY) {
				if ($relation instanceof ModelRelationInfoHasMany) return $relation;
			} else if ($requireType === ModelRelation::HAS_ONE) {
				if ($relation instanceof ModelRelationInfoHasOne) return $relation;
			} else {
				throw new IllegalArgumentException('$requireType');
			}
			
			$class = get_class($this);
			$type = $requireType == ModelRelation::HAS_MANY ? 'HAS_MANY' : 'HAS_ONE';
			throw new IllegalStateException("The relation $name of table $class is not of type $type");
		}
	}

	/**
	 * @param string $name
	 * @return ModelRelationInfoHasMany
	 */
	public abstract static function getHasManyRelationInfo($name);

	/**
	 * @param string $name
	 * @return ModelRelationInfoHasMany
	 */
	protected function _getHasManyRelationInfo($name) {
		return $this->getRelationInfo($name, ModelRelation::HAS_MANY);
	}

	/**
	 * @param string $name
	 * @return ModelRelationInfoHasOne
	 */
	public abstract static function getHasOneRelationInfo($name);

	/**
	 * @param string $name
	 * @return ModelRelationInfoHasOne
	 */
	protected function _getHasOneRelationInfo($name) {
		return $this->getRelationInfo($name, ModelRelation::HAS_ONE);
	}

	/**
	 *
	 * @return array
	 */
	protected function _getRelationsInfo() {
		return $this->relations;
	}

	abstract public static function getRelationsInfo();

	abstract public static function getRelationNames();
	protected function _getRelationNames() {
		return array_keys($this->getRelationsInfo());
	}

	abstract public static function hasField($name);

	protected function _hasField($name) {
		return $this->_hasColumn($name)
				|| $this->_hasRelation($name);
//				|| array_key_exists($name, $this->virtuals);
	}

	abstract public static function hasVirtual($name);

	protected function _hasVirtual($name) {
		return array_key_exists($name, $this->virtuals);
	}
	
	abstract public static function isVirtualCachable($name);
	
	protected function _isVirtualCachable($name) {
		return $this->virtuals[$name]->isCachable();
	}

	/**
	 * @return VirtualField
	 */
	abstract public static function getVirtual($name);

	/**
	 * @return VirtualField
	 */
	protected function _getVirtual($name) {
		return $this->virtuals[$name];
	}

	abstract public static function getVirtualNames();
	protected function _getVirtualNames() {
		return array_keys($this->virtuals);
	}

	/**
	 * Parses the given $name and tests whether it matches an assoc relation
	 * field naming pattern. If it is the case, the passed $relationName and
	 * $fieldName variable will be set; notice that is the test doesn't pass,
	 * then these variables won't be modified.
	 * @param string $name
	 * @param string $relationName
	 * @param string $fieldName
	 * @return TRUE if the submitted name is recognized as an assoc relation
	 * field, else FALSE
	 */
	public static function parseAssocRelationField($name, &$relationName = null, &$fieldName = null) {
		if (preg_match('/^<(\w+)>(\w+)$/', $name, $m)) {
			list(, $relationName, $fieldName) = $m;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return ModelField
	 */
	abstract public static function getField($name, $require = false);

	/**
	 * @internal on 2011-02-07, $require default has been changed from FALSE to TRUE!!!
	 */
	protected function _getField($name, $require = true) {
		if ($this->_hasColumn($name)) {
			return $this->cols[$name];
		} else if ($this->_hasRelation($name)) {
			return $this->relations[$name];
		} else if (isset($this->virtuals[$name])) {
			return $this->virtuals[$name];
		} else if (count($parts = explode('->', $name)) > 1) {
			$field = array_pop($parts);
			$relation = implode('->', $parts);
			return $this->getRelationInfo($relation)->getField($field);
		} else if (self::parseAssocRelationField($name, $assocRelation, $field)) {
			throw new UnsupportedOperationException('The following has not been tested...');
			return $this->getRelationInfo($assocRelation)->getField($field);
		} else if (!$require) {
			return null;
		} else throw new IllegalArgumentException(
			'Table ' . get_class($this) . " has no field $name"
		);
	}

	protected function _insertRandom($n, $usr_mod = null, $zealous = false) {

		for ($i=0; $i<$n; $i++) {

			$init = array();

			foreach ($this->_getColumns() as $col) {
				$col instanceof ModelColumn;
				if ($usr_mod !== null && $col->getAutoValueId(ModelColumn::OP_CREATE) == ModelColumn::AUTO_CURRENT_USER) {
					$init[$col->getName()] = $usr_mod;
				} else {
					if (!$col->isPrimary() && ($zealous || $col->isRequired(ModelColumn::OP_CREATE))) {
						$init[$col->getName()] = $col->generateRandomValue($this);

						if ($init[$col->getName()] === false) unset($init[$col->getName()]);
					}
				}
			}

//			print_r($init); die;

			$model = $this->createModel($init);
			$model->save();
		}
	}

	public static function proxy(&$tableVar, $tableName, $dbTableName = null, $modelName = null) {
		if ($dbTableName === null) {
			return new ModelTableProxy($tableVar, $tableName);
		} else {
			return new ModelTableProxyEx($tableVar, $tableName, $dbTableName, $modelName);
		}
	}

	/**
	 * Get all %%ModelTable%%'s columns names in an array
	 * @return Array the name of the columns as an array of strings
	 * @ignore
	 */
	protected function _getColumnNames() {
		return array_keys($this->cols);
	}

	/**
	 * Delete a %%Model%% reccord, selected by its primary key
	 * @param mixed $primaryKeyValue the value of the primary key of the reccord
	 * to be deleted
	 * @return Boolean TRUE if a row was successfuly deleted in the database,
	 * else FALSE
	 * @ignore
	 */
	protected function _delete($primaryKeyValue) {
		return $this->executeDelete(
			$this->createQuery()
				->where($this->getPrimaryKeyName() . ' = ?', $primaryKeyValue)
		) === 1;
	}

	function __toString() {
		return $this->getTableName();
	}

	/**
	 * Starts a search in %%ModelTable%%
	 * @param $condition
	 * @param $inputs,...
	 * @return ModelTableFinder
	 * @see QueryWhere for the syntax of a search
	 * @ignore
	 */
	protected function _find($condition = null, $inputs = null) {
		if (func_num_args() > 2) $inputs = array_splice(func_get_args(), 1);
		return new ModelTableFinder($this, $condition, $inputs);
	}

	/**
	 *
	 * @param String $col
	 * @param mixed $value
	 * @param Const $mode
	 * @return ModelSet
	 */
	protected function _findBy($col, $value, $mode) {

		if (!array_key_exists($col, $this->cols)) {
			throw new IllegalArgumentException();
		}

		return ModelSet::create($this,
				$this->createQuery()->where($col . ' = ?', $value), $mode);
	}

//	/**
//	 * @return ModelTableFirstFinder
//	 * @ignore
//	 */
//	abstract public static function findFirst($condition = null, $inputs = null);
//	/**
//	 * Starts a search of a single reccord in %%ModelTable%%
//	 * @param $condition
//	 * @param $inputs,...
//	 * @return Model
//	 * @see QueryWhere for the syntax of a search
//	 * @ignore
//	 */
//	protected function _findFirst($condition = null, $inputs = null) {
//		if (func_num_args() > 2) $inputs = array_splice(func_get_args(), 1);
////		return new ModelTableFirstFinder($this, $condition, $inputs);
//		$result = $this->createQuery()->where($condition, $inputs)->executeSelectFirst();
//		if ($result === null) return null;
//		else return $this->createModel($result, true);
//	}

//REM	/**
//	 * Starts a search of a single reccord in %%ModelTable%%
//	 * @param $condition
//	 * @param $inputs,...
//	 * @return Model
//	 * @ignore
//	 */
//	public abstract static function findFirstAssocWhere(QueryWhere $where, Model $parentModel);

	/**
	 * @return QueryWhere
	 */
	public abstract static function getExtraFindAssocWhere(QueryAliasable $aliasable, array $context = null);

	/**
	 * @return QueryWhere
	 */
	protected function _getExtraFindAssocWhere(QueryAliasable $aliasable, array $context = null) {
		// 2 be overriden
		return null;
	}

	public function addJoinWhere(QueryJoin $join) {
		// overridden
	}

//REM	/**
//	 * @return myModel
//	 */
//	protected function _findFirstAssocWhere(QueryWhere $where, Model $parentModel) {
//		return $this->findFirstWhere($this->addAssocWhere($where, $parentModel->params));
//	}
//
//	abstract public static function findAssocWhere(QueryWhere $where, Model $parentModel, $resultMode = ModelSet::RANDOM_ACCESS);
//
//	protected function _findAssocWhere(QueryWhere $where, Model $parentModel, $resultMode = ModelSet::RANDOM_ACCESS) {
//		return $this->findWhere($this->addAssocWhere($where), null, $resultMode);
//	}

	abstract public static function addAssocWhere(QueryWhere $where, QueryAliasable $aliasable);

	protected function _addAssocWhere(QueryWhere $where, QueryAliasable $aliasable, array $context = null) {
		if (null !== $extraWhere = $this->getExtraFindAssocWhere($aliasable, $context)) {
			$where = $aliasable->createWhere($where)->andWhere($extraWhere);
//			dumpl(array(
//				'extraWhere' => $extraWhere->buildSql($a = array()),
//				'where' => $where->buildSql($a)
//			));
		}
		return $where;
	}

	/**
	 *
	 * @param String $col
	 * @param mixed $value
	 * @return %%Model%%
	 */
	protected function _findFirstBy($col, $value) {

		if (!array_key_exists($col, $this->cols)) {
			throw new IllegalArgumentException();
		}

		return $this->createModel(
				$this->createQuery()->where($col . ' = ?', $value)->executeSelectFirst(),
				true);
	}

	/**
	 * @param QueryWhere $where
	 * @param array $context
	 * @return Model
	 */
	abstract public static function findFirst(QueryWhere $where=null, array $context = array(), $aliasingCallback = null);
	/**
	 * @param QueryWhere $where
	 * @param array $context
	 * @return %%Model%%
	 */
	protected function _findFirst(QueryWhere $where=null, array $context = array(), $aliasingCallback = null) {
		$query = $this->createQuery($context);
		if ($aliasingCallback !== null) $where = call_user_func_array(
			$aliasingCallback, 
			array(&$where, $query)
		);
		if (null !== $data = $query->where($where)->executeSelectFirst()) {
			return $this->createModel($data, true, $context);
		} else {
			return null;
		}
	}

	/**
	 * @param array $context
	 * @return Model
	 */
	abstract public static function findFirstWhere($condition = null, $inputs = null,
			array $context = array(), $aliasingCallback = null);
	/**
	 * @param array $context
	 * @return %%Model%%
	 */
	protected function _findFirstWhere($condition = null, $inputs = null,
			array $context = array(), $aliasingCallback = null) {
		
		if (null !== $data = $this->createFindOneQuery(
				$condition, $inputs, $context, $aliasingCallback)->executeSelectFirst()
		) {
			return $this->createModel($data, true, $context);
		} else {
			return null;
		}
	}

	/**
	 * @return ModelTableQuery
	 */
	private function createFindOneQuery($condition, $inputs, $context, $aliasingCallback) {
		
		$query = $this->createQuery($context);
		$where = $query->createWhere($condition, $inputs);
		if ($aliasingCallback !== null) {
			$where = call_user_func_array(
				$aliasingCallback,
				array(&$where, $query, $context)
			);
		}
		
		return $query->where($where);
	}

	/**
	 * Find the Model corresponding the given condition, when only one result
	 * is expected. If no corresponding model is found, NULL is returned. But, if
	 * more than one is found, an Exception is thrown.
	 * @return Model
	 */
	abstract public static function findOneWhere($condition = null, $inputs = null,
			array $context = array(), $aliasingCallback = null);
	
	/**
	 * Find the Model corresponding the given condition, when only one result
	 * is expected. If no corresponding model is found, NULL is returned. But, if
	 * more than one is found, an Exception is thrown.
	 * @return %%Model%%
	 */
	protected function _findOneWhere($condition = null, $inputs = null,
			array $context = array(), $aliasingCallback = null) {
		
		$data = $this->createFindOneQuery(
			$condition, $inputs, $context, $aliasingCallback
		)->executeSelect();
		
		if (count($data) === 1) {
			return $this->createModel($data[0], true, $context);
		} else if (1 < $n = count($data)) {
			$condition = StringHelper::replaceSuccessively('?', $inputs, $condition);
			throw new IllegalStateException(<<<EX
Data corruption: there should be only 1 ($n found) $this->modelName 
for condition: $condition.
EX
			);
		} else {
			return null;
		}
	}

//REM	/**
//	 * @return Model
//	 * @ignore
//	 */
//	abstract public static function findFirstWhere($condition, $inputs = null);

//REM	/**
//	 * Execute a search of a single reccord in %%ModelTable%%, and returns
//	 * the result as a %%Model%%
//	 * @param $condition
//	 * @param $inputs,...
//	 * @return %%Model%%
//	 * @see QueryWhere::where() for the syntax of a search
//	 * @ignore
//	 */
//	protected function _findFirstWhere($condition, $inputs = null, array $params = array()) {
////		if (func_num_args() > 2) $inputs = array_splice(func_get_args(), 1);
//		$data = $this->createQuery($params)->where($condition, $inputs)->executeSelectFirst();
//		if ($data === null) return null;
//		else return $this->createModel($data, true);
//	}

	/**
	 * @return ModelTableFinder
	 * @ignore
	 */
	abstract public static function find($condition = null, $inputs = null);

	/**
	 * @return ModelSet
	 * @ignore
	 */
	abstract public static function findWhere(
		$condition = null, $inputs = null,
		$mode = ModelSet::ONE_PASS,
		array $context = array(),
		$aliasingCallback = null,
		ModelRelationReciproqueFactory $reciproqueFactory = null
	);
	/**
	 * Execute a search in %%ModelTable%%, and returns the result as a ModelSet
	 *
	 * <b>Attention</b>: this method's syntax differs from the other find...
	 * methods; the $inputs argument must necessarily be given as an array!
	 *
	 * @param $condition
	 * @param array $inputs
	 * @param Const $mode one of the {@link ModelSet} format constants
	 * @param ModelRelation $existingRelation
	 *
	 * @return ModelSet
	 * @see QueryWhere::where() for the syntax of a search
	 * @ignore
	 */
	protected function _findWhere(
		$condition = null, $inputs = null,
		$mode = ModelSet::ONE_PASS,
		array $context = array(),
		$aliasingCallback = null,
		ModelRelationReciproqueFactory $reciproqueFactory = null
	) {
		$query = $this->createQuery($context)->select();
		$where = $query->createWhere($condition, $inputs);
		if ($aliasingCallback !== null) $where = call_user_func_array(
			$aliasingCallback, 
			array(&$where, $query)
		);
		$query->where($where);
		return ModelSet::create(
			$this,
			$query,
			$mode,
			$reciproqueFactory
		);
	}
	
	/**
	 * @return ModelSet
	 */
	abstract public static function findWherePkIn(
		array $ids, 
		$modelSet = ModelSet::ONE_PASS,
		array $context = array(),
		$aliasingCallback = null,
		ModelRelationReciproqueFactory $reciproqueFactory = null
	);
	protected function _findWherePkIn(
		array $ids, 
		$modelSet = ModelSet::ONE_PASS,
		array $context = array(),
		$aliasingCallback = null,
		ModelRelationReciproqueFactory $reciproqueFactory = null
	) {
		$query = $this->createQuery($context)->select()->whereIn($this->getPrimaryKeyName(), $ids);
		if ($aliasingCallback !== null) {
			$where = $query->createWhere();
			$where = call_user_func_array($aliasingCallback, 
				array(&$where, $query)
			);
			$query->where($where);
		}
		return ModelSet::create(
			$this,
			$query,
			$modelSet,
			$reciproqueFactory
		);
	}

	/**
	 * @return Model
	 * @ignore
	 */
	abstract static function findFirstByPrimaryKey($primaryKeyValue, array $context = array());

	/**
	 * @return Model
	 * @ignore
	 */
	abstract static function findByPrimaryKey($primaryKeyValue, array $context = array());

	public static function  __callStatic($name, $arguments) {
		if (substr($name, 0, 6) == 'findFirstBy') {
			return self::findFirstBy(substr($name, 0, 6), $arguments[0]);
		} else if (substr($name, 0, 6) == 'findBy') {
			return self::findBy(substr($name, 0, 6), $arguments[0]);
		}

		return ModelTable::getTable(get_called_class())->__call($name, $arguments);
	}

	public function __call($name, $arguments) {
		if (isset($this->pluginMethods[$name])) {
			return call_user_func_array($this->pluginMethods[$name], $arguments);
		}
		throw new SystemException('Method ' . $name . ' does not exist');
	}

	protected function addPlugin($plugin) {
		$this->plugins[get_class($plugin)] = $plugin;
		foreach (get_class_methods($plugin) as $method) {
			$this->pluginMethods[$method] = array($plugin, $method);
		}
	}

	public function getPlugin($class) {
		if (!$this->plugins || !isset($this->plugins[$class])) {
			throw new IllegalStateException("{$this->getTableName()} has no plugin $class");
		}
		return $this->plugins[$class];
	}
	
	abstract static public function createModelSet(ModelTableQuery $loadQuery,
			$modelSetMode = ModelSet::ONE_PASS,
			ModelRelationReciproqueFactory $reciproqueFactory = null);
	protected function _createModelSet(ModelTableQuery $loadQuery,
			$modelSetMode = ModelSet::ONE_PASS,
			ModelRelationReciproqueFactory $reciproqueFactory = null) {

		return ModelSet::create(
			$this, $loadQuery, $modelSetMode, $reciproqueFactory
		);
	}

	abstract public static function createWhereIs($fieldValues);
	protected function _createWhereIs($fieldValues, &$query = null) {

	}

	/**
	 * @return int the number of affected records
	 */
	abstract static public function deleteWhereIs($fieldValues);
	protected function _deleteWhereIs($fieldValues) {
		$query = $this->createQuery();
		// Create the where clause
		$where = $query->createWhere();
		foreach ($fieldValues as $field => $value) {
			$where->andWhere($query->getQualifiedName($field) . '=?', $value);
		}
		// Notify each refering model of the end of the relationship, in order
		// to trigger cleaning and post processing procedures
		foreach (
			$this->createModelSet($query->where($where), ModelSet::ONE_PASS)
			as $model
		) {
			$model->notifyDelete();
		}
		// Actually remove the data from the data store
		return $this->executeDelete($query);
	}

	abstract static public function deleteWhereIn($field, $values);
	protected function _deleteWhereIn($field, $values) {
		$query = $this->createLoadQuery(self::LOAD_NONE);
		// Create the where clause
//		$where = $query->createWhere()->whereIn($query->getQualifiedName($field), $values);
		$where = $query->createWhere()->whereIn($field, $values);
		// Notify each refering model of the end of the relationship, in order
		// to trigger cleaning and post processing procedures
		foreach (
			$this->createModelSet($query->where($where), ModelSet::ONE_PASS)
			as $model
		) {
			$model->notifyDelete();
		}
		// Actually remove the data from the data store
		return $this->executeDelete($query);
	}
	
	abstract static public function deleteWhereNotIn($field, $values);
	protected function _deleteWhereNotIn($field, $values) {
		$query = $this->createLoadQuery(self::LOAD_NONE);
		// Create the where clause
		$where = $query->createWhere()->whereNotIn($field, $values);
		// Notify each refering model of the end of the relationship, in order
		// to trigger cleaning and post processing procedures
		foreach (
			$this->createModelSet($query->where($where), ModelSet::ONE_PASS)
			as $model
		) {
			$model->notifyDelete();
		}
		// Actually remove the data from the data store
		return $this->executeDelete($query);
	}
	
	protected function executeDelete(ModelTableQuery $query) {
		return $query->executeDelete();
	}

	abstract public static function deleteWherePkIn($values);
	protected function _deleteWherePkIn($values) {
		return $this->deleteWhereIn($this->getPrimaryKeyName(), $values);
	}
	
	abstract public static function deleteWherePkNotIn($values);
	protected function _deleteWherePkNotIn($values) {
		return $this->deleteWhereNotIn($this->getPrimaryKeyName(), $values);
	}

	/**
	 * @param array $pointer an array containing a reference to the variable to
	 * be attached to.
	 * @return ModelTable
	 */
	public function attach(&$pointer) {
		return $pointer = $this;
	}

}  // <-- ModelTable

abstract class ModelSet implements Iterator {

	const RAW = -1;
	const ONE_PASS = 0;
	const RANDOM_ACCESS = 1;

	/**
	 *
	 * @param Query $query
	 * @param Const $mode
	 * @param ModelRelation $existingRelation
	 * @return ModelSet
	 */
	public static function create(
			ModelTableProxy $table,
			Query $query,
			$mode = ModelSet::ONE_PASS,
			ModelRelationReciproqueFactory $reciproqueFactory = null
		) {

		switch ($mode) {
			case ModelSet::ONE_PASS:
				return new OnePassModelSet($table, $query, $reciproqueFactory);
				break;
			case ModelSet::RANDOM_ACCESS:
				return new RandomAccessModelSet($table, $query, $reciproqueFactory);
				break;
			case ModelSet::RAW:
				return $query->executeSelect();
			default:
				throw new IllegalArgumentException('Unknown mode: ' . $mode);
		}
	}

	public static function createEmpty(ModelTableProxy $table, $mode = ModelSet::RANDOM_ACCESS) {
		switch ($mode) {
			case ModelSet::ONE_PASS:
//				return new OnePassModelSet($table, null, $reciproqueFactory);
				throw new IllegalArgumentException('There is absolutly no point in creating'
						. 'an empty one-pass model set...');
				break;
			case ModelSet::RANDOM_ACCESS:
				return new RandomAccessModelSet($table, null);
				break;
			case ModelSet::RAW:
				return array();
			default:
				throw new IllegalArgumentException('Unknown mode: ' . $mode);
		}
	}

	public function __toString() {
		$r = get_class($this) . '[';
		$empty = true;
		foreach ($this as $k => $v) {
			$empty = false;
			$r .= "\n\t$k => " . $v;
		}
		return $r . ($empty ? '' : "\n") . ']';
	}
}

class RandomAccessModelSet extends ModelSet implements ArrayAccess {

	/** @var array */
	protected $set;
	protected $context = null;

	public function __construct(ModelTableProxy $table, Query $query = null,
			ModelRelationReciproqueFactory $reciproqueFactory = null) {

		$this->set = array();

		if ($query !== null) {
			foreach ($query->executeSelect() as $results) {
				$this->set[] = $table->loadModelFromData($results, $query->context);
			}
			$this->context = $query->context;
		}

		if ($reciproqueFactory !== null) {
			foreach ($this->set as $model) {
				$reciproqueFactory->init($model);
			}
		}
	}

	public function groupBy($fieldName, $asc = true) {
		$r = array();
		foreach ($this as $model) {
			$r[$model->__get($fieldName)][] = $model;
		}

		if ($asc) ksort($r);
		else krsort($r);

		return $r;
	}

	public function groupByBoolean($fieldName, $asc = true) {
		$r = array();
		foreach ($this as $model) {
			$r[(bool) $model->__get($fieldName)][] = $model;
		}

		if ($asc) ksort($r);
		else krsort($r);

		return $r;
	}

	public function count() {
		return count($this->set);
	}

	public function size() {
		return count($this->set);
	}

	public function toArray() {
		return $this->set;
	}

	public function getModelsData() {
		$r = array();
		foreach ($this->set as $model) {
			$r[] = $model->getData();
		}
		return $r;
	}

	public function push(Model $model) {
		$this->set[] = $model;
		if ($this->context !== null) $model->setContextIf($this->context);
	}

	public function pop() {
		return array_pop($this->set);
	}

	/**
	 * @param mixed $value	the value of the primary key of the model to remove,
	 * or a Model from which the id wil be taken. If the Model is new (hence,
	 * has no id, an IllegalArgumentException will be thrown.
	 * @throws IllegalArgumentException
	 */
	public function removeById($value) {
		if ($value instanceof Model) {
			if ($value->isNew()) throw new IllegalArgumentException(
				'It is impossible to remove by id a new model'
			);
			$value = $value->getPrimaryKeyValue();
		}
		foreach ($this->set as $i => $m) {
			if ($m->getPrimaryKeyValue() === $value) {
				unset($this->set[$i]);
				return $m;
			}
		}
		return false;
	}

	public function offsetExists($offset) {
		return array_key_exists($offset, $this->set);
	}

	/**
	 *
	 * @param int $offset
	 * @return Model
	 */
	public function offsetGet($offset) {
		return $this->set[$offset];
	}

	public function offsetSet($offset, $value) {
		throw new UnsupportedOperationException('Read Only');
	}

	public function offsetUnset($offset) {
//		throw new UnsupportedOperationException('Read Only');
//		unset($this->set[$offset]);
		for ($i=$offset, $l=count($this->set)-1; $i<$l; $i++) {
			$this->set[$i] = $this->set[$i+1];
		}
		array_pop($this->set);
	}

	protected $i;

	public function key() {
		return $this->i;
	}

	public function next() {
		$this->i++;
	}

	public function rewind() {
		$this->i = 0;
	}

	public function valid() {
		return array_key_exists($this->i, $this->set);
	}

	/**
	 *
	 * @return Model
	 */
	public function current() {
		return $this->set[$this->i];
	}

}

class OnePassModelSet extends ModelSet {

	/** @var ModelTable */
	protected $table;

	/** @var Query */
	protected $query;
	protected $pdoStatement;

	protected $reciproqueFactory;

	private $i = null;
	private $current = null;

	public function __construct(ModelTableProxy $table, Query $query, ModelRelationReciproqueFactory $reciproqueFactory = null) {
		$this->query = $query;
		$table->attach($this->table);
		$this->reciproqueFactory = $reciproqueFactory;
	}

	protected $count = null;

	public function count() {
		if ($this->count !== null) return $this->count;
		else return $this->count = $this->query->executeCount();
	}

	public function toArray() {
		$r = array();
		foreach($this as $record) $r[] = $record;
		return $r;
	}

	/**
	 * 
	 * @return Model
	 */
	public function current() {
		$model = $this->table->loadModelFromData($this->current, $this->query->context);

		if ($this->reciproqueFactory !== null) {
			$this->reciproqueFactory->init($model);
		}

		return $model;
	}

	public function key() {
		return $this->i;
	}

	public function next() {
		$this->i++;
		$this->current = $this->pdoStatement->fetch(PDO::FETCH_ASSOC);
	}

	public function rewind() {
		if ($this->i === null) {
			$this->pdoStatement = $this->query->executeSelectRaw();
		} else {
			$this->pdoStatement = $this->query->reExecuteSelectRaw();
		}
		$this->i = 0;
		$this->current = $this->pdoStatement->fetch(PDO::FETCH_ASSOC);
	}

	public function valid() {
		return $this->current !== false;
	}

}

/**
 * @method ModelTableFinder where($condition, $inputs)
 * @method ModelTableFinder whereIn($field, $values)
 * @method ModelTableFinder whereNotIn($field, $values)
 * @method ModelTableFinder andWhere($condition, $inputs)
 * @method ModelTableFinder andWhereIn($field, $values)
 * @method ModelTableFinder andWhereNotIn($field, $values)
 * @method ModelTableFinder orWhere($condition, $inputs)
 * @method ModelTableFinder orWhereIn($field, $values)
 * @method ModelTableFinder orWhereNotIn($field, $values)
 */
class ModelTableFinder extends QueryWhere {

	/** @var ModelTable */
	private $table;
	/** @var ModelTableQuery */
	public $query;

	public function __construct(ModelTable $table, $condition = null, $inputs = null) {
		$this->table = $table;
		$this->query = $this->table->createQuery();
		parent::__construct($this->query, $condition, $inputs);
//		if ($condition !== null) $this->where($condition, $inputs);
	}

	public function getQuery() {
		$q = clone $this->query;
		$q->where($this)->select();
		return $q;
	}

	/**
	 * DEPRECATED DOC !!!
	 * Execute the search query and returns the result as a {@link ModelSet}
	 * @param Const $mode one of the {@link ModelSet} format constants
	 * @param ModelRelation $existingRelation
	 * @return ModelSet
	 */
	public function execute($mode = ModelSet::ONE_PASS) {
		return ModelSet::create($this->table, $this->query->andWhere($this), $mode);
	}

}

/**
 * @method ModelTableFirstFinder where($condition, $inputs)
 * @method ModelTableFirstFinder whereIn($field, $values)
 * @method ModelTableFirstFinder whereNotIn($field, $values)
 * @method ModelTableFirstFinder andWhere($condition, $inputs)
 * @method ModelTableFirstFinder andWhereIn($field, $values)
 * @method ModelTableFirstFinder andWhereNotIn($field, $values)
 * @method ModelTableFirstFinder orWhere($condition, $inputs)
 * @method ModelTableFirstFinder orWhereIn($field, $values)
 * @method ModelTableFirstFinder orWhereNotIn($field, $values)
 */
class ModelTableFirstFinder extends QueryWhere {

	/** @var ModelTable */
	private $table;

	public function __construct(ModelTable $table, $condition = null, $inputs = null) {
		$this->table = $table;
		if ($condition !== null) $this->where($condition, $inputs);
	}

	/**
	 *
	 * @return Query
	 */
	public function getQuery() {
		return $this->table->createQuery()->selectFirst()->where($this);
	}

	/**
	 * Execute the search query and returns the result as a {@link ModelSet}
	 * @return Model
	 */
	public function execute() {
		$result = $this->table->createQuery()->where($this)->executeSelectFirst();
		if ($result === null) return null;
		else return $this->table->createModel($result, true);
	}

	public function __toString() {
		return $this->table->createQuery()->selectFirst()->where($this)->__toString();
	}

}

/**
 * @var string $tableName
 * @var string $dbTableName
 * @var string $modelName
 */
abstract class ModelTableProxy {

	/**
	 * @return ModelTable
	 */
	public abstract static function getInstance();

	public abstract function attach(&$pointer);

	public abstract static function getTableName();

	public abstract static function getDBTableName();

	public abstract static function getModelName();
}

require_once __DIR__ . '/VirtualField.class.php';
