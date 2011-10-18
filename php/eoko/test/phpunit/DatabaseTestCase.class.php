<?php

namespace eoko\test\phpunit;

require_once 'PHPUnit/Extensions/Database/TestCase.php';

use eoko\database\Database;

class DataSetFilter extends PHPUnit_Extensions_Database_DataSet_DataSetFilter {}

abstract class DatabaseTestCase extends PHPUnit_Extensions_Database_TestCase {

	// only instantiate pdo once for test clean-up/fixture load
	static private $pdo = null;
	// only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
	private $conn = null;

	/**
	 * @return PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 */
	public function getConnection() {
		if ($this->conn === null) {
			if (self::$pdo == null) {
				self::$pdo = Database::getDefaultConnection();
			}
			$this->conn = $this->createDefaultDBConnection(self::$pdo, 'oce_dev');
		}

		return $this->conn;
	}

	/**
	 * @param string $ymlFile
	 * @param string $dir
	 * @return PHPUnit_Extensions_Database_DataSet_YamlDataSet 
	 */
    protected function createYmlDataSet($ymlFile, $dir = null) {
		if ($dir !== null && substr($dir, -1) !== DIRECTORY_SEPARATOR) {
			$dir = $dir . DIRECTORY_SEPARATOR;
		}
        return new PHPUnit_Extensions_Database_DataSet_YamlDataSet("$dir$ymlFile");
	}
}