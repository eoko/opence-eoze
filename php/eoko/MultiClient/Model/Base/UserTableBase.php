<?php

namespace eoko\MultiClient\Model\Base;

use ModelSet;
use ModelColumn;


/**
 * Base of the User Table.
 *
 * @category Eoze
 * @package Model
 * @subpackage Base
 *
 *
 * @method mixed findById($id)
 * @method mixed findFirstById($id)
 * @method mixed findByClientId($clientId)
 * @method mixed findFirstByClientId($clientId)
 * @method mixed findByUsername($username)
 * @method mixed findFirstByUsername($username)
 * @method mixed findByPassword($password)
 * @method mixed findFirstByPassword($password)
 * @method mixed findByLevel($level)
 * @method mixed findFirstByLevel($level)
 * @method mixed findByFirstName($firstName)
 * @method mixed findFirstByFirstName($firstName)
 * @method mixed findByLastName($lastName)
 * @method mixed findFirstByLastName($lastName)
 * @method mixed findByEmail($email)
 * @method mixed findFirstByEmail($email)
 * @method mixed findByComment($comment)
 * @method mixed findFirstByComment($comment)
 * @method mixed findByPhone($phone)
 * @method mixed findFirstByPhone($phone)
 * @method mixed findByDateAdd($dateAdd)
 * @method mixed findFirstByDateAdd($dateAdd)
 * @method mixed findByDateMod($dateMod)
 * @method mixed findFirstByDateMod($dateMod)
 * @method mixed findByUserMod($userMod)
 * @method mixed findFirstByUserMod($userMod)
 * @method mixed findByDisabled($disabled)
 * @method mixed findFirstByDisabled($disabled)
 * @method mixed findByEndUse($endUse)
 * @method mixed findFirstByEndUse($endUse)
 * @method mixed findByDeleted($deleted)
 * @method mixed findFirstByDeleted($deleted)
 * @method mixed findByRole($role)
 * @method mixed findFirstByRole($role)
 */
abstract class UserTableBase extends \myModelTable {

	private static $singleton = null;

	protected $databaseProxyName = 'eoko\MultiClient\MultiClient';

	public $modelName = 'User';
	public $tableName = 'UserTable';
	public $dbTableName = 'users';

	protected $engineAutomaticCascade = true;

	protected function __construct() {
		$cols = array(
				'id' => new ModelColumn('id', ModelColumn::T_INT, '10', false, null, true, null, true, true, null),
				'client_id' => new ModelColumn('client_id', ModelColumn::T_INT, '10', false, null, false, \eoko\MultiClient\Model\Proxy\ClientTableProxy::get(), false, false, null),
				'username' => new ModelColumn('username', ModelColumn::T_STRING, '255', false, null, true, null, false, false, null),
				'password' => new ModelColumn('password', ModelColumn::T_STRING, '255', false, null, false, null, false, false, null),
				'level' => new ModelColumn('level', ModelColumn::T_INT, '11', false, null, false, null, false, false, null),
				'first_name' => new ModelColumn('first_name', ModelColumn::T_STRING, '255', true, null, false, null, false, false, null),
				'last_name' => new ModelColumn('last_name', ModelColumn::T_STRING, '255', true, null, false, null, false, false, null),
				'email' => new ModelColumn('email', ModelColumn::T_STRING, '255', true, null, false, null, false, false, null),
				'comment' => new ModelColumn('comment', ModelColumn::T_TEXT, NULL, true, null, false, null, false, false, null),
				'phone' => new ModelColumn('phone', ModelColumn::T_STRING, '64', true, null, false, null, false, false, null),
				'date_add' => new ModelColumn('date_add', ModelColumn::T_DATE, NULL, false, null, false, null, false, false, null),
				'date_mod' => new ModelColumn('date_mod', ModelColumn::T_DATE, NULL, false, null, false, null, false, false, null),
				'user_mod' => new ModelColumn('user_mod', ModelColumn::T_STRING, '255', false, null, false, null, false, false, null),
				'disabled' => new ModelColumn('disabled', ModelColumn::T_INT, '4', false, 1, false, null, false, false, null),
				'end_use' => new ModelColumn('end_use', ModelColumn::T_DATE, NULL, false, null, false, null, false, false, null),
				'deleted' => new ModelColumn('deleted', ModelColumn::T_INT, '4', false, 0, false, null, false, false, null),
				'role' => new ModelColumn('role', ModelColumn::T_STRING, '255', false, null, false, null, false, false, null)
		);

		$relations = array(
				'Client' => new \ModelRelationInfoReferencesOne(array(
					'name' => 'Client',
				), $this, \eoko\MultiClient\Model\Proxy\ClientTableProxy::get(), 'client_id')
		);

		parent::__construct($cols, $relations);
	}

	/**
	 * @return \eoko\MultiClient\Model\UserTable
	 */
	public static function getInstance() {
		if (self::$singleton == null) {
			self::$singleton = new \eoko\MultiClient\Model\UserTable();
		}
		return self::$singleton;
	}

	/**
	 * Gets the name of this Table's Model.

	 * @return String
	 */
	public static function getModelName() {
		return 'User';
	}

	/**
	 * @return String
	 */
	public static function getTableName() {
		return 'UserTable';
	}

	/**
	 * @return String
	 */
	public static function getDBTable() {
		return 'users';
	}

	/**
	 * Get whether the given object is an instance of this table's model.
	 *
	 * @param object $obj
	 * @return bool TRUE if $obj is an instance of \eoko\MultiClient\Model\User
	 */
	public static function isInstanceOfModel($obj) {
		return $obj instanceof \eoko\MultiClient\Model\User;
	}

	/**
	 * @param array $context
	 * @return \myQuery
	 */
	protected function doCreateQuery(array $context = null) {
		return \myQuery::create($this, $context);
	}

	/**
	 * @param $getQuery
	 * @param array $context
	 * @return \myQuery
	 */
	public static function createQueryGet(&$getQuery, array $context = null) {
		return self::createQuery($context, $getQuery);
	}

	/**
	 * Create a new User.
	 *
	 * The new record will be considered new until its primary key is set.
	 *
	 * An array of values can be given to initialize the record's fields. It
	 * is not required for all model's fields to have a value in the given
	 * array; the absent fields will be set to NULL.
	 *
	 * @param array $initValues an array containing values with which the
	 * Model's fields will be initialized.
	 *
	 * @param bool $strict if set to TRUE, then all field of the model will be
	 * required to be set in $initValues, or an IllegalArgumentException will
	 * be thrown.
	 *
	 * @param array $context
	 *
	 * @return \eoko\MultiClient\Model\User
	 */
	public static function createModel($initValues = null, $strict = false, array $context = null) {
		return \eoko\MultiClient\Model\User::create($initValues, $strict, $context);
	}

	/**
	 * @return bool
	 */
	public static function hasPrimaryKey() {
		return true;
	}

	/**
	 * Get the name of the primary key field.
	 *
	 * @return string
	 */
	public static function getPrimaryKeyName() {
		return 'id';
	}

	/**
	 * Get the column representing the primary key.
	 *
	 * @return ModelColumn
	 */
	public static function getPrimaryKeyColumn() {
		return self::getInstance()->cols['id'];
	}

	/**
	 * Load a User record from the database, selected by
	 * its primary key.
	 *
	 * @param mixed $id
	 * @param array $context
	 *
	 * @return \eoko\MultiClient\Model\User the data Model from the loaded record, or null if no
	 * record matching the given primary key has been found
	 */
	public static function loadModel($id, array $context = null) {
		return \eoko\MultiClient\Model\User::load($id, $context);
	}

	/**
	 * @param $id
	 * @param array $context
	 * @return \eoko\MultiClient\Model\User
	 */
	public static function findByPrimaryKey($id, array $context = null) {
		return \eoko\MultiClient\Model\User::load($id, $context);
	}

	/**
	 * @param $id
	 * @param array $context
	 * @return \eoko\MultiClient\Model\User
	 */
	public static function findFirstByPrimaryKey($id, array $context = null) {
		return \eoko\MultiClient\Model\User::load($id, $context);
	}

	/**
	 *
	 * @param array $context
	 * @param const $mode
	 * @return ModelSet
	 */
	public static function findAll(array $context = null, $mode = ModelSet::ONE_PASS) {
		if ($context === null) {
			$context = array();
		}
		return self::findWhere('1', null, $mode, $context);
	}

	/**
	 * Create a new User record initialized by the given $data
	 * array. All the model's fields must have a value set in the $data array.
	 * The User record will be considered loaded and not-new.
	 *
	 * @param array $data
	 * @param array $context
	 * @return \eoko\MultiClient\Model\User
	 */
	public static function loadModelFromData(array $data, array $context = null) {
		return \eoko\MultiClient\Model\User::loadFromData($data, $context);
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
	public static function createNewModel($initValues = null, $strict = false, array $context = null) {
		return self::getInstance()->_createNewModel($initValues, $strict, $context);
	}

	 
	public static function getDBTableName() {
		return self::getInstance()->_getDBTableName();
	}

	/**
	 *
	 * @param mixed $excludeAutoOperation boolean FALSE or {ModelColumn::OP_CREATE | ModelColumn::OP_UPDATE}
	 * @param Bool $excludeFinal
	 * @return array
	 */ 
	public static function getColumns($excludeAutoOperation = false, $excludeFinal = false) {
		return self::getInstance()->_getColumns($excludeAutoOperation, $excludeFinal);
	}

	 
	public static function buildSelectAllColumns($tableName = null, $alias_es = null, $quoteTable = true) {
		return self::getInstance()->_buildSelectAllColumns($tableName, $alias_es, $quoteTable);
	}

	/**
	 *
	 * @param string $name
	 * @return Bool
	 */ 
	public static function hasColumn($name) {
		return self::getInstance()->_hasColumn($name);
	}

	/**
	 * Get a ModelTableColumn of UserTable from its name.
	 *
	 * @param string $name
	 * @param bool $require
	 * @throws \IllegalStateException
	 * @return ModelColumn the column matching the given field name, or NULL if
	 * this Model have no field matching this name
	 */ 
	public static function getColumn($name, $require = true) {
		return self::getInstance()->_getColumn($name, $require);
	}

	 
	public static function hasSetter($name) {
		return self::getInstance()->_hasSetter($name);
	}

	 
	public static function hasRelation($name) {
		return self::getInstance()->_hasRelation($name);
	}

	 
	public static function hasName() {
		return self::getInstance()->_hasName();
	}

	 
	public static function getNameFieldName($require = true) {
		return self::getInstance()->_getNameFieldName($require);
	}

	 
	public static function createReadQuery(array $context = null) {
		return self::getInstance()->_createReadQuery($context);
	}

	/**
	 * @return \ModelTableQuery
	 */ 
	public static function createLoadQuery($relationsMode = 1, array $context = null, $columns = null) {
		return self::getInstance()->_createLoadQuery($relationsMode, $context, $columns);
	}

	/**
	 *
	 * @param string $name
	 * @param bool $requireType
	 * @throws \IllegalStateException
	 * @throws \IllegalArgumentException
	 * @return \ModelRelationInfo
	 */ 
	public static function getRelationInfo($name, $requireType = false) {
		return self::getInstance()->_getRelationInfo($name, $requireType);
	}

	/**
	 * @param string $name
	 * @return ModelRelationInfoHasMany
	 */ 
	public static function getHasManyRelationInfo($name) {
		return self::getInstance()->_getHasManyRelationInfo($name);
	}

	/**
	 * @param string $name
	 * @return ModelRelationInfoHasOne
	 */ 
	public static function getHasOneRelationInfo($name) {
		return self::getInstance()->_getHasOneRelationInfo($name);
	}

	/**
	 *
	 * @return array
	 */ 
	public static function getRelationsInfo() {
		return self::getInstance()->_getRelationsInfo();
	}

	 
	public static function getRelationNames() {
		return self::getInstance()->_getRelationNames();
	}

	 
	public static function hasField($name) {
		return self::getInstance()->_hasField($name);
	}

	 
	public static function hasVirtual($name) {
		return self::getInstance()->_hasVirtual($name);
	}

	 
	public static function isVirtualCachable($name) {
		return self::getInstance()->_isVirtualCachable($name);
	}

	/**
	 * @return VirtualField
	 */ 
	public static function getVirtual($name) {
		return self::getInstance()->_getVirtual($name);
	}

	 
	public static function getVirtualNames() {
		return self::getInstance()->_getVirtualNames();
	}

	/**
	 * @internal on 2011-02-07, $require default has been changed from FALSE to TRUE!!!
	 */ 
	public static function getField($name, $require = true) {
		return self::getInstance()->_getField($name, $require);
	}

	 
	public static function insertRandom($n, $usr_mod = null, $zealous = false) {
		return self::getInstance()->_insertRandom($n, $usr_mod, $zealous);
	}

	/**
	 * Get all UserTable's columns names in an array
	 * @return Array the name of the columns as an array of strings
	 */ 
	public static function getColumnNames() {
		return self::getInstance()->_getColumnNames();
	}

	/**
	 * Delete a User reccord, selected by its primary key
	 * @param mixed $primaryKeyValue the value of the primary key of the reccord
	 * to be deleted
	 * @return Boolean TRUE if a row was successfuly deleted in the database,
	 * else FALSE
	 */ 
	public static function delete($primaryKeyValue) {
		return self::getInstance()->_delete($primaryKeyValue);
	}

	/**
	 * Starts a search in UserTable
	 * @param $condition
	 * @param $inputs,...
	 * @return ModelTableFinder
	 * @see QueryWhere for the syntax of a search
	 */ 
	public static function find($condition = null, $inputs = null) {
		return self::getInstance()->_find($condition, $inputs);
	}

	/**
	 *
	 * @param string $col
	 * @param mixed $value
	 * @param Const $mode
	 * @return ModelSet
	 */ 
	public static function findBy($col, $value, $mode) {
		return self::getInstance()->_findBy($col, $value, $mode);
	}

	/**
	 *
	 * @param string $col
	 * @param mixed $value
	 * @return User
	 */ 
	public static function findFirstBy($col, $value) {
		return self::getInstance()->_findFirstBy($col, $value);
	}

	/**
	 * @param \QueryWhere $where
	 * @param array $context
	 * @param callback $aliasingCallback
	 * @return \Model|null User
	 */ 
	public static function findFirst(\QueryWhere $where = null, array $context = null, $aliasingCallback = null) {
		return self::getInstance()->_findFirst($where, $context, $aliasingCallback);
	}

	/**
	 * @param array $context
	 * @return User
	 */ 
	public static function findFirstWhere($condition = null, $inputs = null, array $context = null, $aliasingCallback = null) {
		return self::getInstance()->_findFirstWhere($condition, $inputs, $context, $aliasingCallback);
	}

	/**
	 * Find the Model corresponding the given condition, when only one result
	 * is expected. If no corresponding model is found, NULL is returned. But, if
	 * more than one is found, an Exception is thrown.
	 * @return User
	 */ 
	public static function findOneWhere($condition = null, $inputs = null, array $context = null, $aliasingCallback = null) {
		return self::getInstance()->_findOneWhere($condition, $inputs, $context, $aliasingCallback);
	}

	/**
	 * Execute a search in UserTable, and returns the result as a ModelSet
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
	 */ 
	public static function findWhere($condition = null, $inputs = null, $mode = 0, array $context = null, $aliasingCallback = null, \ModelRelationReciproqueFactory $reciproqueFactory = null) {
		return self::getInstance()->_findWhere($condition, $inputs, $mode, $context, $aliasingCallback, $reciproqueFactory);
	}

	 
	public static function findWherePkIn(array $ids, $modelSet = 0, array $context = null, $aliasingCallback = null, \ModelRelationReciproqueFactory $reciproqueFactory = null) {
		return self::getInstance()->_findWherePkIn($ids, $modelSet, $context, $aliasingCallback, $reciproqueFactory);
	}

	/**
	 * Creates a ModelSet for the given Query.
	 *
	 * @param ModelTableQuery $loadQuery
	 * @param int $modelSetMode
	 * @param ModelRelationReciproqueFactory $reciproqueFactory
	 * @return ModelSet
	 */ 
	public static function createModelSet(\ModelTableQuery $loadQuery, $modelSetMode = 0, \ModelRelationReciproqueFactory $reciproqueFactory = null) {
		return self::getInstance()->_createModelSet($loadQuery, $modelSetMode, $reciproqueFactory);
	}

	 
	public static function deleteWhereIs($fieldValues, array $context = null) {
		return self::getInstance()->_deleteWhereIs($fieldValues, $context);
	}

	 
	public static function deleteWhereIn($field, $values, $context = null) {
		return self::getInstance()->_deleteWhereIn($field, $values, $context);
	}

	 
	public static function deleteWhereNotIn($field, $values, $context = null) {
		return self::getInstance()->_deleteWhereNotIn($field, $values, $context);
	}

	 
	public static function deleteWherePkIn($values, $context = null) {
		return self::getInstance()->_deleteWherePkIn($values, $context);
	}

	 
	public static function deleteWherePkNotIn($values, $context = null) {
		return self::getInstance()->_deleteWherePkNotIn($values, $context);
	}


}
