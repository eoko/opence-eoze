<?php

namespace eoko\database;

use eoko\config\ConfigManager;
use eoko\util\Arrays;
use eoko\util\collection\ImmutableMap;

class Database {

	private function __construct() {}

	private static $defaultAdapter = null, $defaultConnection = null;
	
	private static $config = null;

	/**
	 * Get config for database from the default node.
	 * @return eoko\util\collection\Map
	 */
	public static function getDefaultConfig() {
		if (!self::$config) {
			self::$config = ConfigManager::get(__NAMESPACE__);
			
			$defaults = $config =& self::$config;
			
			// Process server-conditional configuration
			if (isset($config['servers'])) {
				$servers = $config['servers'];
				unset($config['servers']);
				
				if (isset($servers['default'])) {
					$defaults =  $servers['default'];
				} else {
					$defaults = $config;
					unset($defaults['servers']);
				}
				unset($servers['default']);

				if (isset($_SERVER['SERVER_NAME'])) {
					$name = $_SERVER['SERVER_NAME'];
					foreach ($servers as $test => $cfg) {
						if (substr($name, -strlen($test)) === $test) {
							Arrays::apply($defaults, $cfg);
						}
					}
				}
				
				Arrays::apply($config, $defaults);
			}
			
			self::$config = new \eoko\util\collection\ImmutableMap($config);
		}
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