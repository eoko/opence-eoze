<?php

namespace eoko\MultiClients\bin\Model;

/**
 * Base of the User Model.
 *
 * @category opence
 * @package Model
 * @subpackage Base
 *
 * @property $id
 * @property $client_id
 * @property $username
 * @property $password
 * @property $level
 * @property $first_name
 * @property $last_name
 * @property-read $client
 */
abstract class UserBase extends \myModel {

	const F_ID = 'id';
	const F_CLIENT_ID = 'client_id';
	const F_USERNAME = 'username';
	const F_PASSWORD = 'password';
	const F_LEVEL = 'level';
	const F_FIRST_NAME = 'first_name';
	const F_LAST_NAME = 'last_name';

	/**
	 * @param array $values an array of values ($fieldName => $value) to initially set
	 * the User with.
	 * @param array $context
	 */
	protected function __construct($initValues = null, $strict = false, array $context = null) {

		$fields = array(
				'id' => null,
				'client_id' => null,
				'username' => null,
				'password' => null,
				'level' => null,
				'first_name' => null,
				'last_name' => null
		);

		parent::__construct($fields, $initValues, $strict, $context);
	}

	/**
	 * Get the name of this class's Model.
	 * @return string
	 */
	protected function getModelName() {
		return 'User';
	}

	/**
	 * Create a new User	 *
	 * The new reccord will be considered new until its primary key is set.
	 *
	 * An array of values can be given to initialize the reccord's fields. It
	 * is not required for all model's fields to have a value in the given
	 * array; the absent fields will be set to NULL.
	 *
	 * @param array $initValues an array containing values with which the
	 * Model's fields will be initialized. This
	 *
	 * @param array $context
	 * 
	 * @return User
	 */
	static function create($initValues = null, $strict = false, array $context = null) {
		return new User($initValues, $strict, $context);
	}


	/**
	 * Set the value of this Reccord's primary key.
	 */
	function setPrimaryKeyValue($value) {
		$this->setPrimaryKeyValueImpl('id', $value);
	}

	/**
	 * @return mixed the value of this Reccord's primary key.
	 */
	function getPrimaryKeyValue() {
		return $this->getId();
	}

	/**
	 * Create a new User, and load it from the database, according to its
	 * $primaryKey value.
	 * 
	 * @param mixed $id
	 *
	 * @param array $context
	 * 
	 * @return User the data Model from the loaded reccord, or null if no
	 * reccord matching the given primary key has been found
	 */
	public static function load($id, array $context = null) {

		$model = new User(array(
			'id' => $id		), false, $context);

		return $model->doLoad($context);
	}

	/**
	 * @param boolean $loadFromDB determines how the initial values are acquired.
	 * If TRUE, the data will be forcefully loaded from the datastore, throwing
	 * an Exception if the data corresponding to this model have been removes.
	 * If FALSE, the data will be tentatively retrieved from the initial values
	 * stored in the User, throw an Exception if these data
	 * have not been filled up (which depends on the method used to fill in
	 * the model's data). If NULL, initial values will be used if they exist,
	 * else it will be tried to load the model from the datastore, eventually
	 * throwing an Exception if no method works.
	 * @return User 
	 */
	public function getStoredCopy($loadFromDB = null) {
		return $this->doGetStoredCopy($loadFromDB);
	}


	/**
	 * Load a %%Model%% from the given data. All model's fields must have a
	 * set value in the $data array. The model will be considered loaded and
	 * not-new, when being created this way.
	 * @param ModelTable $table
	 * @param array $data
	 * @param array $context
	 * @return %%Model%%
	 * @ignore
	 */
	public static function loadFromData(array $data, array $context = null) {
//		$model = new User();
//		$model->params = $model->context = $context;
//		$model->setAllFieldsFromDatabase($data);
//		return $model;
		return new User($data, true, $context);
	}

	/**
	 * @return UserTable
	 */
	static function getTable() {
		return UserTable::getInstance();
	}

	/**
	 * Get the value of the id field.
	 * @return Int
	 */
	function getId() {
		$v = $this->getField('id');
		return $v === null ? null : (int) $v;
	}

	/**
	 * Set the value of the id field.
	 * @param Int $value
	 * @return User 

	 */
	function setId($id, $forceAcceptNull = false) {
		$this->setColumn('id', $id, $forceAcceptNull);
		return $this;
	}

	/**
	 * Get the value of the clientId field.
	 * @return Int
	 */
	function getClientId() {
		$v = $this->getField('client_id');
		return $v === null ? null : (int) $v;
	}

	/**
	 * Set the value of the clientId field.
	 * @param Int $value
	 * @return User 

	 */
	function setClientId($clientId, $forceAcceptNull = false) {
		$this->setColumn('client_id', $clientId, $forceAcceptNull);
		return $this;
	}

	/**
	 * Get the value of the username field.
	 * @return String
	 */
	function getUsername() {
		$v = $this->getField('username');
		return $v === null ? null : (string) $v;
	}

	/**
	 * Set the value of the username field.
	 * @param String $value
	 * @return User 

	 */
	function setUsername($username, $forceAcceptNull = false) {
		$this->setColumn('username', $username, $forceAcceptNull);
		return $this;
	}

	/**
	 * Get the value of the password field.
	 * @return String
	 */
	function getPassword() {
		$v = $this->getField('password');
		return $v === null ? null : (string) $v;
	}

	/**
	 * Set the value of the password field.
	 * @param String $value
	 * @return User 

	 */
	function setPassword($password, $forceAcceptNull = false) {
		$this->setColumn('password', $password, $forceAcceptNull);
		return $this;
	}

	/**
	 * Get the value of the level field.
	 * @return Int
	 */
	function getLevel() {
		$v = $this->getField('level');
		return $v === null ? null : (int) $v;
	}

	/**
	 * Set the value of the level field.
	 * @param Int $value
	 * @return User 

	 */
	function setLevel($level, $forceAcceptNull = false) {
		$this->setColumn('level', $level, $forceAcceptNull);
		return $this;
	}

	/**
	 * Get the value of the firstName field.
	 * @return String
	 */
	function getFirstName() {
		$v = $this->getField('first_name');
		return $v === null ? null : (string) $v;
	}

	/**
	 * Set the value of the firstName field.
	 * @param String $value
	 * @return User 

	 */
	function setFirstName($firstName, $ignoredArgument = false) {
		$this->setColumn('first_name', $firstName, $ignoredArgument);
		return $this;
	}

	/**
	 * Get the value of the lastName field.
	 * @return String
	 */
	function getLastName() {
		$v = $this->getField('last_name');
		return $v === null ? null : (string) $v;
	}

	/**
	 * Set the value of the lastName field.
	 * @param String $value
	 * @return User 

	 */
	function setLastName($lastName, $ignoredArgument = false) {
		$this->setColumn('last_name', $lastName, $ignoredArgument);
		return $this;
	}

	/**
	 *
	 * @param array $overrideContext
	 * @return Client
	 */
	function getClient(array $overrideContext = null) {
		return $this->getForeignModel('Client', $overrideContext);
	}
	/**
	 *
	 * @param Client $client
	 * @return Client
	 */
	function setClient(Client $client) {
		// return $this->getRelation('Client')->get($this);
		return $this->setForeignModel('Client', $client);
	}

}
