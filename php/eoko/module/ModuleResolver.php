<?php

namespace eoko\module;

use Request;
use eoko\module\executor\Executor;
// Exceptions
use IllegalArgumentException;
use eoko\routing\InvalidRequestException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 16 oct. 2012
 */
class ModuleResolver {

	private function __construct() {}

	/**
	 * Parse {@link Module} and {@link Executor} names from the $controller. The
	 * names are assignated to the $module and $executor variables.
	 *
	 * The $controller can be either an already instanciated Module (in which
	 * case, the $executor name won't be found), or a string following the
	 * spcecification "moduleName.executorName" or "moduleName" only.
	 *
	 * The meaning of the second form, which doesn't specify the name of the
	 * Executor may vary depending on the situation it is used. In the context
	 * where the $controller was given as a Module name, the Executor will most
	 * oftenly not be precised; on the opposite, in the context where
	 * $controller was given for an Executor, that will mean the default
	 * Executor of the Module. In the later case, the notation "module." can be
	 * used to explicitely name the default Executor.
	 *
	 * @param Module           $controller
	 * @param string|Module    &$module
	 * @param string|Executor  &$executor
	 */
	private static function parseModule($controller, &$module, &$executor = null) {
		if ($controller instanceof Module) {
			$module = $controller;
			$executor = null;
		} else if ($controller instanceof Executor) {
			$module = $controller->getModule();
			$executor = $controller->name;
		} else if (is_string($controller)) {
			if (count($parts = explode('.', $controller, 2)) === 2) {
				$module = $parts[0];
				$executor = $parts[1];
			} else {
				$module = $parts[0];
				$executor = null;
			}
		} else if (is_array($controller)) {
			$module = isset($controller['module']) ? $controller['module'] : null;
			$executor = isset($controller['executor']) ? $controller['executor'] : null;
		} else {
			throw new IllegalArgumentException(
				"\$controller: eoko\\module\\Module|string|array (here: $controller)"
			);
		}
	}

// REM
// Unused
//	public static function parseModuleName($controller) {
//		self::parseModule($controller, $module);
//		if ($module instanceof Module) {
//			return $module->getName();
//		} else {
//			return $module;
//		}
//	}

// REM Unused
//	public static function parseInternalAction($controller, Request $request, $defaultExecutor = Module::DEFAULT_EXECUTOR) {
//
//		self::parseModule($controller, $module, $executor);
//
//		if (!($module instanceof Module)) {
//			$module = ModuleManager::getModule($module);
//		}
//
//		throw new IllegalStateException('Not implemented yet');
//
////		$module->getInternalExecutor($executor, $action, $opts, $fallbackExecutor)
//	}

	/**
	 * Creates the executor to serve the given $request, forcing the $controller
	 * (ie. Module and Executor type) and $action to be the ones specified.
	 * @param mixed $controller
	 * @param string $action
	 * @param Request $request
	 * @return Executor
	 */
	public static function parseAction($controller, $action, $request) {

		self::parseModule($controller, $module, $executor);
		// $module instanceof Module;

		if (!($module instanceof Module)) {
			$module = ModuleManager::getModule($module);
		}

		return $module->createExecutor($executor, $action, $request, false);
	}

	/**
	 * Parses the given request to extract the information to create the serving
	 * executor.
	 * @param Request $request
	 * @return Executor
	 */
	public static function parseRequestAction(Request $request) {

		self::parseModule($request->req('controller'), $module, $executor);

		$request->override(array(
			'module' => "$module",
			'executor' => "$executor",
		));

		if (!$module) {
			throw new InvalidRequestException(
				"Invalid controller information: {$request->req('controller')}"
			);
		}

		if (!($module instanceof Module)) {
			$module = ModuleManager::getModule($module);
		}

		return $module->createRequestExecutor($request, $executor);
	}

}
