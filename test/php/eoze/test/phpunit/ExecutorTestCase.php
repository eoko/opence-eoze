<?php

namespace eoze\test\phpunit;

use eoko\module\ModuleManager;
use IllegalStateException;

use ReflectionProperty;
use ReflectionClass;

use eoko\module\Module;
use eoko\module\Module\executor\Executor;
use Request;
use ExtJSResponse;
use eoko\util\YmlReader;

use PHP_Timer;

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
	
	protected $testRequests = true;
	
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
	
	private function loadRequestFile($file) {
		$dataSet = array();
		$data = YmlReader::loadFile($file);
		foreach ($data as $name => $test) {
			if (!isset($test['request']['controller'])) {
				$test['request']['controller'] = $this->getControllerName();
			}
			$dataSet[$name] = $test;
		}
		return $dataSet;
	}
	
	private $result;
	
	public function run(\PHPUnit_Framework_TestResult $result = null) {
		if ($result === null) {
			$result = new \PHPUnit_Framework_TestResult;
		}
		$this->result = $result;
		
		// Find if there are other test methods
		$theClass = new ReflectionClass($this);
        foreach ($theClass->getMethods() as $method) {
            if (strpos($method->getDeclaringClass()->getName(), 'PHPUnit_') !== 0) {
				if ($method->getName() !== 'testRequests'
						&& \PHPUnit_Framework_TestSuite::isTestMethod($method)) {
					return parent::run($result);
				}
            }
        }
		
		$this->testRequests();
		
		return $this->result;
	}		

	public function testRequests() {
		
		$class = new ReflectionClass($this);
		
		$tests = array();
		
		// Load ./ClassName.requests.yml file
		if (file_exists($file = dirname($class->getFileName()) 
				. '/' . get_relative_classname($this) . '.requests.yml')) {
			
			$tests[get_relative_classname($this)] = $this->loadRequestFile($file);
		}

		$requestDir = dirname($class->getFileName()) . DS . get_relative_classname($this) 
				. '.requests';

		// Load ./ClassName.requests/*.yml files
		if (file_exists($requestDir)) {
			foreach (glob($requestDir . '/*.yml') as $file) {
				$tests[substr(basename($file), 0, -4)] = $this->loadRequestFile($file);
			}
		}

		// Run
		foreach ($tests as $file => $data) {
			foreach ($data as $name => $test) {
				$this->result->startTest($this);
				PHP_Timer::start();
				
				try {
					$this->doTestRequest($file, $name, $test);
				}
				catch (\PHPUnit_Framework_AssertionFailedError $ex) {
					$this->result->addFailure($this, $e, PHP_Timer::stop());
				}
				catch (\Exception $e) {
					$this->result->addError($this, $e, PHP_Timer::stop());
				}
				
				$this->result->endTest($this, PHP_Timer::stop());
			}
		}
	}
	
	protected function doTestRequest($file, $name, $test) {
		if (!isset($test['request'])) {
			throw new IllegalStateException("Missing request in data set $name of file $file");
		}
		if (!isset($test['expected'])) {
			throw new IllegalStateException("Missing expected restul in data set $name of file $file");
		}
		$this->assertSame(
			$test['expected'],
			$this->runRequest($test['request'])
		);
	}
	
//	public function testRequests() {
//		
//		$dataSet = array();
//
//		$class = new \ReflectionClass($this);
//		
//		$result = $this->getResult();
////		assert($result instanceof \PHPUnit_Framework_Result);
//		
//		if (file_exists($file = dirname($class->getFileName()) 
//				. '/' . get_relative_classname($this) . '.requests')) {
//			foreach ($this->loadRequestFile($file) as $name => $file) {
//				$result->startTest($this);
//				PHP_Timer::start();
//
//				$result->endTest($this, PHP_Timer::stop());
//			}
//		}
//
////		$requestDir = dirname($class->getFileName()) . DS . get_relative_classname($this) 
////				. '.requests';
////
////		if (file_exists($requestDir)) {
////			foreach (glob($requestDir . '/*.yml') as $file) {
////				$dataSet += $this->loadRequestFile($file);
////			}
////		}
////		
////		dump($dataSet);;
//	}
}
