<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 * @license http://www.planysphere.fr/licenses/psopence.txt
 */

use eoko\config\ConfigManager;
use eoko\module\ModuleManager;
use eoko\module\Module;
use eoko\util\Arrays;

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

	const CONFIG_NODE = 'eoko/router';

	/** @var Router */
	private static $instance = null;

//	const ROOT_MODULE_NAME = 'root';
//	protected $rootModuleName = 'root';

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
		
		$request = $_REQUEST;
		
		if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' 
				&& $_SERVER['CONTENT_TYPE'] === 'application/json' || isset($_GET['contentType']) 
				&& preg_match('/(?:^|\/)json$/i', $_GET['contentType'])) {
		
			Arrays::apply($request, json_decode(file_get_contents("php://input"), true));
			
			unset($request['contentType']);
		}

		$this->microTimeStart = self::microtime($time);
		$this->actionTimestamp = $time;
		
		if (isset($request['route'])) {
			\eoko\url\Maker::populateRouteRequest($request);
		}
		
		$this->request = new Request($request);
		
		Logger::getLogger($this)->info('Start action #{}', $this->actionTimestamp);
		
		if (class_exists('MonitorRequest') 
				&& $this->request->get('controller') !== 'AccessControl.login') {
			$this->requestMonitorRecord = MonitorRequest::create(array(
				'datetime' => date('Y-m-d H:i:s', $time),
				'action_timestamp' => $this->actionTimestamp,
				'http_request' => serialize($request),
				'json_request' => json_encode($request),
				'php_request' => serialize($this->request),
				'controller' => $this->request->get('controller'),
				'action' => $this->request->get('action'),
			));
			$this->requestMonitorRecord->save();
		}
		
		// $_REQUEST usage must be fixed in that
//		UserMessageService::parseRequest($this->request);
	}

	public static function getActionTimestamp() {
		return self::getInstance()->actionTimestamp;
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
        
		if (!$this->request->has('controller')) {
			$this->request->override(
				'controller',
				ConfigManager::get(self::CONFIG_NODE, 'indexModule')
//				defined('APP_INDEX_MODULE') ? APP_INDEX_MODULE : self::ROOT_MODULE_NAME
			);
		}

		try {
			$action = Module::parseRequestAction($this->request);
			$action();

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
		
//		if (($controller = $this->getController()) !== null) {
//
//			if (($action = $this->getAction()) !== null) {
//				$this->executeAction($controller, $action);
//			} else {
//				$this->executeAction($controller);
//			}
//
//		} else {
//			// Routing is done after it has been checked that user is logged
////			throw new SystemException('Module info absent from request', lang('Module introuvable'));
//			Logger::getLogger()->warn('No routing information available => reloading application');
//			$this->executeAction(self::ROOT_MODULE_NAME);
//		}
	}

	private function getController() {
		if (false !== $key = $this->request->hasAny(array('controller', 'module', 'mod'), true)) {
			if ($key != 'controller') {
				Logger::getLogger($this)->warn('"module" used instead of "controller" in request');
			}
			$controller = $this->request->getRaw($key);
		} else {
			$controller = defined('APP_INDEX_MODULE') ? 
				APP_INDEX_MODULE : self::ROOT_MODULE_NAME;
		}
		
		Logger::getLogger('router')->debug('Controller is: {}', $controller);
		return $controller;
	}

	private function getAction() {
		$action = $this->request->getFirst(array('action', 'act'), 'index', true);
		Logger::getLogger('router')->debug('Action is: {}', $action);
		return $action;
	}

	/**
	 * Force the routing to the 'login' action of the root module, that is the
	 * action that process login requests.
	 */
	public function executeLogin() {

		$module = $this->getController();
		$action = $this->getAction();

		if ($module !== 'root' && $action !== 'login') {
			Logger::getLogger('Router')->warn('Forcing routing to Login module.'
					. 'Request params are: module={} action={}', $module, $action);
		}

		$this->executeAction('root', 'login');
	}

	/**
	 * Force the routing to the 'get_login_page' action of the 'root' module,
	 * that is the action which presents the user with the page where they can
	 * log in.
	 */
	public function loadLoginPage() {

		$module = $this->getController();
		$action = $this->getAction();

		if ($module !== 'root' && $action !== 'get_login_module') {
			Logger::getLogger('Router')->warn('Forcing routing to Login module.'
					. 'Request params are: module={} action={}', $module, $action);
		}

		$this->executeAction(self::ROOT_MODULE_NAME, 'get_login_module');
	}

	/**
	 * Execute the given action of the specified module.
	 * @param String $module name of the module
	 * @param String $action name of the action
	 */
	public function executeAction($module, $action = 'index', $request = null) {

		if ($request !== null) {
			$request = is_array($request) ? new Request($request) : $request;
		} else {
			$request = $this->request;
		}

		if (preg_match('/^([^.]+)\.(.+)$/', $module, $m)) {
			$module = $m[1];
			$this->request->override('executor', $m[2]);
		}

		ModuleManager::getModule($module)->executeRequest($request);
		
//REM		$module = ModuleManager::createController($module, $action, $request);
//		
//		if ($module instanceof eoko\module\Module) {
//			$module->execute($request);
//		} else {
//			// Execute action
//			$module->beforeAction($action);
//
//	//		if (is_bool($r = $controller->$action())) {
//			if (is_bool($r = $module->execute($action))) {
//				if ($r) {
//					ExtJSResponse::answer();
//				} else {
//					ExtJSResponse::failure();
//				}
//			} else if ($r instanceof TemplateHtml) {
//				$module->engine->processHtmlFragment($r);
//			}
//		}
	}
	
}
