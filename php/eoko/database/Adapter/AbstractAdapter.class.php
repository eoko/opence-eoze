<?php

namespace eoko\database\Adapter;

use eoko\database\Adapter;
use eoko\database\Database; // better to have a dependance on that rather than ConfigManager
use eoko\util\collection\Map;

use InvalidArgumentException;

abstract class AbstractAdapter implements Adapter {

	/**
	 * @var string prefix applied to all tables in the database.
	 */
	private $prefix = null;
	private $prefixLength = 0;

	/** @var PDO */
	private $connection = null;

	/** @var Config */
	protected $config;

	public function __construct(Map $config = null) {

		if ($config !== null) {
			$this->config = $config;
		} else {
			$this->config = Database::getDefaultConfig();
		}
		
		// prefix
		$this->prefix = $this->config->prefix;
		$this->prefixLength = $this->prefix !== null ? strlen($this->prefix) : 0;
	}

	public function getConfig() {
		return $this->config;
	}
	
	public function getDatabaseName() {
		return $this->config->database;
	}

	/**
	 * @return PDO
	 */
	abstract protected function createConnection();

	public function getConnection() {
		if (!$this->connection) {
			$this->connection = $this->createConnection();
		}
		return $this->connection;
	}

	public function testTablePrefix($dbTableName) {
		return $this->prefix === null || substr($dbTableName, 0, $this->prefixLength) === $this->prefix;
	}

	public function databaseTableNameToInternal($dbTableName) {
		if ($this->prefix === null) {
			return $dbTableName;
		} else if (!self::testTablePrefix($dbTableName)) {
			throw new InvalidArgumentException(
				"The given table name doesn't match the configured prefix: $dbTableName"
			);
		} else {
			return substr($dbTableName, $this->prefixLength);
		}
	}

	public function internalTableNameToDatabase($internalTableName) {
		return $this->prefix . $internalTableName;
	}
	
	public function getDumper() {
		return null;
	}
	
}