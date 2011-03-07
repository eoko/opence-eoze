<?php

/**
 * Definitions:
 * <ul>
 * <li>Module plugin: </li>
 * <li>Module class:
 * eg. GridModule is a class of module.
 * </li>
 * <li>Module instance:
 * eg. membres is an instance of the module class GridModule.
 * </li>
 * </ul>
 */
class ModuleManager {
	
	private static $modules = null;

	private static function getLogger() {
		return Logger::getLogger('ModuleManager');
	}
	
	/**
	 *
	 * @param string $name
	 * @return Template
	 */
	public static function createModule($name, Config $config = null) {
		if ($config === null) $config = self::loadModuleConfig($name);
		$class = $config->class;
//		return $class::generateModule($name, $config); // php 5.3
		$tpl = call_user_func(array($class, 'generateModule'), $name, $config);

		$dir = CACHE_PATH . 'modules' . DS;
		if (!is_dir($dir)) mkdir($dir);
		$tpl->saveFile("$dir$name.mod.js");

		return $tpl;
	}

	public static function canGenerateController($name) {
		foreach (self::getModuleBasePaths() as $path) {
			if (
				file_exists($path . $name . DS . 'config.yml')
				|| file_exists($path . "$name.yml")
				|| file_exists($path . $name . DS . "$name.yml")
			) return true;
		}
		return false;
	}

	public static function loadModuleConfig($name, $require = true) {
		foreach (self::getModuleBasePaths() as $path) {
			if (
				file_exists($file = $path . "$name/config.yml")
				|| file_exists($file = $path . "$name.yml")
				|| file_exists($file = $path . $name . DS . "$name.yml")
			) {
				return Config::load($file, $name);
			}
		}

		if ($require) {
			throw new SystemException(
				'No config information available for controller: ' . $name
			);
		} else {
			return null;
		}
	}

	public static function getModulePath($name, $require = true, $ds = DS) {
		foreach (self::getModuleBasePaths() as $basePath) {
			if (is_dir($dir = $basePath . $name)) {
				return $dir . $ds;
			}
		}

		if ($require) {
			throw new IllegalStateException("Cannot find path for module $name");
		} else {
			return null;
		}
	}

	public static function getModulePathAndUrl($name, $require = true) {
		foreach (self::getModuleBasePathsAndUrls() as $basePath => $baseUrl) {
			if (is_dir($dir = $basePath . $name)) {
				return array(
					'path' => $dir . DS,
					'url' => "$baseUrl$name/"
				);
			}
		}

		if ($require) {
			throw new IllegalStateException("Cannot find path for module $name");
		} else {
			return null;
		}
	}

	/**
	 * Get module base paths, DS terminated.
	 * @return array[string]
	 */
	private static function getModuleBasePaths() {

		$paths = array();

		if (defined('APP_MODULES_PATH')) {
			$paths[] = APP_MODULES_PATH;
		}

		$paths[] = MODULES_PATH;

		return $paths;
	}

	/**
	 * Get module base paths, DS terminated.
	 * @return array[string]
	 */
	private static function getModuleBaseDirs() {

		$path = array();

		if (defined('APP_MODULES_PATH')) {
			$paths[APP_MODULES_PATH] = APP_MODULES_DIR;
		}

		$paths[MODULES_PATH] = MODULES_DIR;

		return $paths;
	}

	private static function getModuleBasePathsAndUrls() {

		$path = array();


		if (defined('APP_MODULES_PATH')) {
			$urls[APP_MODULES_PATH] = APP_MODULES_BASE_URL;
		}

		$urls[MODULES_PATH] = MODULES_BASE_URL;

		return $urls;
	}

	/**
	 *
	 * @param boolean $absolute
	 * @param mixed $lastDS
	 * @return array[string]	$r[$modulePath] => $moduleUrl (relative to SITE_BASE_URL)
	 */
	private static function getModulePathsAndUrls($lastDS = DS) {
		$r = array();
		foreach (self::getModuleBasePathsAndUrls() as $path => $url) {
			foreach (FileHelper::listDirs($path, false, '/^[^.]/') as $dir) {
				$r[$path . $dir . $lastDS] = "$url$dir";
			}
		}
		return $r;
	}

	private static function getModulePaths($lastDS = DS) {
		$r = array();
		foreach (self::getModuleBasePaths() as $basePath) {
			foreach (FileHelper::listDirs($basePath, true, '/^[^.]/') as $path) {
				$r[] = $path . $lastDS;
			}
		}
		return $r;
	}

	/**
	 *
	 * @param string $name
	 * @return Template
	 */
	private static function loadController($name, Config $config = null) {
		if ($config === null) $config = self::loadModuleConfig($name);
		$class = $config->class;
//		return $class::generateController($name, $config); // php 5.3
		return call_user_func(array($class, 'generateController'), $name, $config);
	}

	/**
	 * This function gives the opportunity to each module [plugin] to add a js file
	 * inclusion in the index.html document which will be sent as the answer
	 * to the first request by the browser.
	 *
	 * This is a good way for a module [plugin] to push some javascript object
	 * modified or created (and used) by the plugin that may be shared by
	 * multiple instances of the module [plugin].
	 *
	 * @return array[string]	an array of $fileName strings, $fileName being
	 * the part of the url, after {@see SITE_BASE_URL}.
	 */
	public static function getModulesJS() {
		$jsUrls = array();

		// include .js files without modification
		foreach (self::getModulePathsAndUrls() as $path => $url) {
			if (is_dir($jsDir = $path . 'js')) {
				foreach (FileHelper::listFiles($jsDir, '/\.js$/i', true, false) as $file) {
					$jsUrls[] = "$url/js/$file";
				}
			}
		}

		return $jsUrls;
	}

	public static function addIncludePaths(&$includePaths) {
		foreach (self::getModulePaths() as $dir) {
			if (is_dir($path = $dir . 'php')) {
				$includePaths[] = $path . DS;
			}
		}
	}

	public static function getModules() {
		$r = array();
		foreach (FileHelper::listDirs(MODULES_PATH) as $module) {
			if (OceModule::isValid($module, MODULES_PATH . $module)) {
				$r[] = new OceModule($module, MODULES_PATH . $module, false);
			}
		}
		foreach (FileHelper::listFiles(MODULES_PATH, '/\.yml$/i') as $module) {
			if (OceModule::isValid($module, MODULES_PATH)) {
				$r[] = new OceModule($module, MODULES_PATH, true);
			}
		}
		return $r;
	}

	public static function listModuleNames() {
		$r = array();
		foreach (FileHelper::listDirs(MODULES_PATH) as $module) {
			$r[] = $module;
		}
		foreach (FileHelper::listFiles(MODULES_PATH, '/\.yml$/i') as $module) {
			$r[] = $module;
		}
		return $r;
	}
	
	public static function getController($controllerName, $action, $request) {
		return self::createController($controllerName, $action, $request);
	}
	
	private static function createDefaultController($moduleName, Request $request) {
		
		if (null !== $pu = self::getModulePathAndUrl($moduleName, false)) {
			$path = $pu['path'];
			$url = $pu['url'];
		} else {
			$path = $url = null;
		}
		
		return self::$modules[$moduleName] = new eoko\module\Module(
			$moduleName, $path, $url, $request
		);
	}
	
	public static function createController($controllerName, $action, $request) {

		if (isset(self::$modules[$controllerName])) {
			return self::$modules[$controllerName];
		}

		if (!class_exists($controllerName . 'Controller', false)) {
			if (USE_CONTROLLER_CACHE && file_exists($file = CACHE_PATH . 'modules' . DS . "$controllerName.class.php")) {
				require_once $file;
			} else {
				if (null !== $path = self::getModulePath($controllerName, false)) {
					if (file_exists($filename = $path . 'controller.class.php')) {
						require $filename;
					} else if (file_exists($filename = $path . $controllerName . '.class.php')) {
						require $filename;
					} else {
						return self::createDefaultController($controllerName, $request);
//REM						throw new IllegalStateException('Missing controller for: ' . $controllerName);
					}
				} else {
					$done = false;
					foreach (self::getModuleBasePaths() as $path) {
						if (file_exists($filename = $path . "$controllerName.class.php")) {
							require $filename;
							$done = true;
							break;
						}
					}
					
					if (!$done) {
						return self::createDefaultController($controllerName, $request);
//REM						throw new IllegalStateException(
//							'Missing controller for: ' . $controllerName
//						);
					}
				}
			}
		}

		$class = $controllerName . 'Controller';
		
		if (
			class_exists($nsClass = APP_MODULES_NAMESPACE . $class, false)
			|| class_exists($nsClass = MODULES_NAMESPACE . $class, false)
		) {
			$class = $nsClass;
		}
		
		return self::$modules[$controllerName] = 
				new $class($controllerName, $path, $url, $action, $request);
	}

}
