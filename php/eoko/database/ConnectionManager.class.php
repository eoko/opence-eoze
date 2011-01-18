<?php

namespace eoko\database;
use eoko\config\ConfigManager;
use eoko\util\Arrays;
use \Logger;
use \PDO;

class ConnectionManager {

	private static $connection = null;
	private static $params = null;
	
	private static $defaults = array(
		'user' => 'root'
		,'host' => 'localhost'
		,'database' => 'oce_dev'
		,'password' => 'root'
	);

	private function __construct() {}

	/**
	 * Establishes the connection to the database according to the configuration,
	 * if needed, and returns the PDO object representing this connection.
	 * @return PDO
	 */
	public static function get() {
		if (self::$connection === null) return self::createPDO();
		return self::$connection;
	}

	private static function getParams($name = null) {
		
		if (self::$params === null) {
			$params = self::$defaults;

			if (null !== $config = ConfigManager::get(__NAMESPACE__)) {
//REM				if (isset($config['database']) && is_array($config['database'])) {
//					$config = $config['database'];
//				}
				Arrays::apply($params, $config);
			}

			self::$params = $params;
			
			Logger::dbg('Database: {}', $params['database']);
		}

		if ($name !== null) {
			return self::$params[$name];
		} else {
			return self::$params;
		}
	}

	private static function createPDO() {
		$p = self::getParams();

		self::$connection = new PDO(
			"mysql:dbname=$p[database];host=$p[host]", 
			$p['user'], 
			$p['password']
		);

		self::$connection->prepare("SET NAMES 'utf8' COLLATE 'utf8_general_ci'")->execute();

		return self::$connection;
	}
	
	public static function getDatabaseName() {
		return self::getParams('database');
	}
}