<?php

namespace eoko\mvc;

use Zend\Http\Request;
use Zend\Mvc\Router\RouteMatch;

/**
 * Marker class for legitimate Request data readers.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2012-07-29
 */
interface RequestReader {

	/**
	 * Creates a new RequestReader.
	 *
	 * @param \Zend\Http\Request $request
	 * @param \Zend\Mvc\Router\RouteMatch $routeMatch
	 * @return \eoko\mvc\RequestReader
	 */
	function __construct(Request $request, RouteMatch $routeMatch);

	function createRequest();
}
