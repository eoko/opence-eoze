<?php

namespace eoko\MultiClient\Model\Proxy;

/**
 * Proxy of the Client Table.
 *
 * @category Eoze
 * @package Model
 * @subpackage Proxy
 */
class ClientTableProxy extends \ModelTableProxy {

	private static $tableVars = array();

	public static $tableName = 'ClientTable';
	public static $modelName = 'Client';
	public static $dbTableName = 'clients';

	private static $instance = null;

	public static function get() {
		if (self::$instance === null) self::$instance = new ClientTableProxy;
		return self::$instance;
	}

	/**
	 * @return ClientTableProxy
	 */
	public static function getInstance() {
		$table = \eoko\MultiClient\Model\ClientTable::getInstance();
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
		return 'ClientTable';
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
