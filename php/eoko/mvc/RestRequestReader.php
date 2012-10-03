<?php

namespace eoko\mvc;

use IllegalStatedException;
use Zend\Http\Request;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 29 juil. 2012
 */
class RestRequestReader implements RequestReader {
	
	private $uriPrefix = 'api/rest';
	/**
	 * @var Request
	 */
	private $httpRequest;

	public function __construct(Request $request) {
		$this->httpRequest = $request;
	}

	public function createRequest() {

		$request = new \Zend\Http\PhpEnvironment\Request();
		$request instanceof \Zend\Http\PhpEnvironment\Request;

//		$router = \Zend\Mvc\Router\SimpleRouteStack::factory(array(
		$router = \Zend\Mvc\Router\Http\TreeRouteStack::factory(array(
			'routes' => array(
				'home' => array(
					'type' => 'segment',
					'options' => array(
						'route'    => '/api/rest[/:name]',
						'defaults' => array(
							'controller' => 'Application\Controller\Index',
							'action'     => 'index',
						),
					),
				),
				// The following is a route to simplify getting started creating
				// new controllers and actions without needing to create a new
				// module. Simply drop new controllers in, and you can access them
				// using the path /application/:controller/:action
				'application' => array(
					'type' => 'Zend\Mvc\Router\Http\Literal',
					'options' => array(
						'route'    => '/application',
						'defaults' => array(
							'__NAMESPACE__' => 'Application\Controller',
							'controller'    => 'Index',
							'action'        => 'index',
						),
					),
					'may_terminate' => true,
					'child_routes' => array(
						'default' => array(
							'type'    => 'Segment',
							'options' => array(
								'route'    => '/[:controller[/:action]]',
								'constraints' => array(
									'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
									'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								),
								'defaults' => array(
								),
							),
						),
					),
				),
			),
		));

		$routeMatch = $router->match($request);
//		dump(array(
//			$routeMatch->getMatchedRouteName(),
//			$routeMatch->getParams(),
//		));

//		$request = new Request();
		dump(array(
			$request->getUri(),
			'query' => $request->getQuery()->toArray(),
			$request->getBaseUrl(),
			$request->getBasePath(),
			$request->getRequestUri(),
			$request->getEnv()->toArray(),
			$request->getEnv()->toArray(),
			$request->getServer()->toArray(),
			gettype($request->getContent()),
			$request->getContent(),
		));

		dump(1);
		dump(file_get_contents("php://input"));
		
		$uri = $this->trimUri($_SERVER['REQUEST_URI']);

		self::parsePathQuery($uri, $path, $query);
		
		$params = self::parseQueryParams($query);
		
		$method = $_SERVER['REQUEST_METHOD'];
		
		dump(array(
			$uri,
			$params,
			$method,
		));
		
		dump(array(
			$_SERVER['REQUEST_URI'],
			$_SERVER,
		));
	}
	
	/**
	 * Trim expected URI prefix (and all the path before it).
	 * @param string $uri
	 * @return string
	 * @throws IllegalStatedException If the expected prefix is not found in the given string.
	 */
	private function trimUri($uri) {
		$prefix = preg_quote($this->uriPrefix, '@');
		if (preg_match("@$prefix/(?P<trimmed>.*)@", $uri, $matches)) {
			return $matches['trimmed'];
		} else {
			throw new IllegalStatedException(
					"URI does not contain expected prefix '$prefix': $uri");
		}
	}
	
	private static function parsePathQuery($uri, &$path, &$query) {
		if (strstr($uri, '?')) {
			list($path, $query) = explode('?', $uri);
		} else {
			$path = $uri;
			$query = null;
		}
	}
	
	private static function parseQueryParams($query) {
		$params = array();
		foreach (explode('&', $query) as $pair) {
			$members = explode('=', $pair);
			$params[$members[0]] = isset($members[1]) ? $members[1] : null;
		}
		return $params;
	}
	
}
