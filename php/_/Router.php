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
use Zend\Mvc\Router\RouteStackInterface;
use Zend\Mvc\Router\RouteMatch;
use Zend\Stdlib\ArrayUtils;

use \MonitorRequest;

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
	 * @var RouteMatch
	 */
	private $routeMatch;

	/**
	 * @var Request
	 */
	public $request;
	public $actionTimestamp;

	private $microTimeStart;
	/** @var MonitorRequest */
	private $requestMonitorRecord;

	/** @var int */
	private $routeCallCount = 0;

	/**
	 * @var RouteStackInterface
	 */
	public $routeStack;

	/**
	 * @return Router
	 */
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new Router();
		}
		return self::$instance;
	}

	private function __construct() {

		// Debug infos
		$this->microTimeStart = self::microtime($time);
		$this->actionTimestamp = $time;

		ExtJSResponse::put('timestamp', $this->actionTimestamp);

		// TODO that should not happen in the constructor
		if (!defined('EOZE_AS_LIB') || !EOZE_AS_LIB) {
			// HTTP request
			$this->httpRequest = new HttpRequest();

			// Route match
			$this->routeMatch = $this->getRouteMatch();

			if ($this->routeMatch) {
				// Legacy eoze Request
				$requestReader = $this->createRequestReader();
				$this->request = $requestReader->createRequest();

				// Monitor
				Logger::getLogger($this)->info('Start action #{}', $this->actionTimestamp);
				$this->logRequest($this->request->toArray());
			}
		}
	}

	/**
	 * @return Zend\Mvc\Router\RouteMatch
	 */
	private function getRouteMatch() {

		$routesConfigCacheKey = get_class($this) . '-RoutesConfig';

		// Retrieve route stack config from cache, or build it from modules
		if (!($routesConfig = Cache::getCachedData($routesConfigCacheKey))) {

			$assembler = new Router_RouteConfigAssembler;

			$monitors = array();
			foreach (ModuleManager::listModules() as $module) {
				if ($module instanceof HasRoutes) {
					if (null !== $routes = $module->getRoutesConfig()) {
						$assembler->addRoutes($routes);
					}
				}
				$monitors = array_merge($monitors, $module->getCacheMonitorFiles());
			}

			$routesConfig = $assembler->assembleRoutes();

			Cache::cacheObject($routesConfigCacheKey, $routesConfig);
			Cache::monitorFiles($routesConfigCacheKey, $monitors);
		}

		// Create route stack
		$this->routeStack = new Zend\Mvc\Router\Http\TreeRouteStack;
		$this->routeStack->addRoutes($routesConfig);

		return $this->routeStack->match($this->httpRequest);
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

		return new $readerClass($this->httpRequest, $this->routeMatch);
	}

	private function createRouter() {
		if ($this->routeMatch) {
			$routerClass = $this->routeMatch !== null
				? $this->routeMatch->getParam('_Router', $this->defaultRouterClass)
				: $this->defaultRouterClass;

			if (!class_exists($routerClass)) {
				throw new IllegalStateException('Cannot find router class: ' . $routerClass);
			}

			return new $routerClass($this->request, $this->routeStack, $this->routeMatch);
		} else {
			return new \eoko\mvc\ErrorRouter(\Zend\Http\Response::STATUS_CODE_404);
		}
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
			'http_method' => $this->httpRequest->getMethod(),
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
		// We don't use getInstance() to avoid initialization only for the timestamp (which will fail
		// in most early crashes)
		return self::$instance
			? self::$instance->actionTimestamp
			: null;
	}

	public static function getRequestId() {
		if (self::$instance) {
			if (null !== $record = self::getInstance()->requestMonitorRecord) {
				return $record->getId();
			}
		}
		return null;
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

		// Log roll
		if ($this->requestMonitorRecord && $this->requestMonitorRecord->getId() % 100 === 0) {
			MonitorRequestTable::createQuery()
				->andWhere('(`finish_state` != "OK" AND `datetime` < DATE_SUB(NOW(), INTERVAL 14 DAY))'
				. 'OR (`finish_state` = "OK" AND `datetime` < DATE_SUB(NOW(), INTERVAL 2 DAY))')
				->executeDelete();
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
