<?php

namespace eoko\MultiClients\Model\Base;

/**
 * Base of the Client Model.
 *
 * @category Eoze
 * @package Model
 * @subpackage Base
 *
 * @property $id
 * @property $name
 * @property $home_directory
 * @property $database_name
 * @property-read $users
 */
abstract class ClientBase extends \myModel {

	const F_ID = 'id';
	const F_NAME = 'name';
	const F_HOME_DIRECTORY = 'home_directory';
	const F_DATABASE_NAME = 'database_name';

	/**
	 * @param array $initValues an array of values ($fieldName => $value) to initially set
	 * the Client with.
	 * @param bool $strict
	 * @param array $context
	 */
	protected function __construct($initValues = null, $strict = false, array $context = null) {

		$fields = array(
				'id' => null,
				'name' => null,
				'home_directory' => null,
				'database_name' => null
		);

		parent::__construct($fields, $initValues, $strict, $context);
	}

	/**
	 * Get the name of this class's Model.
	 *
	 * @return string
	 */
	protected function getModelName() {
		return 'Client';
	}

	/**
	 * Create a new Client	 *
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
	 * @return \eoko\MultiClients\Model\Client
	 */
	public static function create($initValues = null, $strict = false, array $context = null) {
		return new \eoko\MultiClients\Model\Client($initValues, $strict, $context);
	}


	/**
	 * Set the value of this record's primary key.
	 */
	public function setPrimaryKeyValue($value) {
		$this->setPrimaryKeyValueImpl('id', $value);
	}

	/**
	 * @return mixed the value of this record's primary key.
	 */
	public function getPrimaryKeyValue() {
		return $this->getId();
	}

	/**
	 * Create a new Client, and load it from the database, according to its
	 * $primaryKey value.
	 * 
	 * @param mixed $id
	 *
	 * @param array $context
	 * 
	 * @return \eoko\MultiClients\Model\Client the data Model from the loaded record, or null if no
	 * record matching the given primary key has been found
	 */
	public static function load($id, array $context = null) {

		$model = new \eoko\MultiClients\Model\Client(array(
			'id' => $id		), false, $context);

		return $model->doLoad($context);
	}

	/**
	 * @param boolean $loadFromDB determines how the initial values are acquired.
	 * If TRUE, the data will be forcefully loaded from the datastore, throwing
	 * an Exception if the data corresponding to this model have been removes.
	 * If FALSE, the data will be tentatively retrieved from the initial values
	 * stored in the Client, throw an Exception if these data
	 * have not been filled up (which depends on the method used to fill in
	 * the model's data). If NULL, initial values will be used if they exist,
	 * else it will be tried to load the model from the datastore, eventually
	 * throwing an Exception if no method works.
	 *
	 * @return \eoko\MultiClients\Model\Client
	 */
	public function getStoredCopy($loadFromDB = null) {
		return $this->doGetStoredCopy($loadFromDB);
	}


	/**
	 * Load a %%Model%% from the given data. All model's fields must have a
	 * set value in the $data array. The model will be considered loaded and
	 * not-new, when being created this way.
	 *
	 * @param array $data
	 * @param array $context
	 * @return \eoko\MultiClients\Model\Client
	 * @ignore
	 */
	public static function loadFromData(array $data, array $context = null) {
		return new \eoko\MultiClients\Model\Client($data, true, $context);
	}

	/**
	 * @return \eoko\MultiClients\Model\ClientTable
	 */
	public static function getTable() {
		return \eoko\MultiClients\Model\ClientTable::getInstance();
	}

	/**
	 * Get the value of the id field.
	 *
	 * @return int
	 */
	public function getId() {
		$v = $this->getField('id');
		return $v === null ? null : (int) $v;
	}

	/**
	 * Set the value of the id field.
	 *
	 * @param int $id
	 * @param $forceAcceptNull
	 * @return \eoko\MultiClients\Model\Client
	 */
	public function setId($id, $forceAcceptNull = false) {
		$this->setColumn('id', $id, $forceAcceptNull);
		return $this;
	}

	/**
	 * Get the value of the name field.
	 *
	 * @return string
	 */
	public function getName() {
		$v = $this->getField('name');
		return $v === null ? null : (string) $v;
	}

	/**
	 * Set the value of the name field.
	 *
	 * @param string $name
	 * @param $forceAcceptNull
	 * @return \eoko\MultiClients\Model\Client
	 */
	public function setName($name, $forceAcceptNull = false) {
		$this->setColumn('name', $name, $forceAcceptNull);
		return $this;
	}

	/**
	 * Get the value of the homeDirectory field.
	 *
	 * @return string
	 */
	public function getHomeDirectory() {
		$v = $this->getField('home_directory');
		return $v === null ? null : (string) $v;
	}

	/**
	 * Set the value of the homeDirectory field.
	 *
	 * @param string $homeDirectory
	 * @param $forceAcceptNull
	 * @return \eoko\MultiClients\Model\Client
	 */
	public function setHomeDirectory($homeDirectory, $forceAcceptNull = false) {
		$this->setColumn('home_directory', $homeDirectory, $forceAcceptNull);
		return $this;
	}

	/**
	 * Get the value of the databaseName field.
	 *
	 * @return string
	 */
	public function getDatabaseName() {
		$v = $this->getField('database_name');
		return $v === null ? null : (string) $v;
	}

	/**
	 * Set the value of the databaseName field.
	 *
	 * @param string $databaseName
	 * @param $forceAcceptNull
	 * @return \eoko\MultiClients\Model\Client
	 */
	public function setDatabaseName($databaseName, $forceAcceptNull = false) {
		$this->setColumn('database_name', $databaseName, $forceAcceptNull);
		return $this;
	}

	/**
	 *
	 * @param array $overrideContext
	 * @return \ModelSet
	 */
	public function getUsers(array $overrideContext = null) {
		return $this->getForeignModel('Users', $overrideContext);
	}
	/**
	 *
	 * @param \eoko\MultiClients\Model\User[] $users
	 * @return \eoko\MultiClients\Model\User[]
	 */
	public function setUsers(array $users) {
		// return $this->getRelation('Users')->get($this);
		return $this->setForeignModel('Users', $users);
	}

}
