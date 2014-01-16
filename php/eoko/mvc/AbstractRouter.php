<?php

namespace eoko\mvc;

use Request as RequestData;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Router\RouteStackInterface as Router;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2012-08-03
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

	/**
	 * @var Router
	 */
	protected $router;

	public function __construct(RequestData $requestData, Router $router, RouteMatch $routeMatch) {
		$this->requestData = $requestData;
		$this->router = $router;
		$this->routeMatch = $routeMatch;
	}
}
