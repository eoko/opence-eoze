<?php

namespace eoko\module\executor;

use eoko\module\Module;
use eoko\module\ModuleResolver;

use eoko\url\Maker as UrlMaker;
use eoko\util\Files as FileHelper;
use SecurityException, IllegalStateException;
use eoko\file;
use eoko\util\Arrays;
use eoko\config\Config;
use Request;
use Logger;
use Zend\Http\Response;

use Zend\Mvc\Router\RouteStackInterface;

const ACTION_CANCELLED = -1;

/**
 * @internal This function is externalized from the {@link Executor} class, to
 * limit its access to the Executor's public methods.
 * @param Executor $executor
 * @param $action
 * @throws \IllegalStateException
 * @return
 */
function execute(Executor $executor, $action) {
	if (method_exists($executor, $action)) {
		return $executor->$action();
	} else if ($executor->executeAction($action, $returnValue)) {
		return $returnValue;
	} else {
		throw new IllegalStateException("Executor $executor has no action $action");
	}
}

abstract class Executor implements file\Finder {

	/** @var Module */
	public $module;
	/**
	 * @var Request
	 * @deprecated Use {@link getRequest()} instead.
	 */
	protected $request;

	/**
	 * @var RouteStackInterface
	 */
	private $router;

	public $name;

	private $action;
	private $actionMethod;

	/** @var file\Finder */
	private $fileFinder = null;

	private $cancelled = false;
	private $executed = false;
	private $forwardResult = null;

	protected $actionParam = 'action';
	protected $defaultAction = 'index';

	public final function __construct(Module $module, $name, $internal, $action, Request $request = null) {

		$this->module = $module;
		$this->name = $name;
		$this->request = $request !== null ? $request : new Request(array());
		$this->action = $action !== null ? $action : $this->getDefaultAction();

		if (substr($this->action, 0, 1) == '_') {
			throw new SecurityException('Litigious action: ' . $action);
		}

		if ($this instanceof InternalExecutor) {
			if (!$internal) {
				throw new SecurityException('Request is not allowed to trigger internal actions');
			}
			$this->actionMethod = $this->action;
		} else {
			if ($internal) {
				$this->actionMethod = "_$this->action";
			} else {
			$this->actionMethod = $this->action;
			}
		}

		$this->construct();
	}

	protected function construct() {}

	/**
	 * @param RouteStackInterface $router
	 * @return Executor
	 */
	public function setRouter(RouteStackInterface $router) {
		$this->router = $router;
		return $this;
	}

	/**
	 * @return RouteStackInterface
	 */
	protected function getRouter() {
		return $this->router;
	}

	/**
	 * @var Response
	 */
	private $response = null;

	/**
	 * @return Response
	 */
	protected function getResponse() {
		if (!$this->response) {
			$this->response = new \Zend\Http\PhpEnvironment\Response();
		}
		return $this->response;
	}

	/**
	 * @return Request
	 */
	protected function getRequest() {
		return $this->request;
	}

	private function getDefaultAction() {
		return $this->request->get($this->actionParam, $this->defaultAction);
	}

	/**
	 * Execute the action with name $name.
	 * @param $name
	 * @param mixed $returnValue A variable that will be set to the action return
	 * value, if the action is executed by this executor.
	 * @return bool true if the action was executed, else false.
	 */
	public function executeAction($name, &$returnValue) {
		return false;
	}

	/**
	 * Returns the qualified name of the executor, that is:
	 *
	 * controllerName.executorSuffix
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->name;
	}

	public function getControllerString() {
		return "$this->module.$this";
	}

	/**
	 * @return Module
	 * @deprecated
	 * @todo Track usage
	 */
	public function getModule() {
		return $this->module;
	}

	/**
	 * @return Config
	 */
	public function getModuleConfig() {
		return $this->module->getConfig();
	}

	/**
	 * @return \eoko\config\Application
	 */
	public function getApplication() {
		return $this->module->getApplication();
	}

	/**
	 * Gets the Logger for this executor's context.
	 * @return Logger
	 */
	protected function getLogger() {
		return Logger::get(get_class($this));
	}

	/**
	 * @deprecated
	 * @todo Track usage
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param bool $return
	 * @return \Zend\Http\Response|null
	 * @throws \IllegalStateException
	 * @throws \Exception
	 */
	public final function __invoke($return = false) {

		if ($this->executed) {
			throw new IllegalStateException('Already executed');
		}

		Logger::get($this)->debug(
			'Executing {}.{}->{}',
			$this->module, $this->name, $this->action
		);

		if ($this->beforeAction() === false) {
			$this->cancelled = true;
			$this->executed = true;
			return null;
		}

		try {
			$result = $this->doInvoke();
		} catch (\Exception $ex) {
			$m = 'handle' . get_relative_classname($ex);
			if (method_exists($this, $m)) {
				$result = $this->$m($ex);
			} else {
				throw $ex;
			}
		}

		$this->executed = true;

		// Forwarded
		if ($this->forwardResult) {
			return $this->forwardResult;
		}

		if ($this->cancelled) {
			return null;
		}

		$this->afterAction();

		return $this->processResult($result, $return);
	}

	private function doInvoke() {
		return execute($this, $this->actionMethod);
	}

//	public final function _beforeAction($action) {
//
//		if ($this->action !== null) {
//			throw new IllegalStateException('Action execution collision');
//		}
//
//		$this->action = $action;
//
//		return $this->beforeAction($action);
//	}
//
//	public final function _afterAction($action, $result) {
//
//		// do nothing if the action has been cancelled
//		if ($this->cancelled) {
//			return;
//		}
//
//		$r = $this->processResult($result);
//
//		$this->afterAction($action);
//
//		return $r;
//	}

	private function cancel() {

		if ($this->cancelled) {
			throw new IllegalStateException('Already cancelled!');
		}

		$this->cancelled = true;
	}

//	protected function setupAction() {}
//	protected function cleanupAction() {}

	protected function afterAction() {}

	protected function beforeAction() {}

	/**
	 * @param mixed $result
	 * @return Response|null
	 */
	protected function processResult($result) {
		if ($result instanceof Response) {
			return $result;
		} else {
			return null;
		}
	}

	/**
	 * Get the name of the action currently being executed. If this method is
	 * called before or after an action is actually executed, an exception will
	 * be thrown.
	 * @return string
	 * @throws IllegalStateException if this method is called outside of the
	 * action execution process, or when the action has been cancelled
	 */
	protected function getAction() {
		if ($this->action === null) {
			throw new IllegalStateException('No action set');
		} else if ($this->cancelled) {
			throw new IllegalStateException('Action has been cancelled');
		} else {
			return $this->action;
		}
	}

	protected function isAction($action) {
		return $this->getAction() === $action;
	}

	protected function forward($controller, $action = null, $overrideRequest = null) {

		$this->cancel();

		$overrideRequest = Arrays::applyIf($overrideRequest, array(
			'action' => $action,
		));

		$this->request->override($overrideRequest);
		$this->request->remove('module', 'executor', 'controller');

		$action = ModuleResolver::parseAction($controller, $action, $this->request, false);

		if ($action instanceof Executor) {
			$action->setRouter($this->getRouter());
		}

		$this->forwardResult = $action();

		return $this->forwardResult;
	}

	public function redirectTo($url) {
		$this->cancel();
		header('Location: ' . UrlMaker::makeAbsolute($url));
	}

	/**
	 * @return file\Finder
	 */
	protected function getFileFinder() {
		if ($this->fileFinder === null) {
			$this->fileFinder = new file\ObjectFinder(
				$this, array(
					'forbidUpwardResolution' => true,
					'finderFnName' => '_getFileFinderFor%s',
				),
				$this->module
			);
		}
		return $this->fileFinder;
	}

	public function searchPath($name, $type = null, &$getUrl = false, $forbidUpward = null, $require = false) {
		return $this->getFileFinder()->searchPath($name, $type, $getUrl, $forbidUpward, $require);
	}

	public function findPath($name, $type = null, &$getUrl = false, $forbidUpward = null) {
		return $this->getFileFinder()->findPath($name, $type, $getUrl, $forbidUpward);
	}

	public function resolveRelativePath($relativePath, $type = null, $forbidUpward = null) {
		return $this->getFileFinder()->resolveRelativePath($relativePath, $type, $forbidUpward);
	}
}
