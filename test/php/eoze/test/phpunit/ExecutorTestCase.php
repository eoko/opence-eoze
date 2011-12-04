<?php

namespace eoze\test\phpunit;

use eoko\module\ModuleManager;
use IllegalStateException;

use ReflectionProperty;
use ReflectionClass;

use eoko\module\Module;
use eoko\module\Module\executor\Executor;
use eoko\util\YmlReader;
use eoko\util\Arrays;
use eoze\test\phpunit\ArrayValidator;

use Request;
use ExtJSResponse;

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
	
	/**
	 * `true` to dump the result array in the failure message when schema validation fails.
	 * @var bool
	 */
	protected $dumpResult = false;
	
    public function __construct($name = NULL, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
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
		return null;
//		throw new IllegalStateException('Cannot determine executor type');
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
		
		if (!array_key_exists('controller', $request)) {
			$request['controller'] = $this->getControllerName();
		}
		
		$request  = new \Request($request);
		$executor = Module::parseRequestAction($request);

		ExtJSResponse::purge();
		$executor();
		
		return $executor->getData();
	}
	
	protected function getRequestTestList() {
		$class = new ReflectionClass($this);
		
		$tests = array();
		
		// Load ./ClassName.requests.yml file
		if (file_exists($file = dirname($class->getFileName()) 
				. '/' . get_relative_classname($this) . '.requests.yml')) {
			
			$tests[get_relative_classname($this)] = YmlReader::loadFile($file);
		}

		$requestDir = dirname($class->getFileName()) . DS . get_relative_classname($this) 
				. '.requests';

		// Load ./ClassName.requests/*.yml files
		if (file_exists($requestDir)) {
			foreach (glob($requestDir . '/*.yml') as $file) {
				$tests[substr(basename($file), 0, -4)] = YmlReader::loadFile($file);
			}
		}
		
		return $tests;
	}
	
	public function requestsTestProvider() {
		// Run
		$return = array();
		foreach ($this->getRequestTestList() as $file => $data) {
			foreach ($data as $name => $test) {
				$return["$file > $name"] = array(
					$file,
					$name,
					$test
				);
			}
		}
		return $return;
	}

	/**
	 * @dataProvider requestsTestProvider
	 */
	public function testRequests($file, $name, $test) {
		
		if (!isset($test['request'])) {
			throw new IllegalStateException("Missing request in data set $name of file $file");
		}
		
		// Shortcut form 'request' => $action
		if (is_string($test['request'])) {
			$test['request'] = array(
				'action' => $test['request']
			);
		}
		
		// Automatic controller
		if (!isset($test['request']['controller'])) {
			$test['request']['controller'] = $this->getControllerName();
		}
			
		$result = $this->runRequest($test['request']);
		
		if (isset($test['expected'])
				&& !Arrays::compareMap($test['expected'], $result)) {
			
			$this->assertEquals($test['expected'], $result, 
					'Asserting that result match expected');
		}
		
		$format = null;
		if (isset($test['expectedSchema'])) {
			$format = $test['expectedFormat'];
		} else if (isset($test['schema'])) {
			$format = $test['schema'];
		} else if (isset($test['responseSchema'])) {
			$format = $test['responseSchema'];
		} else if (isset($test['expectedFormat'])) {
			$format = $test['expectedFormat'];
		} else if (isset($test['format'])) {
			$format = $test['format'];
		}
		
		if (isset($format)) {
			$validator = new ArrayValidator($format);
			if (!$validator->test($result)) {
				$this->fail(
					'Result validation failed: ' . $validator->getLastError()
					. ($this->dumpResult ? PHP_EOL . print_r($result, true) : '')
				);
			}
		}
		
		if (isset($test['after'])) {
			$method = $test['after'];
			$this->$method($result);
		}
	}
}
