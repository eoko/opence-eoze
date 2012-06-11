<?php

namespace eoko\database\Dumper;

use eoko\database\Dumper,
	eoko\log\Logger;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 10 avr. 2012
 * 
 * @version 1.0.0 10/04/12 01:13
 */
abstract class DumperAdapter implements DumperListener {
	
	/**
	 * @var Dumper
	 */
	private $dumper;

	public function __construct(Dumper $dumper) {
		$this->dumper = $dumper;
	}
	
	/**
	 * Gets the {@link DumperListener} execution context's {@link Dumper}.
	 * @return Dumper
	 */
	protected function getDumper() {
		return $this->dumper;
	}
//	
//	/**
//	 * Gets the {@link DumperListener} internal {@link Logger}.
//	 * @return Logger
//	 */
//	protected function getLogger() {
//		if (!$this->logger) {
//			$this->logger = Logger::get($this);
//		}
//		return $this->logger;
//	}
//	
//	protected function getConfig() {
//		return $this->dumper->getConfig();
//	}
	
	public function beforeDump($dataFilename, $structureFilename = null) {}
}
