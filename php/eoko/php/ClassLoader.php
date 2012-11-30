<?php

namespace eoko\php;

use eoko\module\ModuleManager;
use eoko\php\generator\ClassGeneratorManager;

use IllegalStateException;

class ClassLoader {

	private static $classLoader = null;

	private $includePaths = array();

	private $allowedClassSuffix = array(
		'', '.class', '.interface',
	);

	private function __construct() {}

	/**
	 * Instanciates and register the class loader.
	 * @return ClassLoader
	 */
	public static function register() {
		if (self::$classLoader) throw new \Exception('ClassLoader already registered!');
		self::$classLoader = new ClassLoader();
		spl_autoload_register(array(self::$classLoader, 'load'));
		return self::$classLoader;
	}

	/**
	 * Gets the ClassLoader registered instance.
	 * @return ClassLoader
	 * @throws IllegalStateException if the ClassLoader has not yet been
	 * registered
	 */
	public static function getInstance() {
		if (!self::$classLoader) throw new IllegalStateException('ClassLoader has not been loaded yet!');
		return self::$classLoader;
	}

	public function addIncludePath($path) {
		if (is_array($path)) {
			foreach ($path as $path) $this->addIncludePath($path);
		} else {
			if (substr($path, -1) !== DS) {
				$path .= DS;
			}
			$this->includePaths[] = $path;
		}
	}

	public function load($class) {
		foreach ($this->allowedClassSuffix as $suffix) {
			if ($this->doLoad($class, $suffix)) return;
		}
	}

	protected function doLoad($class, $suffix) {

		$classPath = str_replace('\\', DS, $class);
		$nsPath = str_replace('\\', DS, rtrim(get_namespace($class), '\\'));

		foreach ($this->includePaths as $path) {
			if (file_exists($filename = "$path$classPath$suffix.php")) {
				require_once $filename;
				return true;
			} else if (!$nsPath) {
				if (file_exists($filename = "{$path}_/$classPath$suffix.php")) {
					require_once $filename;
					return true;
				}
			} else if (file_exists($filename = "$path$nsPath.ns.php")) {
				require_once $filename;
				return true;
			}
		}

	//	if (substr($class, 0, strlen(MODULES_NAMESPACE)) === MODULES_NAMESPACE) {
	//		$classPath = substr($class, strlen(MODULES_NAMESPACE));
	//		$classPath = str_replace('\\', DS, $classPath);
	//		$classPath = MODULES_PATH . $classPath;
	//		if (file_exists($filename = "$classPath$suffix.php")) {
	//			require_once $filename;
	//			return true;
	//		}
	//	}
		if (ModuleManager::autoLoad($class, $suffix)) return true;

		if (ClassGeneratorManager::generate($class)) {
			return true;
		}

		if (2 === count($parts = explode('_', $classPath, 2))) {
			$classPath = $parts[0];
			foreach ($this->includePaths as $path) {
				if (file_exists($filename = "$path$classPath$suffix.php")
						|| file_exists($filename = "{$path}_/$classPath$suffix.php")) {
					require_once $filename;
					return true;
				}
			}
		}
		if (substr($classPath, -5) === 'Query') {
			if (defined('MODEL_QUERY_PATH')
					&& file_exists($filename = MODEL_QUERY_PATH . "$classPath$suffix.php")) {
				require_once $filename;
				return true;
			}
		}
		if (substr($classPath, -5) === 'Proxy') {
			if (defined('MODEL_PROXY_PATH')
					&& file_exists($filename = MODEL_PROXY_PATH . "$classPath$suffix.php")) {
				require_once $filename;
				return true;
			}
		}

//		if (substr($class, 0, 5) === 'eoze\\') {
//			$orig = $class;
//			$class = 'eoko' . substr($class, 4);
//			if ($this->doLoad($class, $suffix)) {
//				class_alias($class, $orig);
//			}
//		}

		return false;
	}
}
