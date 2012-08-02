<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 * @license http://www.planysphere.fr/licenses/psopence.txt
 */

use eoko\config\ConfigManager;
use eoko\module\ModuleManager;
use eoko\module\traits\HasRoutes;
use eoko\cache\Cache;
use Zend\Http\PhpEnvironment\Request as HttpRequest;

use \MonitorRequest;

if (!isset($GLOBALS['directAccess'])) { header('HTTP/1.0 404 Not Found'); exit('Not found'); }

/**
 * Route HTTP requests to the correct action of the correct module.
 *
 * Two parameters are expected in the request's data (either POST or GET):<ul>
 * <li>'mod' or 'module' specifying the module
 * <li>'act' or 'action' sepcifying the action
 * </ul>
 *
 * If no module is specified, then the request will be routed to the 'root'
 * module. If no action is specified, then the 'index' action of the specified
 * module will be used.
 */
class Router {

	private $defaultRequestReaderClass = 'eoko\mvc\LegacyRequestReader';
	private $defaultRouterClass = 'eoko\mvc\LegacyRouter';

	/** @var Router */
	private static $instance = null;
	
	/**
	 * @var HttpRequest
	 */
	private $httpRequest;
	
	/**
	 * @var Zend\Mvc\Router\RouteMatch
	 */
	private $routeMatch;

	public $request;
	public $actionTimestamp;
	
	private $microTimeStart;
	/** @var MonitorRequest */
	private $requestMonitorRecord;

	/** @var int */
	private $routeCallCount = 0;
	
	/**
	 * @return Router
	 */
	public static function getInstance() {
		if (self::$instance === null) self::$instance = new Router();
		return self::$instance;
	}

	private function __construct() {

		// Debug infos
		$this->microTimeStart = self::microtime($time);
		$this->actionTimestamp = $time;
		
		ExtJSResponse::put('timestamp', $this->actionTimestamp);

		// HTTP request
		$this->httpRequest = new HttpRequest();
		
		// Route match
		$this->routeMatch = $this->getRouteMatch();
		
		// Legacy eoze Request
		$requestReader = $this->createRequestReader();
		$this->request = $requestReader->createRequest();

		// Monitor
		Logger::getLogger($this)->info('Start action #{}', $this->actionTimestamp);
		$this->logRequest($this->request->toArray());
		
		// $_REQUEST usage must be fixed in that
//		UserMessageService::parseRequest($this->request);
	}
	
	/**
	 * @return Zend\Mvc\Router\RouteMatch
	 */
	private function getRouteMatch() {
		
		$routesConfigCacheKey = get_class($this) . '-RoutesConfig';

		// Retrieve route stack config from cache, or build it from modules
		if (!($routesConfig = Cache::getCachedData($routesConfigCacheKey))) {
			$routesConfig = array();
			$monitors = array();
			foreach (ModuleManager::listModules() as $module) {
				if ($module instanceof HasRoutes) {
					if (null !== $routes = $module->getRoutesConfig()) {
						$routesConfig[] = $routes;
					}
				}
				$monitors = array_merge($monitors, $module->getCacheMonitorFiles());
			}
			Cache::cacheObject($routesConfigCacheKey, $routesConfig);
			Cache::monitorFiles($routesConfigCacheKey, $monitors);
		}
		
		// Create route stack
		$stack = new Zend\Mvc\Router\Http\TreeRouteStack;
		foreach ($routesConfig as $routes) {
			$stack->addRoutes($routes);
		}
		
		return $stack->match($this->httpRequest);
	}
	
	/**
	 * @return eoko\mvc\RequestReader
	 */
	private function createRequestReader() {
		
		$readerClass = $this->routeMatch !== null
				? $this->routeMatch->getParam('_RequestReader', $this->defaultRequestReaderClass)
				: $this->defaultRequestReaderClass;
		
		if (!class_exists($readerClass)) {
			throw new IllegalStateException('Cannot find reader class: ' . $readerClass);
		}

		if (!array_key_exists('eoko\mvc\RequestReader', class_implements($readerClass))) {
			throw new IllegalStateException('Illegal reader class: ' . $readerClass);
		}

		return new $readerClass($this->httpRequest);
	}
	
	private function createRouter() {
		
		$routerClass = $this->routeMatch !== null
			? $this->routeMatch->getParam('_Router', $this->defaultRouterClass)
			: $this->defaultRouterClass;
		
		if (!class_exists($routerClass)) {
			throw new IllegalStateException('Cannot find router class: ' . $routerClass);
		}

		return new $routerClass($this->request, $this->routeMatch);
	}
	
	private function logRequest($requestData) {
		
		if (!class_exists('MonitorRequest')) {
			return;
		}
		
		$phpRequest = $this->request;
		$controller = $this->request->get('controller');
		
		if (substr($controller, 0, 14) === 'RequestMonitor') {
			return;
		}
		
		// Don't store clear passwords...
		if ($controller === 'AccessControl.login') {
			$requestData['password'] = '***';
			$phpRequest = new Request($requestData);
		}
		
		$this->requestMonitorRecord = MonitorRequest::create(array(
			'datetime' => date('Y-m-d H:i:s', $this->actionTimestamp),
			'action_timestamp' => $this->actionTimestamp,
			'http_request' => serialize($requestData),
			'json_request' => json_encode($requestData),
			'php_request' => serialize($phpRequest),
			'controller' => $controller,
			'action' => $phpRequest->get('action'),
		));
		
		$this->requestMonitorRecord->save();

		ExtJSResponse::put('requestId', $this->requestMonitorRecord->getId());
	}

	public static function getActionTimestamp() {
		return self::getInstance()->actionTimestamp;
	}
	
	public static function getRequestId() {
		if (null !== $record = self::getInstance()->requestMonitorRecord) {
			return $record->getId();
		} else {
			return null;
		}
	}
	
	private function isAllowMultipleRouteCalls() {
		return ConfigManager::get('eoko/router', 'allowMultipleCalls', false);
	}
	
	private function testMultipleRouteCall() {
		if ($this->routeCallCount === 0 || $this->isAllowMultipleRouteCalls()) {
			$this->routeCallCount++;
		} else {
			throw new IllegalStateException('Forbidden multiple route calls!'
					. ' See config node: eoko\\router\\allowMultipleCalls.');
		}
	}
	
	private static function microtime(&$time = null) {
		list($µ, $time) = explode(' ', microtime());
		return (int) ($time . substr($µ, 2, 6));
	}

	/**
	 * Examines the request's params and route to the appropriate action.
	 * @see Router
	 */
	public function route() {
		
		$this->testMultipleRouteCall();
		
		// PHP error converted to exceptions will bypass the try/catch block
		if ($this->requestMonitorRecord) {
			eoko\php\ErrorException::onError(array($this, 'onRequestError'));
		}

		try {
			$this->createRouter()->route();

			if ($this->requestMonitorRecord) {
				$microtime = self::microtime($time);
				$runningTime = $microtime - $this->microTimeStart;
				$this->requestMonitorRecord->setFinishState('OK');
				$this->requestMonitorRecord->setFinishDatetime(date('Y-m-d H:i:s'), $time);
				$this->requestMonitorRecord->setRunningTimeMicro($runningTime);
				$this->requestMonitorRecord->save();
			}
		} catch (Exception $ex) {
			if ($this->requestMonitorRecord) {
				$microtime = self::microtime($time);
				$runningTime = $microtime - $this->microTimeStart;
				$this->requestMonitorRecord->setFinishState("$ex");
				$this->requestMonitorRecord->setFinishDatetime(date('Y-m-d H:i:s'), $time);
				$this->requestMonitorRecord->setRunningTimeMicro($runningTime);
				$this->requestMonitorRecord->save();
			}
			throw $ex;
		}
	}
	
	public function onRequestError($ex) {
		$microtime = self::microtime($time);
		$runningTime = $microtime - $this->microTimeStart;
		$this->requestMonitorRecord->setFinishState("$ex");
		$this->requestMonitorRecord->setFinishDatetime(date('Y-m-d H:i:s'), $time);
		$this->requestMonitorRecord->setRunningTimeMicro($runningTime);
		$this->requestMonitorRecord->save();
	}
}
