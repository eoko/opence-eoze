<?php
/**
 * @package PS-ORM-1
 * @author Éric Ortéga <eric@mail.com>
 * @copyright Copyright (c) 2010, Éric Ortéga
 * @license http://www.planysphere.fr/licenses/psopence.txt
 */

use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use eoko\config\ConfigManager;
use eoko\database\Database;
use eoko\cqlix\Model\Relation\Info\Factory as ModelRelationInfoFactory;

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
 * @method static ModelTable getInstance()
 * @method static string getDBTable()
 * @method static bool hasPrimaryKey()
 * @method static string getPrimaryKeyName()
 * @method static ModelColumn getPrimaryKeyColumn()
 * @method static Model createNewModel($initValues = null, $strict = false, array $context = null)
 *
 * %%ModelTable%% from its name. This method is static in concrete ModelTable
 * implementations, but it is abstract in ModelTable class.
 * @method static ModelColumn[] getColumns($excludeAutoOperation = false, $excludeFinal = false) This
 * method is static in concrete ModelTable implementations, but it is abstract
 * in ModelTable class.
 * 
 * @property string $modelName		name/class of the associated Model
 * @property string $tableName		name/class of this instance
 * @property string $dbTableName	name of the associated database table
 */
abstract class ModelTable extends ModelTableProxy implements EventManagerAwareInterface {

	/**
	 * Fired when a new model is created.
	 */
	const EVENT_MODEL_CREATED = 'modelCreated';

	/**
	 * @var EventManagerInterface
	 */
	private $events;

	/**
	 * @var array[string]ModelColumn
	 */
	protected $cols;

	/**
	 * @var array[ModelRelationInfo]
	 */
	private $relations;

	/**
	 * @var string[]|null
	 */
	protected $uniqueIndexes = null;

	/**
	 * @var VirtualField[]
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
	 * @param $relations
	 */
	protected function __construct(&$cols, &$relations) {

		$this->cols = $cols;
		$this->relations = $relations;

		$this->doConfigure();
		$this->preConfigure($this->cols, $this->relations, $this->virtuals);

		$this->configureBase($this->cols, $this->relations, $this->virtuals);

		$this->configure();

		$this->constructed = true;
	}

	protected function doConfigure() {}

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

	/**
	 * @inheritdoc
	 */
	public function setEventManager(EventManagerInterface $events) {
		$events->setIdentifiers(array(__CLASS__, get_called_class()));
		$this->events = $events;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getEventManager() {
		if (!$this->events) {
			$this->setEventManager(new EventManager());
		}
		return $this->events;
	}

	public function onModelCreate(Model $model) {
		$this->getEventManager()->trigger(self::EVENT_MODEL_CREATED, $this, array(
			'model' => $model,
		));
	}

	private $config;

	/**
	 * @return Database
	 */
	public function getDatabase() {
		return Database::getDefault();
	}

	protected function getConfig() {
		return $this->config;
	}

	protected function configureBase() {

		$this->config = ConfigManager::get(
			ConfigManager::get('eoze/application/namespace') . "/cqlix/models/$this->dbTableName"
		);

		foreach ($this->relations as $name => $relation) {
			/** @var ModelRelationInfo $relation */
			$relation->configureMeta(
				isset($this->config['relations'][$name])
					? $this->config['relations'][$name]
					: null
			);
		}

		foreach ($this->virtuals as $name => $virtual) {
			/** @var VirtualField $virtual */
			$virtual->configureMeta(
				isset($this->config['virtuals'][$name])
					? $this->config['virtuals'][$name]
					: null
			);
		}
	}

	protected function addVirtuals($virtuals) {
		if (func_num_args() > 1) {
			$virtuals = func_get_args();
		}
		foreach ($virtuals as $name => $virtual) {
			if (is_array($virtual)) {
				/** @noinspection PhpForeachNestedOuterKeyValueVariablesConflictInspection */
				foreach ($virtual as $name => $field) {
					if (is_string($name)) {
						$this->addVirtual($field, $name);
					} else {
						$this->addVirtual($field);
					}
				}
			} else if (is_string($name)) {
				$this->addVirtual($virtual, $name);
			} else {
				$this->addVirtual($virtual);
			}
		}
	}

	/**
	 * Gets the virtual field factory used for creating virtual fields from spec string.
	 *
	 * @return \eoko\cqlix\VirtualField\SpecFactory
	 */
	private function getVirtualFactory() {
		return \eoko\cqlix\VirtualField\SpecFactory::getDefault();
	}

	/**
	 * @param VirtualField|string $virtual
	 * @param string|null $name
	 * @throws IllegalStateException
	 * @throws InvalidArgumentException
	 */
	protected function addVirtual($virtual, $name = null) {
		if ($this->constructed) {
			throw new IllegalStateException('This operation is only allowed during initialization');
		}
		if (is_string($virtual)) {
			$virtual = $this->getVirtualFactory()->create($this, $virtual, $name);
		}
		if ($virtual instanceof VirtualField) {
			if ($name === null) {
				$name = $virtual->getName();
			}
			$this->virtuals[$name] = $virtual;
		} else {
			throw new InvalidArgumentException();
		}
	}

	/**
	 * Adds a relation to this table.
	 *
	 * This method must be called in {@link doConfigure()} or {@link preConfigure()}.
	 *
	 * @param string|array|ModelRelationInfo $relation
	 * @return ModelRelationInfo
	 * @throws IllegalStateException
	 * @throws InvalidArgumentException
	 * @throws UnsupportedOperationException
	 */
	protected function addRelation($relation) {

		if ($this->constructed) {
			throw new IllegalStateException('This operation is only allowed during initialization');
		}

		if ($relation instanceof ModelRelationInfo) {
			return $this->addRelationInfo($relation);
		} else if (is_string($relation)) {
			return $this->addRelationInfo(ModelRelationInfoFactory::fromSpec($this, $relation));
		} else if (is_array($relation)) {
			throw new UnsupportedOperationException('TODO');
		} else {
			throw new InvalidArgumentException();
		}
	}

	private function addRelationInfo(ModelRelationInfo $relation) {
		$this->relations[$relation->getName()] = $relation;
		return $relation;
	}

	/**
	 * Returns `true` if the underlying database engine automatically performs
	 * ON_DELETE and ON_UPDATE actions.
	 * @return bool
	 */
	public function isAutomaticCascadeEngine() {
		/** @noinspection PhpUndefinedFieldInspection */
		return $this->engineAutomaticCascade;
	}

	/**
	 * Creates a new Model.
	 *
	 * The new record will be considered new until its primary key is set. This
	 * default behaviour can be controlled with the {@link ModelTable::forceNew()
	 * forceNew()} method (if you intend to use forceNew() just after the call
	 * of the present method, both calls can be combined by using the {@link
	 * createNewModel()} method).
	 *
	 * An array of values can be given to initialize the record's fields. It
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
	 * @param array $context     see {@link createModel()}
	 * 
	 * @return Model
	 */
	public static function createModel($initValues = null, $strict = false, array $context = null) {
		/** @var $modelClass Model */
		$modelClass = static::getModelClass();
		return $modelClass::create($initValues, $strict, $context);
	}

	/**
	 * Creates a new Model instance (see {@link createModel()}), and set the
	 * forceNew flag to TRUE.
	 *
	 * @param array $initValues see {@link createModel()}
	 * @param boolean $strict   see {@link createModel()}
	 * @param array $context     see {@link createModel()}
	 * @return \Model
	 */
	protected function _createNewModel($initValues = null, $strict = false, array $context = null) {
		return $this->createModel($initValues, $strict, $context)->forceNew();
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
	 * @param array $context
	 * 
	 * @return Model
	 */
	abstract static function loadModel($primaryKeyValue, array $context = null);

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
	 * @param array $context
	 * 
	 * @return Model
	 */
	abstract static function loadModelFromData(array $data, array $context = null);

	protected function _getDBTableName() {
		return $this->getDBTable();
	}

	/**
	 *
	 * @param string $colName
	 * @return ModelColumn
	 */
	abstract static public function getColumn($colName);

	/**
	 * Get whether the given object is an instance of this table's model.
	 *
	 * @param $obj
	 * @return Bool TRUE if $obj is an instance of <?php echo $modelName ?>
	 */
	abstract static public function isInstanceOfModel($obj);

	/**
	 * @param string $modelName
	 * @return ModelTable
	 */
	static function getModelTable($modelName) {
		return call_user_func(array($modelName, 'getTable'));
	}

	/**
	 * @param string|ModelTable|null $tableName
	 * @return ModelTable
	 */
	public static function getTable($tableName = null) {
		if ($tableName === null) {
			return self::getInstance();
		} else if ($tableName instanceof ModelTable) {
			return $tableName;
		} else {
			return call_user_func(array($tableName, 'getInstance'));
		}
	}

	/**
	 * Creates a new Query based on this table.
	 *
	 * @param array $context
	 * @return ModelTableQuery
	 */
	public static function createQuery(array $context = null) {
		return static::getInstance()->onCreateQuery($context);
	}

	/**
	 * Name of the database {@link \eoko\database\Database::registerProxy() named proxy} to
	 * use.
	 *
	 * @var string|null
	 */
	protected $databaseProxyName = null;

	/**
	 * @param array $context
	 * @return Query
	 */
	private function onCreateQuery(array $context = null) {
		$query = $this->doCreateQuery($context);

		if (isset($this->databaseProxyName)) {
			$pdo = Database::getProxy($this->databaseProxyName)->getConnection();
			$query->setConnection($pdo);
		}

		return $query;
	}

	/**
	 * @param array $context
	 * @return Query
	 */
	protected abstract function doCreateQuery(array $context = null);

	/**
	 * Gets the default controller for CRUD operation on this table's model.
	 * This information is used by UI generator (like cqlix form generator),
	 * for example to create foreign combo fields.
	 * @return string
	 */
	public function getDefaultController() {
		return isset($this->config['defaultController'])
				? $this->config['defaultController']
				: null;
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
	 * @param string $name
	 * @return Bool
	 */
	protected function _hasColumn($name) {
		return array_key_exists($name, $this->cols);
	}

	// TODO field/col disambiguation
	/**
	 * Get a ModelTableColumn of %%ModelTable%% from its name.
	 *
	 * @param string $name
	 * @param bool $require
	 * @throws \IllegalStateException
	 * @return ModelColumn the column matching the given field name, or NULL if
	 * this Model have no field matching this name
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

	/**
	 * Returns `true` if the table has a field selectable as its display
	 * name (that is, the name that should be displayed to the user).
	 * 
	 * Overrides and return `false` if the table has a field that would 
	 * be automatically selected as the display name by the
	 * {@link getNameFieldName()} method.
	 * 
	 * @return bool `true` if the table has a field selectable as its
	 * display name.
	 */
	abstract public static function hasName();

	protected function _hasName() {
		return !!$this->getNameFieldName(false);
	}

	/**
	 * Get the name of the field that is considered to be the display name
	 * of the record (that is, the one that can be recognized by the user
	 * in order to identify the record).
	 * 
	 * This method will automatically detect the following fields (in this
	 * order of precedence), if they are present in the table schema:
	 * 
	 * - label
	 * - displayName
	 * - name
	 * 
	 * Any type of field that has this name will be selected by this method 
	 * (that is, an actual column, a virtual field, or other).
	 * 
	 * If you don't want a field with such a name to be considered as the
	 * display name, overrides this method and/or {@link hasName()}.
	 * 
	 * @param bool $require If `true`, the method will throw an
	 * {@link IllegalStateException} if it cannot find a display name.
	 * 
	 * @return string
	 */
	abstract public static function getNameFieldName($require = true);

	protected function _getNameFieldName($require = true) {
		foreach (array(
			'label', 'displayName', 'name'
		) as $field) {
			if ($this->hasField($field)) {
				return $field;
			}
		}
		if ($require) {
			throw new IllegalStateException("Table $this->tableName has no display name");
		} else {
			return null;
		}
//		if ($this->hasColumn('label')) return 'label';
//		else if ($this->hasColumn('displayName')) return 'displayName';
//		else if ($this->hasColumn('name')) return 'name';
//		else if ($this->hasColumn('nom')) return 'nom';
//		else if ($require) throw new IllegalStateException();
//		else return null;
	}

	/**
	 * Creates a Query with its WHERE claused configured to match only
	 * 
	 * @param array $context
	 * @return \ModelTableQuery 
	 */
	abstract public static function createReadQuery(array $context = null);

	protected function _createReadQuery(array $context = null) {

		$query = $this->createQuery($context);

		return $query;
	}

	const LOAD_NONE   = 0;
	const LOAD_NAME   = 1;
	const LOAD_ID     = 2;
	const LOAD_FIELD  = 3;
	const LOAD_FULL   = 3;

	/**
	 * @param const $relationsMode
	 * @param array $context
	 * @param array $columns
	 * @return \ModelTableQuery
	 */
	abstract public static function createLoadQuery($relationsMode = ModelTable::LOAD_NAME, 
			array $context = null, $columns = null);
	/**
	 * @return \ModelTableQuery
	 */
	protected function _createLoadQuery($relationsMode = ModelTable::LOAD_NAME, 
			array $context = null, $columns = null) {

		$query = $this->createReadQuery($context);

		// Makes it a hash... for speed!
		if ($columns) {
			$columns = array_flip($columns);
		}

		foreach ($this->getColumns() as $col) {
			if ($columns === null || isset($columns[$col->getName()]) || $col->isPrimary()) {
				$col->select($query);
			}
		}

		$this->applyLoadQueryDefaultOrder($query);

		$this->selectLoadQueryVirtuals($query, $columns);

		if (is_array($relationsMode)) {
			foreach ($relationsMode as $mode => $values) {
				switch ($mode) {
					case self::LOAD_NAME:
						foreach ($values as $relation) {
							if ($columns === null || isset($columns[$relation])) {
								$this->getRelationInfo($relation)->selectName($query);
							}
						}
						break;
					case self::LOAD_ID:
						foreach ($values as $relation) {
							if ($columns === null || isset($columns[$relation])) {
								$this->getRelationInfo($relation)->selectId($query);
							}
						}
						break;
					case self::LOAD_FULL:
						foreach ($values as $relation => $fields) {
							if ($columns !== null) {
								$fields = array_intersect_key($fields, $columns);
							}
							if ($fields) {
								$this->getRelationInfo($relation)->selectFields($query, $fields);
							}
						}
						break;
				}
			}
		} else {
			switch ($relationsMode) {
				case ModelTable::LOAD_NAME:
					foreach ($this->relations as $relation) {
						if ($columns === null || isset($columns[$relation])) {
							$relation->selectName($query);
						}
					}
					break;
				case ModelTable::LOAD_ID:
					foreach ($this->relations as $relation) {
						if ($columns === null || isset($columns[$relation])) {
							$relation->selectId($query);
						}
					}
					break;
				case ModelTable::LOAD_NONE: break;
				case ModelTable::LOAD_FULL: throw new UnsupportedOperationException();
				default:
					throw new IllegalArgumentException("Invalid \$relationMode: $relationsMode");
			}
		}

		return $query;
	}

	protected function selectLoadQueryVirtuals(\ModelTableQuery $query, $columns) {
		foreach ($this->virtuals as $virtual) {
			if ($columns === null || isset($columns[$virtual->getName()])) {
				if ($virtual->isSelectable($query)) {
					$virtual->select($query);
				}
			}
		}
	}

	protected function applyLoadQueryDefaultOrder(Query $query) {}

	/**
	 * Gets the relation info with the specified name. As opposed to {@link getRelationInfo()},
	 * this method won't try to expand chained relations, so it will be usable before the relation
	 * graph has been constructed.
	 *
	 * @param string $name
	 * @param bool $require
	 * @return ModelRelationInfo
	 * @throws IllegalStateException
	 */
	public function getRelationInfoDeclaration($name, $require = false) {
		if (isset($this->relations[$name])) {
			return $this->relations[$name];
		} else {
			if ($require) {
				throw new IllegalStateException('No relation info declared with name: ' . $name);
			} else {
				return null;
			}
		}
	}

	/**
	 * @param string $name
	 * @param bool $requireType
	 * @throws \IllegalStateException
	 * @throws \IllegalArgumentException
	 * @return \ModelRelationInfo
	 */
	public abstract static function getRelationInfo($name, $requireType = false);

	/**
	 *
	 * @param string $name
	 * @param bool $requireType
	 * @throws \IllegalStateException
	 * @throws \IllegalArgumentException
	 * @return \ModelRelationInfo
	 */
	protected function _getRelationInfo($name, $requireType = false) {

		if (count($parts = explode('->', $name, 2)) == 2) {
			if (!isset($this->relations[$parts[0]])) {
				throw new IllegalArgumentException(
					get_class($this) . ' has no relation ' . $parts[0]
				);
			}
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
				if ($relation instanceof ModelRelationInfoHasMany) {
					return $relation;
				}
			} else if ($requireType === ModelRelation::HAS_ONE) {
				if ($relation instanceof ModelRelationInfoHasOne) {
					return $relation;
				}
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

	/**
	 * @return ModelRelationInfo[]
	 */
	abstract public static function getRelationsInfo();

	abstract public static function getRelationNames();
	protected function _getRelationNames() {
		return array_keys($this->getRelationsInfo());
	}

	/**
	 * Result cache for method {@link getFieldRelationInfo()}.
	 *
	 * @var array
	 */
	private $relationInfoByFieldCache = null;

	/**
	 * Get the relation info for the given field in this table. This only works for *referred* relations,
	 * that is if the reference field is owned by this table.
	 *
	 * @param string|ModelField $field
	 * @param bool $require
	 * @return ModelRelationInfoHasReference|null
	 * @throws IllegalStateException
	 */
	public function getFieldRelationInfo($field, $require = false) {

		$fieldName = $field instanceof ModelField
			? $field->getName()
			: $field;

		if (!isset($this->relationInfoByFieldCache[$fieldName])) {
			$this->relationInfoByFieldCache[$fieldName] = $this->discoverFieldRelationInfo($fieldName);
		}

		$relationInfo = $this->relationInfoByFieldCache[$fieldName];

		if ($relationInfo === false) {
			if ($require) {
				$tableName = $this->getModelName();
				throw new IllegalStateException("Cannot find relation for field: $tableName.$fieldName");
			} else {
				return null;
			}
		} else {
			return $relationInfo;
		}
	}

	private function discoverFieldRelationInfo($fieldName) {

		foreach ($this->getRelationsInfo() as $relationInfo) {
			if ($relationInfo instanceof ModelRelationInfoHasReference) {
				if ($relationInfo->getReferenceFieldName() === $fieldName) {
					return $relationInfo;
				}
			}
		}

		return false;
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
	 * @param string $name
	 * @return VirtualField
	 */
	abstract public static function getVirtual($name);

	/**
	 * @param string $name
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

	public function isUniqueBy($fields) {
		if (!is_array($fields)) {
			$fields = func_get_args();
		}

		$n = count($fields);

		foreach ($this->uniqueIndexes as $indexFields) {
			if (count($indexFields) === $n) {
				foreach ($fields as $field) {
					if (array_search($field, $indexFields, true) === false) {
						continue 2;
					}
				}
				return true;
			}
		}

		return false;
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

// REM 2012-12-11
//	public static function proxy(&$tableVar, $tableName, $dbTableName = null, $modelName = null) {
//		if ($dbTableName === null) {
//			return new ModelTableProxy($tableVar, $tableName);
//		} else {
//			return new ModelTableProxyEx($tableVar, $tableName, $dbTableName, $modelName);
//		}
//	}

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
	 * @param $inputs
	 * @param array $context
	 * @return ModelTableFinder
	 * @see QueryWhere for the syntax of a search
	 * @ignore
	 */
	protected function _find($condition = null, $inputs = null, array $context = null) {
		// 2013-03-21 Deprecated multiple inputs args
		// if (func_num_args() > 2) $inputs = array_splice(func_get_args(), 1);
		return new ModelTableFinder($this, $condition, $inputs, $context);
	}

	/**
	 *
	 * @param string $col
	 * @param mixed $value
	 * @param int $mode
	 * @throws IllegalArgumentException
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

	public function addJoinWhere(QueryJoin $join) {
		$where = $join->createWhere();
		$this->addAssocWhere($where, $join);
		if (!$where->isNull()) {
			$join->andWhere($where);
		}
	}

	public function addAssocWhere(QueryWhere $where, QueryAliasable $aliasable) {}

	/**
	 *
	 * @param string $col
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
	abstract public static function findFirst(QueryWhere $where = null, array $context = null, 
			$aliasingCallback = null);

	/**
	 * @param \QueryWhere $where
	 * @param array $context
	 * @param callback $aliasingCallback
	 * @return \Model|null %%Model%%
	 */
	protected function _findFirst(\QueryWhere $where = null, array $context = null,
			$aliasingCallback = null) {

		$query = $this->createQuery($context);

		if ($aliasingCallback !== null) {
			call_user_func($aliasingCallback, $where, $query);
// REM 13/12/11 04:28
//			$where = call_user_func_array(
//				$aliasingCallback, 
//				array(&$where, $query)
//			);
		}
		if (null !== $data = $query->andWhere($where)->executeSelectFirst()) {
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
			array $context = null, $aliasingCallback = null);
	/**
	 * @param array $context
	 * @return %%Model%%
	 */
	protected function _findFirstWhere($condition = null, $inputs = null,
			array $context = null, $aliasingCallback = null) {

		$data = $this->createFindOneQuery($condition, $inputs, $context, $aliasingCallback)
				->executeSelectFirst();

		if ($data !== null) {
			return $this->createModel($data, true, $context);
		} else {
			return null;
		}
	}

	/**
	 * @return \ModelTableQuery
	 */
	private function createFindOneQuery($condition, $inputs, $context, $aliasingCallback) {

		$query = $this->createQuery($context);
		$where = $query->createWhere($condition, $inputs);
		if ($aliasingCallback !== null) {
			call_user_func($aliasingCallback, $where, $query);
		}

		return $query->andWhere($where);
	}

	/**
	 * Find the Model corresponding the given condition, when only one result
	 * is expected. If no corresponding model is found, NULL is returned. But, if
	 * more than one is found, an Exception is thrown.
	 * @return Model
	 */
	abstract public static function findOneWhere($condition = null, $inputs = null,
			array $context = null, $aliasingCallback = null);

	/**
	 * Find the Model corresponding the given condition, when only one result
	 * is expected. If no corresponding model is found, NULL is returned. But, if
	 * more than one is found, an Exception is thrown.
	 * @return %%Model%%
	 */
	protected function _findOneWhere($condition = null, $inputs = null,
			array $context = null, $aliasingCallback = null) {

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
	 * @param string $condition
	 * @param array $inputs
	 * @param array $context
	 * @return ModelTableFinder
	 * @ignore
	 */
	abstract public static function find($condition = null, $inputs = null, array $context = null);

	/**
	 * @param string $condition
	 * @param array $inputs
	 * @param int $mode
	 * @param array $context
	 * @param callback $aliasingCallback
	 * @param ModelRelationReciproqueFactory $reciproqueFactory
	 * @return ModelSet
	 * @ignore
	 */
	abstract public static function findWhere(
		$condition = null, $inputs = null,
		$mode = ModelSet::ONE_PASS,
		array $context = null,
		$aliasingCallback = null,
		ModelRelationReciproqueFactory $reciproqueFactory = null
	);

	/**
	 * Execute a search in %%ModelTable%%, and returns the result as a ModelSet
	 *
	 * <b>Attention</b>: this method's syntax differs from the other find...
	 * methods; the $inputs argument must necessarily be given as an array!
	 *
	 * @param string $condition
	 * @param array $inputs
	 * @param int $mode one of the {@link ModelSet} format constants
	 * @param array $context
	 * @param callback $aliasingCallback
	 * @param ModelRelationReciproqueFactory $reciproqueFactory
	 *
	 * @return ModelSet
	 * @see QueryWhere::where() for the syntax of a search
	 * @ignore
	 */
	protected function _findWhere(
		$condition = null, $inputs = null,
		$mode = ModelSet::ONE_PASS,
		array $context = null,
		$aliasingCallback = null,
		ModelRelationReciproqueFactory $reciproqueFactory = null
	) {
		$query = $this->createQuery($context)->select();
		$where = $query->createWhere($condition, $inputs);
		if ($aliasingCallback !== null) {
			call_user_func($aliasingCallback, $where, $query);
		}
		if (!$where->isNull()) {
			$query->andWhere($where);
		}
		return ModelSet::create(
			$this,
			$query,
			$mode,
			$reciproqueFactory
		);
	}

	/**
	 * @param array $ids
	 * @param int $modelSet
	 * @param array $context
	 * @param callback $aliasingCallback
	 * @param ModelRelationReciproqueFactory $reciproqueFactory
	 * @return ModelSet
	 */
	abstract public static function findWherePkIn(
		array $ids, 
		$modelSet = ModelSet::ONE_PASS,
		array $context = null,
		$aliasingCallback = null,
		ModelRelationReciproqueFactory $reciproqueFactory = null
	);
	protected function _findWherePkIn(
		array $ids, 
		$modelSet = ModelSet::ONE_PASS,
		array $context = null,
		$aliasingCallback = null,
		ModelRelationReciproqueFactory $reciproqueFactory = null
	) {
		$query = $this->createQuery($context)->select()->whereIn($this->getPrimaryKeyName(), $ids);
		if ($aliasingCallback !== null) {
			$where = $query->createWhere();
			call_user_func($aliasingCallback, $where, $query);
			$query->andWhere($where);
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
	abstract static function findFirstByPrimaryKey($primaryKeyValue, array $context = null);

	/**
	 * @return Model
	 * @ignore
	 */
	abstract static function findByPrimaryKey($primaryKeyValue, array $context = null);

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

	/**
	 * Creates a ModelSet for the given Query.
	 *
	 * @param ModelTableQuery $loadQuery
	 * @param int $modelSetMode
	 * @param ModelRelationReciproqueFactory $reciproqueFactory
	 * @return ModelSet
	 */
	abstract static public function createModelSet(ModelTableQuery $loadQuery,
			$modelSetMode = ModelSet::ONE_PASS,
			ModelRelationReciproqueFactory $reciproqueFactory = null);
	/**
	 * Creates a ModelSet for the given Query.
	 *
	 * @param ModelTableQuery $loadQuery
	 * @param int $modelSetMode
	 * @param ModelRelationReciproqueFactory $reciproqueFactory
	 * @return ModelSet
	 */
	protected function _createModelSet(ModelTableQuery $loadQuery,
			$modelSetMode = ModelSet::ONE_PASS,
			ModelRelationReciproqueFactory $reciproqueFactory = null) {

		return ModelSet::create(
			$this, $loadQuery, $modelSetMode, $reciproqueFactory
		);
	}

	/**
	 * @return int the number of affected records
	 */
	abstract static public function deleteWhereIs($fieldValues);
	protected function _deleteWhereIs($fieldValues, array $context = null) {
		$query = $this->createQuery($context);
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

	abstract static public function deleteWhereIn($field, $values, $context = null);
	protected function _deleteWhereIn($field, $values, $context = null) {

		// 12/12/11 22:45 changed to createQuery, to bypass context
//		$query = $this->createLoadQuery(self::LOAD_NONE);
		$query = $this->createQuery($context);

		// Create the where clause
//		$where = $query->createWhere()->whereIn($query->getQualifiedName($field), $values);
		$where = $query->createWhere()->whereIn($field, $values);
		// Notify each refering model of the end of the relationship, in order
		// to trigger cleaning and post processing procedures
		foreach (
			$this->createModelSet($query->where($where), ModelSet::ONE_PASS)
			as $model
		) {
			/** @var $model Model */
			$model->notifyDelete();
		}
		// Actually remove the data from the data store
		return $this->executeDelete($query);
	}

	abstract static public function deleteWhereNotIn($field, $values, $context = null);
	protected function _deleteWhereNotIn($field, $values, $context = null) {

		// 12/12/11 22:45 changed to createQuery, to bypass context
//		$query = $this->createLoadQuery(self::LOAD_NONE);
		$query = $this->createQuery($context);

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

	abstract public static function deleteWherePkIn($values, $context = null);
	protected function _deleteWherePkIn($values, $context = null) {
		return $this->deleteWhereIn($this->getPrimaryKeyName(), $values, $context);
	}

	abstract public static function deleteWherePkNotIn($values, $context = null);
	protected function _deleteWherePkNotIn($values, $context = null) {
		return $this->deleteWhereNotIn($this->getPrimaryKeyName(), $values, $context);
	}

	/**
	 * @param $pointer
	 * @return ModelTable
	 */
	public function attach(&$pointer) {
		return $pointer = $this;
	}

}  // <-- ModelTable

// WTF?
require_once __DIR__ . '/VirtualField.php';
