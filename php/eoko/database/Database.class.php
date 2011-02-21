<?php

namespace eoko\database;

use eoko\config\ConfigManager;

class Database {

	private function __construct() {}

	private static $defaultAdapter = null, $defaultConnection = null;
	
	private static $config = null;

	/**
	 * Get config for database from the default node.
	 * @return eoko\util\collection\Map
	 */
	public static function getDefaultConfig() {
		if (!self::$config) self::$config = ConfigManager::getConfigObject(__NAMESPACE__);
		return self::$config;
	}

	/**
	 * @return Adapter
	 */
	public static function getDefaultAdapter() {
		
		if (!self::$defaultAdapter) {
			$config = self::getDefaultConfig();
			$class = __NAMESPACE__ . "\\Adapter\\{$config->adapter}Adapter";
			self::$defaultAdapter = new $class($config);
		}
		
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