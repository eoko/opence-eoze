<?php

namespace eoko\modules\Kepler;

use eoko\module\Module;
use eoko\module\ModuleLocation;
use eoko\util\GlobalEvents;
use Zend\Session\SessionManager;

/**
 *
 * **Important**: currently, the reload event listener for cleaning waiting
 * events must be registered manually in the application bootstrap!
 * 
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 30 nov. 2011
 */
class Kepler extends Module {

	protected $defaultExecutor = 'json';

	private $workingPath;

	private $sessionManager = null;

	/**
	 * This method is overridden here to allow the unit tests to replace the session 
	 * manager. This is bad practice but that seems the least worst when considering
	 * the context (UserManager used as global everywhere...).
	 * @return SessionManager
	 */
	public function getSessionManager() {
		if ($this->sessionManager) {
			return $this->sessionManager;
		} else {
			return parent::getSessionManager();
		}
	}

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

	public function getQueueFilePath() {
		return $this->getWorkingPath($this->getSessionManager()->getId());
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

	public function clearWaitingEvents() {
		if (file_exists($file = $this->getQueueFilePath())) {
			unlink($file);
		}
	}

	public function buildCometEntries(array $entries) {
		$result = array();
		foreach ($entries as $entry) {
			$result[$entry->category][] = $entry->data;
		}
		return $result;
	}
}
