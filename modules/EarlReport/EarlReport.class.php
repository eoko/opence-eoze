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
	
	/**
	 * @return Earl
	 */
	public function getEarl() {
		if (!$this->earl) {
			// Register Earl's path in eoze class loader
			$this->registerClassLoader();

			$this->earl = new Earl($this->getConfig()->toArray());

//			$this->configureEarl($this->earl);
		}
		return $this->earl;
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
