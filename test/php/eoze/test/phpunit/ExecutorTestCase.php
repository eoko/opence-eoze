<?php

namespace eoze\test\phpunit;

//require_once __DIR__ . '/ModuleTestCase.php';

use eoko\module\ModuleManager;
use IllegalStateException;
use ReflectionProperty;

use eoko\module\Module;
use eoko\module\Module\executor\Executor;
use Request;

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
	
	protected function setUp() {
		parent::setUp();
		$this->module = $this->createModule();
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
		throw new IllegalStateException('Cannot determine executor type');
	}
}
