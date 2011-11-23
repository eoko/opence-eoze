<?php

namespace eoze\test\phpunit;

use eoze\test\phpunit\DatabaseTestCase;

use PHPUnit_Extensions_Database_DataSet_IDataSet as IDataSet;
use PHPUnit_Extensions_Database_DataSet_CompositeDataSet as CompositeDataSet;

use UserSession;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 23 nov. 2011
 */
abstract class ModuleTestCase extends DatabaseTestCase {
	
	protected $username = 'test';
	protected $password = 'test';
	
	protected function setUp() {
		parent::setUp();
		UserSession::login($this->username, $this->password);
	}
	
	/**
	 * @return IDataSet
	 */
	protected function getDataset() {
		$baseDataSet = $this->createYmlDataSet(__DIR__ . '/users.yml');
		if (null !== $moduleDataSet = $this->getModuleDataset()) {
			$dataset = new CompositeDataSet(array(
				$baseDataSet,
				$moduleDataSet,
			));
		} else {
			return $baseDataSet;
		}
	}
	
	/**
	 * @return IDataSet
	 */
	protected function getModuleDataset() {
		return null;
	}
}
