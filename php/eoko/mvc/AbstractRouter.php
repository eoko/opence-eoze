<?php

namespace eoko\mvc;

use Request as RequestData;
use Zend\Mvc\Router\RouteMatch;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 août 2012
 */
class AbstractRouter {
	
	/**
	 * @var RequestData
	 */
	protected $requestData;
	
	/**
	 * @var RouteMatch
	 */
	protected $routeMatch;
	
	public function __construct(RequestData $requestData, RouteMatch $routeMatch) {
		$this->requestData = $requestData;
		$this->routeMatch = $routeMatch;
	}
}
