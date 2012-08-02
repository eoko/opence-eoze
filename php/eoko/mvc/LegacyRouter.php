<?php

namespace eoko\mvc;

use eoko\module\Module;
use eoko\config\ConfigManager;
use Request as RequestData;
use Zend\Mvc\Router\RouteMatch;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 août 2012
 */
class LegacyRouter {
	
	const CONFIG_NODE = 'eoko/router';
	
	/**
	 * @var RequestData
	 */
	private $requestData;
	/**
	 * @var RouteMatch
	 */
	private $routeMatch;
	
	public function __construct(RequestData $requestData, RouteMatch $routeMatch) {
		$this->requestData = $requestData;
		$this->routeMatch = $routeMatch;
	}
	
	public function route() {

		foreach ($this->routeMatch->getParams() as $name => $value) {
			if ($name[0] !== '_') {
				if (!$this->requestData->has($name)) {
					$this->requestData->override($name, $value);
				}
			}
		}
		
		if (!$this->requestData->has('controller')) {
			$this->requestData->override(
				'controller',
				ConfigManager::get(self::CONFIG_NODE, 'indexModule')
//				defined('APP_INDEX_MODULE') ? APP_INDEX_MODULE : self::ROOT_MODULE_NAME
			);
		}

		$action = Module::parseRequestAction($this->requestData);
		$action();
	}
}
