<?php

namespace eoko\modules\EarlReport;

use eoko\module\Module;
use eoko\modules\EarlReport\LoggerProxy;

use EarlReport\EarlReport as Earl;

use RuntimeException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 26 janv. 2012
 */
class EarlReport extends Module {

	private static $classLoaderRegistered = false;

	/**
	 * @var EarlReport
	 */
	private $earl;

	public function createExecutor($type, $action = null, Request $request = null, $internal = false) {
		throw new RuntimeException('EarlReport module is not executable.');
	}

	private function registerClassLoader() {
		if (!self::$classLoaderRegistered) {

			$this->getClassLoader()->addIncludePath(__DIR__ . '/lib');

			require_once __DIR__ .'/lib/EarlReport.php';

			self::$classLoaderRegistered = true;
		}
	}

	private $customEarlClassesCache = null;

	/**
	 * Creates a new Earl (or a subclass) object.
	 * 
	 * @param string $class A custom class to be used. If an instance of this class
	 * has already been created by this module, this instance will be returned, instead
	 * of creating a new one.
	 * 
	 * @return Earl
	 */
	public function getEarl($class = null) {

		// Default class
		if ($class === null) {
			if (!$this->earl) {
				// Register Earl's path in eoze class loader
				$this->registerClassLoader();
				// Create & config
				$this->earl = new Earl($this->getConfig()->toArray());
			}
			return $this->earl;
		}

		// Creates with a custom class
		else {
			if (!isset($this->customEarlClassesCache[$class])) {
				// Register Earl's path in eoze class loader
				$this->registerClassLoader();
				// Create
				$this->customEarlClassesCache[$class] = new $class($this->getConfig()->toArray());
			}
			return $this->customEarlClassesCache[$class];
		}
	}

//	protected function configureEarl(Earl $earl) {
//		$config = $this->getConfig();
//
//		$earl->setSofficeCommand($config->get('soffice'))
//				->setUnoconvCommand($config->get('unoconv'))
//				->setLogger(new LoggerProxy($earl));
//
//		if (!$earl->checkDependencies()) {
//			throw new RuntimeException('Missing dependency for EarlReport.');
//		}
//
//		$earl->getContext()
//				->setDateFormat($config->get('dateFormat'))
//				->setDateTimeFormat($config->get('dateTimeFormat'));
//	}
}
