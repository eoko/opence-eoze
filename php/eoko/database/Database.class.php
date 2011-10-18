<?php

namespace eoko\database;

use eoko\config\ConfigManager;
use eoko\util\Arrays;
use eoko\util\collection\ImmutableMap;

class Database {
	
	/**
	 * @var DatabaseProxy
	 */
	private static $defaultProxy = null;
	
	private function __construct() {}
	
	public static function reset() {
		self::$defaultProxy = null;
	}
	
	/**
	 * @return DatabaseProxy
	 */
	private function getDefaultProxy() {
		if (self::$defaultProxy === null) {
			self::$defaultProxy = new DatabaseProxy(ConfigManager::get(__NAMESPACE__));
		}
		return self::$defaultProxy;
	}

	/**
	 * Get config for database from the default node.
	 * @return eoko\util\collection\Map
	 */
	public static function getDefaultConfig() {
		return self::getDefaultProxy()->getConfig();
	}

	/**
	 * @return Adapter
	 */
	public static function getDefaultAdapter() {
		return self::getDefaultProxy()->getAdapter();
	}

	/**
	 * @return PDO
	 */
	public static function getDefaultConnection() {
		return self::getDefaultProxy()->getConnection();
	}
}