<?php

namespace eoze\test\phpunit;

use PHP_Timer as Timer;
use PHPUnit_Framework_Test as Test;
use PHPUnit_Framework_Assert as Assert;
use PHPUnit_Framework_TestResult as TestResult;
use PHPUnit_Framework_AssertionFailedError as AssertionFailedError;

use Exception;

use ReflectionProperty;
use ReflectionClass;
use IllegalStateException;
use Request;
use ExtJSResponse;

use UserSession;
use eoko\security\LoginAdapter\DummyAdapter as DummyLoginAdapter;

use eoko\output\Output;
use eoko\output\Adapter\NullAdapter;

use eoko\module\Module;
use eoko\module\ModuleManager;

use eoko\util\YmlReader;
use eoko\util\Arrays;
use eoze\test\phpunit\ArrayValidator;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 24 nov. 2011
 */
class ExecutorRequestTest extends Assert implements Test {
	
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

	protected $username = 'test';
	protected $password = 'test';
	
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
			if ($var !== 'eoko\module\executor\Executor' // This class' own hinting
					&& preg_match('/(?:^|\\\\)(?P<executor>[^\\\\]+?)(?:Executor)?$/', $var, $matches)) {
				return $this->executorType = strtolower($matches['executor']);
			}
		}
		return null;
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
		$request  = new Request($request);
		$executor = Module::parseRequestAction($request);

		ExtJSResponse::purge();
		$executor();
		
		return $executor->getData();
	}
	
	protected function getTestList() {
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
	
	public function count() {
		return 1;
	}
	
	protected function doTestRequest($file, $name, $test) {
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
		if (isset($test['expected-format'])) {
			$format = $test['expected-format'];
		} else if (isset($test['format'])) {
			$format = $test['format'];
		}
		
		if (isset($format)) {
			$validator = new ArrayValidator($format);
			$success = $validator->test($result);
			
			$this->assertTrue($success, $validator->getLastError(),
					'Asserting that result match expected template');
		}
	}
	
	public function run(TestResult $result = NULL) {
		
		if ($result === NULL) {
			$result = new TestResult;
		}

		UserSession::setLoginAdapter(new DummyLoginAdapter);
		UserSession::login($this->username, $this->password);
		
		Output::setAdapter(new NullAdapter);
		
		foreach ($this->getTestList() as $file => $data) {
			foreach ($data as $name => $test) {
				$result->startTest($this);
				Timer::start();

				try {
					$this->doTestRequest($file, $name, $test);
				} catch (AssertionFailedError $e) {
					$result->addFailure($this, $e, Timer::stop());
				} catch (Exception $e) {
					$result->addError($this, $e, Timer::stop());
				}

				$result->endTest($this, Timer::stop());
			}
		}

		return $result;
	}
}
