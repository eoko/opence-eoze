<?php

namespace eoko\database;

use eoko\util\Arrays;
use eoko\util\collection\ImmutableMap;

use RuntimeException;

/**
 * Creates a database Adapter according to the given configuration.
 */
class DatabaseProxy {

	private $adapter = null;

	private $connection = null;

	private $config = null;

	private $sourceConfig = null;

	private $defaultConfig = array(
		'characterSet' => 'utf8',
		'collation' => 'utf8_general_ci',
	);

	public function __construct(array $sourceConfig) {
		$this->sourceConfig = Arrays::apply($this->defaultConfig, $sourceConfig);
	}

	/**
	 * Get config for database from the default node.
	 * @return \eoko\util\collection\Map
	 */
	public function getConfig() {
		if (!$this->config) {
			$this->config = $this->sourceConfig;

			$defaults = $config =& $this->config;

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

			// Process environment specific configuration
			$env = getenv('APPLICATION_ENV');
			if ($env && isset($config[$env])) {
				$envConfig = $config[$env];
				unset($config[$env]);
				Arrays::apply($config, $envConfig);
			}

			if (isset($config['username']) && !isset($config['user'])) {
				$config['user'] = $config['username'];
			}

			$this->config = new \eoko\util\collection\ImmutableMap($config);
		}
		return $this->config;
	}

	/**
	 * @return Adapter
	 */
	public function getAdapter() {

		if (!$this->adapter) {
			$config = self::getConfig();
			$adapter = ucfirst(strtolower($config->adapter));
			$class = __NAMESPACE__ . "\\Adapter\\{$adapter}Adapter";
			if (!class_exists($class)) {
				throw new RuntimeException('Unknown database adapter: ' . $class);
			}
			$this->adapter = new $class($config);
		}

		return $this->adapter;
	}

	/**
	 * @return PDO
	 */
	public function getConnection() {
		if (!$this->connection) {
			$this->connection = self::getAdapter()->getConnection();
		}
		return $this->connection;
	}
}
