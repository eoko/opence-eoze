<?php

namespace eoko\modules\Kepler;

use eoko\module\executor\JsonExecutor;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 30 nov. 2011
 * 
 * @method Kepler getModule
 */
class Json extends JsonExecutor {

	public function index() {

		/** @var Kepler $module  */
		$module = $this->getModule();
		$path = $module->getQueueFilePath();
		$refreshRate = $module->getConfig()->getValue('refreshRate', 1);

		if (null !== $timeout = $this->request->get('timeout')) {
			$maxTime = time() + min(ini_get('max_execution_time') - 5, $timeout);
		} else {
			$maxTime = time() + ini_get('max_execution_time') - 5;
		}

		// Wait for entry file
		while (!file_exists($path)) {
			if (time() >= $maxTime) {
				return true;
			} else {
				sleep($refreshRate);
			}
		}

		// Unserialize entries
		$entries = array();
		foreach (file($path) as $entry) {
			$entries[] = unserialize($entry);;
		}

		// Delete file
		unlink($path);

		// Build response
		$this->entries = $module->buildCometEntries($entries);

		return true;
	}
}
