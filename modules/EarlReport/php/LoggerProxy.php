<?php

namespace eoko\modules\EarlReport;

use EarlReport\Xapi\Logger as Xapi;
use eoko\log\Logger;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 26 janv. 2012
 */
class LoggerProxy implements Xapi {
	
	/**
	 * @var Logger
	 */
	private $logger;
	
	public function __construct($context) {
		$this->logger = Logger::get($this);
	}
	
	public function debug($message) {
		$this->logger->debug($message);
	}
	
	public function info($message) {
		$this->logger->info($message);
	}
	
	public function warn($message) {
		$this->logger->warn($message);
	}
	
	public function error($message) {
		$this->logger->error($message);
	}
}
