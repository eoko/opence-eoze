<?php

namespace eoko\database;

use eoko\config\ConfigManager;
use eoko\util\Arrays;
use eoko\util\collection\ImmutableMap;
use Zend\Db\Adapter\Adapter as DbAdapter;

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
	private static function getDefaultProxy() {
		if (self::$defaultProxy === null) {
			self::$defaultProxy = new DatabaseProxy(ConfigManager::get(__NAMESPACE__));
		}
		return self::$defaultProxy;
	}

	/**
	 * Get config for database from the default node.
	 * @return \eoko\util\collection\Map
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
	 * @return \PDO
	 */
	public static function getDefaultConnection() {
		return self::getDefaultProxy()->getConnection();
	}

	/**
	 * @return DbAdapter
	 */
	public static function getDefaultDbAdapter() {
		$config = self::getDefaultConfig()->toArray();

		$pairs = array(
			'database' => 'database',
			'host' => 'hostname',
			'port' => 'port',
			'characterSet' => 'charset',

			'user' => 'username',
			'password' => 'password',
		);

		$dbConfig = array(
			'driver' => 'Pdo_Mysql', // TODO hardcoded = bad
		);

		foreach ($pairs as $src => $target) {
			if (isset($config[$src])) {
				$dbConfig[$target] = $config[$src];
			}
		}

		return new DbAdapter($dbConfig);
	}
}
