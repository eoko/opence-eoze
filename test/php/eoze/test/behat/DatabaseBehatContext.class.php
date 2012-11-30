<?php

namespace eoze\test\behat;

use Behat\Behat\Context\BehatContext;
// PHPUnit
use PHPUnit_Extensions_Database_Operation_Factory,
	PHPUnit_Extensions_Database_ITester,
	PHPUnit_Extensions_Database_DB_IDatabaseConnection,
	PHPUnit_Extensions_Database_Operation_DatabaseOperation,
	PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection,
	PHPUnit_Extensions_Database_DataSet_YamlDataSet,
	PHPUnit_Extensions_Database_DataSet_IDataSet as IDataSet,
	PHPUnit_Extensions_Database_DataSet_CompositeDataSet as CompositeDataset,
	PHPUnit_Extensions_Database_DataSet_DefaultDataSet as DefaultDataSet,
	PHPUnit_Extensions_Database_DefaultTester;

use PDO;

use eoko\database\Database;

/**
 * Most of the code of this class is copied from PHPUnit's PHPUnit_Extensions_Database_TestCase
 * class.
 */
abstract class DatabaseBehatContext extends BehatContext {

	// only instantiate pdo once for test clean-up/fixture load
	static private $pdo = null;
	// only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
	private $conn = null;

    /**
     * @var PHPUnit_Extensions_Database_ITester
     */
    protected $databaseTester;

    /**
	 * Returns the test dataset.
	 *
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	protected function getDataSet() {
		return $this->createEmptyDataSet();
	}

	/**
	 * Returns the test database connection.
	 *
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
	 * Creates a new DefaultDatabaseConnection using the given PDO connection
	 * and database schema name.
	 *
	 * @param PDO $connection
	 * @param string $schema
	 * @return PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 */
	protected function createDefaultDBConnection(PDO $connection, $schema = '') {
		return new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($connection, $schema);
	}

	/**
	 * Creates a IDatabaseTester for this testCase.
	 *
	 * @return PHPUnit_Extensions_Database_ITester
	 */
	protected function newDatabaseTester() {
		return new PHPUnit_Extensions_Database_DefaultTester($this->getConnection());
	}

    /**
	 * Gets the IDatabaseTester for this testCase. If the IDatabaseTester is
	 * not set yet, this method calls newDatabaseTester() to obtain a new
	 * instance.
	 *
	 * @return PHPUnit_Extensions_Database_ITester
	 */
	protected function getDatabaseTester() {
		if (empty($this->databaseTester)) {
			$this->databaseTester = $this->newDatabaseTester();
		}

		return $this->databaseTester;
	}

    /**
	 * Returns the database operation executed in test setup.
	 *
	 * @return PHPUnit_Extensions_Database_Operation_DatabaseOperation
	 */
	protected function getSetUpOperation() {
		return PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT();
	}

    /**
	 * Returns the database operation executed in test cleanup.
	 *
	 * @return PHPUnit_Extensions_Database_Operation_DatabaseOperation
	 */
	protected function getTearDownOperation() {
		return PHPUnit_Extensions_Database_Operation_Factory::NONE();
	}

	/**
	 * If the given $context is a Context that exposes a DataSet (that is,
	 * it contains a getDataSet() method), then this DataSet is returned,
	 * else NULL is returned.
	 * 
	 * @return IDataSet
	 */
	private static function getOwnDataSet(BehatContext $context) {
		if (method_exists($context, 'getDataSet')) {
			return $context->getDataSet();
		} else {
			return null;
		}
	}

	/**
	 * Returns a DataSet containing the given Context's one as well as all 
	 * its children's ones, or NULL if nor the given Context nor any of its
	 * children exposes a DataSet.
	 * 
	 * @return IDataSet
	 */
	private static function getDataSetFromContext(BehatContext $context) {

		$subContexts = $context->getSubContexts();

		if ($subContexts) {
			$dataSets = array();
			foreach ($subContexts as $context) {
				if (null !== $dataSet = self::getDataSetFromContext($context)) {
					if (is_array($dataSet)) {
						$dataSets = array_merge($dataSets, $dataSet);
					}
				}
			}
			if ($dataSets) {
				if (null !== $dataSet = self::getOwnDataSet($context)) {
					array_unshift($dataSets, $dataSet);
				}
				return $dataSets;
			}
		}

		return self::getOwnDataSet($context);
	}

	/**
     * Performs operation returned by getSetUpOperation().
	 * 
	 * @BeforeScenario
	 */
	public function setUpDatabaseFixtures() {

		if ($this->getMainContext() !== $this) {
			return;
		}

		$dataSets = self::getDataSetFromContext($this);

		if (!$dataSets) {
			return;
		} else if (is_array($dataSets)) {
			$dataSet = new CompositeDataset($dataSets);
		} else if ($dataSets instanceof IDataSet) {
			$dataSet = $dataSets;
		} else {
			throw new \Exception('');
		}

        $this->databaseTester = NULL;

        $this->getDatabaseTester()->setSetUpOperation($this->getSetUpOperation());
        $this->getDatabaseTester()->setDataSet($dataSet);
        $this->getDatabaseTester()->onSetUp();
	}

    /**
	 * Performs operation returned by getSetUpOperation().
	 * 
	 * @AfterScenario
	 */
	public function tearDownDatabaseFixtures() {

		if ($this->getMainContext() !== $this) {
			return;
		}

		$this->getDatabaseTester()->setTearDownOperation($this->getTearDownOperation());
		$this->getDatabaseTester()->setDataSet($this->getDataSet());
		$this->getDatabaseTester()->onTearDown();

		/**
		 * Destroy the tester after the test is run to keep DB connections
		 * from piling up.
		 */
		$this->databaseTester = NULL;
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
		$path = "$dir$ymlFile";
//		try {
			return new PHPUnit_Extensions_Database_DataSet_YamlDataSet($path);
//		} catch (\Exception $ex) {
//			throw new \Exception("Error parsing yaml: $path", 0, $ex);
//		}
	}

	/**
	 * @return DefaultDataSet
	 */
	protected function createEmptyDataSet() {
		return new DefaultDataSet();
	}

}
