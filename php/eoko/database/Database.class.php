<?php

namespace eoko\database;

class Database {

	private function __construct() {}

	private static $defaultAdapter = null, $defaultConnection = null;

	/**
	 * @return Adapter
	 */
	public static function getDefaultAdapter() {
		if (!self::$defaultAdapter) self::$defaultAdapter = new Adapter\MysqlAdapter();
		return self::$defaultAdapter;
	}

	/**
	 * @return PDO
	 */
	public static function getDefaultConnection() {
		if (!self::$defaultConnection) {
			self::$defaultConnection = self::getDefaultAdapter()->getConnection();
		}
		return self::$defaultConnection;
	}
}