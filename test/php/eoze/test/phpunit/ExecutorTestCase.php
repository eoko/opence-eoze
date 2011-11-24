<?php

namespace eoze\test\phpunit;

use eoko\module\ModuleManager;
use IllegalStateException;
use ReflectionProperty;

use eoko\module\Module;
use eoko\module\Module\executor\Executor;
use Request;
use ExtJSResponse;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 23 nov. 2011
 */
class ExecutorTestCase extends ModuleTestCase {
	
	/**
	 * @var eoko\module\executor\Executor
	 */
	protected $executor;
	protected $executorType;
	
	/**
	 * @var eoko\module\Module
	 */
	protected $module;
	protected $moduleName;
	
	protected $controller;
	
	public function __construct() {
		if (isset($this->controller)) {
			switch (count($parts = explode('.', $this->controller))) {
				case 2:
					if (!$this->executorType) {
						$this->executorType = $parts[1];
					}
				case 1:
					if (!$this->moduleName) {
						$this->moduleName = $parts[0];
					}
					break;
				default:
					throw new IllegalStateException('Invalid controller: ' . $this->controller);
			}
		}
	}
	
	protected function setUp() {
		parent::setUp();
		$this->module = $this->getModule();
	}
	
	protected function getModuleName() {
		if (isset($this->moduleName)) {
			return $this->moduleName;
		}
		$p = new ReflectionProperty($this, 'module');
		$comment = $p->getDocComment();
		if ($comment && preg_match('/@var\s+(?P<var>.+)$/m', $comment, $matches)) {
			$var = $matches['var'];
			if ($var !== 'eoko\module\Module') { // This class' own hinting
				if (preg_match('/\\\\modules\\\\(?P<module>[^\\\\]+)$/', $matches['var'], $matches)) {
					return $this->moduleName = $matches['module'];
				}
			}
		} else if (preg_match('/\\\\modules\\\\(?P<module>[^\\\\]+)\\\\/', get_class($this), $matches)) {
			return $this->moduleName = $matches['module'];
		}
		throw new IllegalStateException('Cannot determine module name');
	}
	
	protected function getExecutorType() {
		if (isset($this->executorType)) {
			return $this->executorType;
		}
		if (isset($this->controller)) {
			$parts = explode('.', $this->controller);
			return $this->executorType = $parts[1];
		}
		$p = new ReflectionProperty($this, 'executor');
		$comment = $p->getDocComment();
		if ($comment 
				&& preg_match('/@var\s+(?P<var>.+)$/m', $comment, $matches)) {
			$var = $matches['var'];
			if ($var !== 'eoko\module\executor\Executor') { // This class' own hinting
				if (preg_match('/(?:^|\\\\)(?P<executor>[^\\\\]+?)(?:Executor)?$/', 
						$var, $matches)) {
					return $this->executorType = strtolower($matches['executor']);
				} else {
					throw new IllegalStateException('Cannot determine executor type for class: '
							. $var);
				}
			}
		}
		throw new IllegalStateException('Cannot determine executor type');
	}
	
	protected function getControllerName() {
		if (!$this->controller) {
			return $this->controller = $this->getModuleName() . '.' . $this->getExecutorType();
		} else {
			return $this->controller;
		}
	}
	
	/**
	 *
	 * @return Module
	 */
	protected function getModule() {
		return ModuleManager::getModule($this->getModuleName());
	}
	
	/**
	 * @param string  $action
	 * @param Request $request
	 * @param bool    $internal
	 * 
	 * @return Executor
	 */
	protected function createExecutor($action = null, Request $request = null, $internal = false) {
		return $this->getModule()->createExecutor(
				$this->getExecutorType(), $action, $request, $internal);
	}
	
	/**
	 * Runs the given request on the Executor tested by this test case.
	 * 
	 * If the controller param of the request array is not set, it will be set
	 * automatically.
	 * 
	 * @param array $request
	 * @param bool $autoController 
	 * 
	 * @return array Request result data
	 */
	protected function runRequest(array $request) {
		$request  = new \Request($request);
		$executor = Module::parseRequestAction($request);

		ExtJSResponse::purge();
		$executor();
		
		return $executor->getData();
	}
}
