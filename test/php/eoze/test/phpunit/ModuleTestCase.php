<?php

namespace eoze\test\phpunit;

use eoze\test\phpunit\DatabaseTestCase;

use PHPUnit_Extensions_Database_DataSet_IDataSet as IDataSet;
use PHPUnit_Extensions_Database_DataSet_CompositeDataSet as CompositeDataSet;
use PHPUnit_Extensions_Database_DataSet_DefaultDataSet as DefaultDataSet;

use UserSession;
use IllegalStateException;
use eoko\config\ConfigManager;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 23 nov. 2011
 */
abstract class ModuleTestCase extends DatabaseTestCase {
	
	protected $username = 'test';
	protected $password = 'test';
	
	private $baseFixtures;
	
	protected function setUp() {
		
		// Completely overriding parent's setUp to change getDataSet
        $this->databaseTester = NULL;

        $this->getDatabaseTester()->setSetUpOperation($this->getSetUpOperation());
        $this->getDatabaseTester()->setDataSet($this->getCompositeDataSet());
        $this->getDatabaseTester()->onSetUp();

		// My code...
		UserSession::login($this->username, $this->password);
	}

	// Completely overriding parent's tearDown to change getDataSet
    protected function tearDown() {
        $this->getDatabaseTester()->setTearDownOperation($this->getTearDownOperation());
        $this->getDatabaseTester()->setDataSet($this->getCompositeDataSet());
        $this->getDatabaseTester()->onTearDown();

        /**
         * Destroy the tester after the test is run to keep DB connections
         * from piling up.
         */
        $this->databaseTester = NULL;
    }
	
	private static function getConfigFixtures() {
		$config = ConfigManager::get(get_class());
		$fixtures = array();
		if ($config) {
			if (isset($config['fixtures'])) {
				foreach ($config['fixtures'] as $fixture) {
					$fixtures[] = $fixture;
				}
			}
		}
		return $fixtures;
	}
	
	protected function getBaseFixtures() {
		if ($this->baseFixtures) {
			return array_merge(self::getConfigFixtures(), $this->baseFixtures);
		} else {
			return self::getConfigFixtures();
		}
	}
	
	protected function getModulesBaseDataSet() {
		if ($this->getBaseFixtures()) {
			$applicationDirectories = ConfigManager::get('eoze\\application\\directories');
			$sets = array();
			foreach ($this->getBaseFixtures() as $path) {
				while(preg_match('/%([^%]+)%/', $path, $matches)) {
					$alias = $matches[1];
					if (isset($applicationDirectories[$alias])) {
						$path = str_replace($matches[0], ROOT . $applicationDirectories[$alias], $path);
					} else {
						throw new IllegalStateException("Unknown application path alias: %$alias%");
					}
				}
				if (file_exists($path)) {
					$sets[] = $this->createYmlDataSet($path);
				} else {
					throw new IllegalStateException('Missing data set file: ' . $path);
				}
			}
			return new CompositeDataSet($sets);
		}
		return null;
	}
	
	/**
	 * @return IDataSet
	 */
	protected function getDataSet() {
		return null;
	}
	
	/**
	 * @return IDataSet
	 */
	private function getCompositeDataSet() {
		$baseDataSet   = $this->getModulesBaseDataset();
		$dataSet       = $this->getDataset();
		if ($baseDataSet) {
			return new CompositeDataSet(array(
				$baseDataSet,
				$moduleDataSet,
			));
		} else if ($dataSet) {
			return $dataSet;
		} else {
			return new DefaultDataSet();
		}
	}
}
