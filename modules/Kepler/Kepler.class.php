<?php

namespace eoko\modules\Kepler;

use eoko\module\Module;
use eoko\module\ModuleLocation;
use eoko\util\GlobalEvents;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 30 nov. 2011
 */
class Kepler extends Module {
	
	protected $defaultExecutor = 'json';
	
	private $workingPath;
	
	public function getWorkingPath($subDirectory = null) {
		if ($this->workingPath === null) {
			if (null !== $path = $this->getConfig()->getValue('workingPath')) {
				$this->workingPath = $this->replacePathVariables($path);
			} else {
				throw new \MissingConfigurationException();
			}
		}
		if ($subDirectory) {
			return $this->workingPath . '/' . ltrim($subDirectory, '\/');
		} else {
			return $this->workingPath;
		}
	}
	
	private function replacePathVariables($path) {
		$search  = array();
		$replace = array();
		foreach (array(
			'%var%' => MY_EOZE_PATH,
		) as $var => $rep) {
			$search[]  = $var;
			$replace[] = rtrim($rep, '\/');
		}
		return str_replace($search, $replace, $path);
	}
	
	protected function construct(ModuleLocation $location) {
		GlobalEvents::addListener('Browser', 'reload', array($this, 'clearWaitingEvents'));
	}
	
	public function clearWaitingEvents() {
		
	}
	
	public function buildCometEntries(array $entries) {
		$result = array();
		foreach ($entries as $entry) {
			$result[$entry->category][] = $entry->data;
		}
		return $result;
	}
}
