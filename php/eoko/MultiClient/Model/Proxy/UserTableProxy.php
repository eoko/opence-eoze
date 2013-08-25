<?php

namespace eoko\MultiClient\Model\Proxy;

/**
 * Proxy of the User Table.
 *
 * @category Eoze
 * @package Model
 * @subpackage Proxy
 */
class UserTableProxy extends \ModelTableProxy {

	private static $tableVars = array();

	public static $tableName = 'UserTable';
	public static $modelName = 'User';
	public static $dbTableName = 'users';

	private static $instance = null;

	public static function get() {
		if (self::$instance === null) self::$instance = new UserTableProxy;
		return self::$instance;
	}

	/**
	 * @return UserTableProxy
	 */
	public static function getInstance() {
		$table = \eoko\MultiClient\Model\UserTable::getInstance();
		foreach (self::$tableVars as &$pointer) {
			$pointer = $table;
		}
		return $table;
	}

	/**
	 * @param $pointer
	 * @return \ModelTableProxy
	 */
	public function attach(&$pointer) {
		self::$tableVars[] =& $pointer;
		return $pointer = $this;
	}

	public static function __callStatic($name, $arguments) {
		$instance = self::getInstance();
		return call_user_func_array(array($instance, $name), $arguments);
	}

	public function __call($name, $arguments) {
		$instance = self::getInstance();
		return call_user_func_array(array($instance, $name), $arguments);
	}

	public function __isset($name) {
		$instance = self::getInstance();
		return isset($instance->$name);
	}

	public function __get($name) {
		$instance = self::getInstance();
		return $instance->$name;
	}

	public function __set($name, $value) {
		$instance = self::getInstance();
		$instance->$name = $value;
	}

	public static function getTableName() {
		return 'UserTable';
	}

	public static function getDBTableName() {
		return self::$dbTableName;
	}

	public static function getModelName() {
		return self::$modelName;
	}
	public static function getPrimaryKeyName() {
		return 'id';
	}
}
