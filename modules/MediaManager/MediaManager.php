<?php

namespace eoko\modules\MediaManager;

//use eoko\module\Module;
use eoko\_getModule\GridModule;
use eoko\module\ModuleLocation;
use eoko\module\traits\HasRoutes;
use Zend\Mvc\Router\Http\Regex;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 août 2012
 */
class MediaManager extends GridModule implements HasRoutes {

	/**
	 * @var Regex
	 */
	private $downloadRoute;
	
	protected function construct(ModuleLocation $location) {
		parent::construct($location);
		if (!$this->isAbstract()) {
			
			$downloadUrl = $this->getConfig()->get('downloadUrl');
			$quotedDownloadUrl = preg_quote($downloadUrl, '/');
			
			$this->downloadRoute = Regex::factory(array(
				'regex' => "/$quotedDownloadUrl/(?<path>.+)",
				'spec' => "/$downloadUrl/%path%",
				'defaults' => array(
					'_RequestReader' => 'eoko\mvc\LegacyRequestReader',
					'_Router' => 'eoko\mvc\LegacyRouter',
					'controller' => $this->getName() . '.download',
					'action' => 'download',
				),
			));
		}
	}
	
	public function getRoutesConfig() {
		if ($this->downloadRoute) {
			return array(
				$this->getName() . '/download-route' => $this->downloadRoute,
			);
		}
	}
	
	/**
	 * @return Regex
	 */
	public function getDownloadRoute() {
		return $this->downloadRoute;
	}
	
	public function getDownloadPath($subPath = null) {
		$path = str_replace(array('%ROOT%/', '%ROOT%'), ROOT, $this->getConfig()->get('downloadPath'));
		$path = rtrim($path, '\\/');
		if ($subPath) {
			return $path . DS . $subPath;
		} else {
			return $path;
		}
	}
}
