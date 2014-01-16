<?php

namespace eoko\mvc;

use Zend\Http\Request;
use Zend\Mvc\Router\RouteMatch;

/**
 * Abstract RequestReader. Provides implementation of the constructor.
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

	/**
	 * @inheritdoc
	 */
	public function __construct(Request $request, RouteMatch $routeMatch) {
		$this->request = $request;
		$this->routeMatch = $routeMatch;
	}
}
