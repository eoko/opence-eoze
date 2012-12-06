<?php

namespace eoko\log;

use LogEntry;
use FirePHP;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 8 nov. 2011
 */
class FirePHPAdapter {
	
	private $firePHP;
	
	public function __construct() {
		$found = false;
		foreach (explode(':', get_include_path()) as $dir) {
			$filename = LIBS_PATH . 'FirePHPCore/FirePHP.php';
			if (file_exists($filename)) {
				require_once($filename);
				$found = true;
				break;
			}
		}
		
		if ($found) {
			$this->firePHP = FirePHP::getInstance(true);
			$this->firePHP->setOptions(array('includeLineNumbers' => false));
			return $this->firePHP;
		} else {
			Logger::getLogger('LoggerFirePHPAppender')->error('FirePHP.php '
					. 'cannot be found in include path');
		}
	}
	
	public function process(LogEntry $entry) {
		switch ($entry->level) {
			case Logger::INFO:
				$this->firePHP->info("{$entry->getLevelName()} {$entry->fileLine} -- {$entry->msg}");
				break;
			case Logger::WARNING:
				$this->firePHP->warn("{$entry->getLevelName()} {$entry->fileLine} -- {$entry->msg}");
				break;
			case Logger::ASSERTION:
			case Logger::ERROR:
				$this->firePHP->error("{$entry->getLevelName()} {$entry->fileLine} -- {$entry->msg}");
				break;
			default:
				$this->firePHP->log("{$entry->getLevelName()} {$entry->fileLine} -- {$entry->msg}");
				break;
		}
	}
}
