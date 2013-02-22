<?php
/**
 * Copyright (C) 2013 Eoko
 *
 * This file is part of Opence.
 *
 * Opence is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Opence is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Opence. If not, see <http://www.gnu.org/licenses/gpl.txt>.
 *
 * @copyright Copyright (C) 2013 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

namespace eoko\MultiClients;

use eoko\database\Database;

/**
 *
 * @category Eoze
 * @package MultiClient
 * @since 2013-02-19 01:13
 */
class MultiClients {

	const DATABASE_PROXY_NAME = __CLASS__;

	/**
	 * @var string Path of the directory where config will be read.
	 */
	private $configDirectory;

	/**
	 * @var array|bool
	 */
	private $config = null;

	/**
	 * @param string $configDirectory Path of the directory where config will be read.
	 */
	private function __construct($configDirectory) {
		$this->configDirectory = $configDirectory;
	}

	private static $instances;

	/**
	 * @param $configDirectory
	 * @return MultiClients
	 */
	public static function forDirectory($configDirectory) {
		if (!isset(self::$instances[$configDirectory])) {
			self::$instances[$configDirectory] = new MultiClients($configDirectory);
		}
		return self::$instances[$configDirectory];
	}

	/**
	 * Gets the config array by reading a MultiClients.config.php file, if it exists in the config
	 * directory.
	 *
	 * @return array|bool
	 */
	public function getConfig() {
		if ($this->config === null) {
			$clientsConfigFile = $this->configDirectory . '/MultiClients.config.php';
			/** @noinspection PhpIncludeInspection */
			$this->config = file_exists($clientsConfigFile)
				? require $clientsConfigFile
				: false;

			if ($this->config) {
				$this->registerDatabaseProxy($this->config);
			}
		}
		return $this->config;
	}

	/**
	 * Registers the database proxy according to the multi client configuration.
	 *
	 * This method won't be operational before the ClassLoader has been set up, and the Database
	 * class is available.
	 *
	 * @param $config
	 */
	private function registerDatabaseProxy($config) {
		if (isset($config['database'])) {
			Database::registerProxy($this->getDatabaseProxyName(), $config['database']);
		}
	}

	private function getDatabaseProxyName() {
		return self::DATABASE_PROXY_NAME;
	}

	/**
	 * @var Database
	 */
	private $database = null;

	/**
	 * @return Database|bool
	 */
	public function getDatabase() {
		if ($this->database === null) {
			$config = $this->getConfig();

			if (!isset($config['database'])) {
				$this->database = false;
			} else {
				$this->database = new Database($config['database']);
			}
		}
		return $this->database;
	}
}
