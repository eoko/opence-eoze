<?php

namespace eoko\mvc;

use Zend\Http\Request;
use Zend\Mvc\Router\RouteMatch;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 août 2012
 */
abstract class AbstractRequestReader implements RequestReader {
	
	/**
	 * @var Request
	 */
	protected $request;
	
	/**
	 * @var RouteMatch
	 */
	protected $routeMatch;
	
	public function __construct(Request $request, RouteMatch $routeMatch) {
		$this->httpRequest = $request;
		$this->routeMatch = $routeMatch;
	}
}
