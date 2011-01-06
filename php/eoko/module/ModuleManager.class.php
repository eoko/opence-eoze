<?php

namespace eoko\module;

const GET_MODULE_NAMESPACE = 'eoko\\_getModule\\';

use eoko\config\Config, eoko\config\ConfigManager;
use eoko\php\generator\ClassGeneratorManager;

use IllegalStateException;
use Logger;

class ModuleManager {

	private static $moduleLocations = null;
	private static $infoLocked = false;
	
	private static $instance = null;

	private static $moduleFactories = null;
	
	private $modules = null;

	private $getModuleNamespaceRegex;

	/**
	 * @var FileFinder
	 */
	private $fileFinder = null;
	
	private function __construct() {

		$this->getModuleNamespaceRegex = '/^' . preg_quote(GET_MODULE_NAMESPACE, '/') . '(.+)$/';
		
		$this->loadConfig();
		
		self::$moduleLocations = array_reverse(self::$moduleLocations, true);
		self::$infoLocked = true;
	}

	private function loadConfig() {
		$config = ConfigManager::get(__NAMESPACE__);
		foreach (array_reverse($config['locations']) as $location) {
			self::addModuleLocationInfo($location['path'], $location['url'], $location['namespace']);
		}
	}

	/**
	 * Adds a new possible location for Modules. This initialization can
	 * be called only before the ModuleManager actually starts being used. The
	 * locations added first will be the ones with the lowest priority in the
	 * module search algorithm; the modules in the lower locations will also
	 * be considered potential parents for all upper locations (i.e. if a module
	 * from an upper location has the same name as one from a lower location,
	 * it will be considered as extending the latter).
	 * @param string $basePath
	 * @param string $baseUrl
	 * @param string $namespace
	 */
	private static function addModuleLocationInfo($basePath, $baseUrl, $namespace) {
		if (self::$infoLocked) {
			throw new IllegalStateException(
				'All module locations must be added before the first use of ModuleManager'
			);
		}
		$parent = self::$moduleLocations === null ? null : self::$moduleLocations[count(self::$moduleLocations) - 1];
		self::$moduleLocations[] = new ModulesDirectory($basePath, $baseUrl, $namespace, $parent);
	}

	private function testGetModuleNamespace($classOrNamespace) {
		if (!preg_match($this->getModuleNamespaceRegex, $classOrNamespace, $m)) {
			return false;
		} else {
			return $m[1];
		}
	}

	public static function autoLoad($class, $suffix) {

		// The class autoloader may be called before the ModuleManager instance
		// has been created, or in the process of its creation. In this case,
		// these are not Modules classes that are searched.
		if (!self::$instance) return false;

		if (false !== $module = self::$instance->testGetModuleNamespace($class)) {
			$module = self::getModule($module);
			class_extend(GET_MODULE_NAMESPACE . $module, get_class($module));
			return true;
		}

		foreach (self::$moduleLocations as $location) {
			$location instanceof ModulesDirectory;
			if ($location->testNamespace($class)) {

				$classPath = substr($class, strlen($location->namespace));
				$classPath = $location->path . str_replace('\\', DS, $classPath);

				if (file_exists($path = "$classPath$suffix.php")) {
					require_once $path;
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @return array[ModulesLocation]
	 */
	private static function getModulesLocations() {
		self::getInstance();
		return self::$moduleLocations;
	}

	public static function registerModuleFactory(ModuleFactory $factory) {
		self::$moduleFactories[] = $factory;
	}

	/**
	 * @return ModuleManager
	 */
	private static function createInstance() {
		return self::$instance = new ModuleManager();
	}
	
	/**
	 * @return ModuleManager
	 */
	public static function getInstance() {
		if (self::$instance) return self::$instance;
		else return self::createInstance();
	}
	
	/**
	 * Gets the module with the given name. Module are searched in each
	 * registered {@link ModulesLocation}, starting from the application level
	 * and going down to the eoko library level.
	 *
	 *
	 * Module inheritance mecanisme allows for recursive overriding of:
	 * - the module class itself
	 * - the module configuration
	 * - any of the module's executors
	 *
	 * It doesn't work however for overriding normal PHP classes. If this is
	 * needed, the top modules must extend the fully qualified (ie. named with
	 * its full namespace) parent class.
	 *
	 *
	 * Module Inheritance
	 *
	 * Modules which class is explicitely declared in a {ModuleName}.class.php
	 * file can override another module by extending it without importing
	 * any specific class with the <b>use</b> keyword. The ModuleManager will
	 * resolve the highest (the nearest to the application level) implementation
	 * of the Module.
	 *
	 * Modules of which the class is implicitely created (that is, which do not
	 * have a {ModuleName}.class.php file) must declare the name of their base
	 * module in their config file.
	 *
	 * A Module with the same name as a Module found lower in the hierarchy will
	 * automatically extend it. It doesn't need to have an explicit class file
	 * declaration, or to have a configuration file. A Module with the same
	 * name as a lower Module is not allowed to extend from another Module
	 * (that is, a module with another name). That would be problematic, since
	 * the lower Module could not be accessed by its name anymore, which could
	 * be unexpected by some part of the application. If you want to do that,
	 * that means that you don't need the lower Module, so you can just disable
	 * it.
	 *
	 *
	 * Configuration Inheritance
	 *
	 * The configuration will always be inherited from lower modules, with
	 * configuration from higher level overriding configuration from lower
	 * levels.
	 *
	 *
	 * Executor Inheritance
	 *
	 * Executors of the same type will override lowser executors if they extend
	 * {ExecutorType}Base.
	 *
	 * 
	 * @param string $name
	 * @return Module
	 */
	public static function getModule($name) {
		if ($name instanceof Module) return $name;
		return self::getInstance()->doGetModule($name);
	}
	
	private function doGetModule($name) {

		if (strstr($name, '\\')) {
			$ns = get_namespace($name, $relName);
			try {
				return $this->getModuleInNamespace($relName, $ns);
			} catch (MissingModuleException $ex) {
				throw new MissingModuleException($name, $ex);
			}
		}

		// cache
		if (isset($this->modules[$name])) {
			return $this->modules[$name];
		}

		// try to delegate
		if (self::$moduleFactories) foreach (self::$moduleFactories as $factory) {
			if (null !== $module = $factory->generateModule($name)) {
				Logger::get($this)->debug('Module generated by factory: {}', $name);
				return $module;
			}
		}

		// ... or do the job
		foreach (self::$moduleLocations as $location) {
			if (($module = $this->tryGetModule($name, $location))) {
				return $this->modules[$name] = $module;
			}
		}

		throw new MissingModuleException($name);
	}

	private function getModuleInNamespace($name, $ns) {
			
		Logger::get($this)->warn(
			'GetModule used to retrieve absolute class: {}. This is wrong. '
			. 'Do not do that!',
			$name
		);
		
		if (isset($this->modules[$name])) {
			return $this->modules[$name];
		}

		foreach (self::$moduleLocations as $location) {
			$location instanceof ModulesDirectory;
			if ($location->testNamespace($ns)) {
				$module = $this->tryGetModule($name, $location);
				if ($module) {
					return $this->modules[$name] = $module;
				}
			}
		}
		
		throw new MissingModuleException($name);
	}

	/**
	 *
	 * @param string $name    the module name
	 * @param ModulesDirectory $inLocation the location to be searched for the
	 * module class
	 * @param ModulesDirectory $dir the base location of the module.
	 * This parameter should be given if a location for this module has already
	 * been found (that is a directory that could contains module information),
	 * but this location doesn't contains the information to instanciate the
	 * module.
	 * @return class
	 */
	private function tryGetModule($name, ModulesDirectory $dir) {
		
		$location = new ModuleLocation($dir, $name);

		if (false === $config = $location->searchConfigFile()) {
			return false;
		}

		if (!$location->isActual()) {
			$namespace = "$dir->namespace$name\\";
			return $this->createDefaultModule($config, $name, $namespace, $dir);
		} else {

			if (null !== $class = $location->searchModuleClass()) {
				$module = new $class($location);
			}

			// try to create the module from the config file information
			if ($config && ($m = $this->createDefaultModule($config, $name, $location->namespace, $dir))) {
				return $m;
			}

			// generate the module class in the namespace
			$superclass = $location->searchModuleSuperclass();
			if ($superclass === null) {
				$superclass = __NAMESPACE__ . '\\Module';
			}
			class_extend($class = "$location->namespace$name", $superclass);
			$module = new $class($location);

			// if the method has not returned yet, that means that the module
			// has not been created from config: the config must be set!
			$module->setConfig($config);
			return $module;
		}
//REM
//			// REM...
//
//			$path = $dir->path . $name . DS;
//			$url = "$dir->url$name/";
//
//			foreach (array("$name.class.php", 'module.class.php', "{$name}Module.class.php") as $file) {
//				if (file_exists($file = "$path$file")) {
//					require_once $file;
//					foreach (array($name, 'module', "{$name}module") as $class) {
//						$class = $namespace . $class;
//						if (class_exists($class, false)) {
//							$module = new $class($name, $path, $url, $dir);
//							$module->setConfig($config);
//							return $module;
//						}
//					}
//					throw new InvalidModuleException($name, 'cannot find module class');
//				}
//			}
//
//			dumpl(func_get_args());
//			dumpl($config);
//
//			// If there is a config file, it will give us the information needed
//			// to create the Module class.
//			if ($config) {
//				return $this->createDefaultModule($config, $name, $path, $namespace, $url, $dir);
//
//			// If there isn't one, that may be the case of a Module having a
//			// folder overriding some parts of its parent module (ie. vertical
//			// inheritance instead of horizontal one). We must find the parent
//			// class of the module.
//			} else {
////			} else if ($inLocation->parent && (false !== $class = $this->tryGetModule($name, $inLocation->parent, $baseLocation))) {
////				// TODO there's a problem here, when a module override a parent
////				// module without declaring the module class itself
////				// (eg. espanki\modules\root extends eoko\modules\root),
////				// the intermediate module should be correctly generated. This is
////				// not the case... (see bellow, a tentative on solving this issue --
////				// not the time to make it work right now :/ )
////				return $this->tryGetModule($name, $inLocation, $baseLocation);
////				// TODO this is not very logical that the base module is instanciated
////				// instead of being simply extended...
////				$c = class_extend($newClass = "$namespace$name", get_class($class));
////				$c = new $newClass($name, $path, $url, $baseLocation);
//
//			// In last resort, we create an alias of the base Module for the
//			// given module $name...
////			} else {
//				class_extend($class = "$namespace$name", __NAMESPACE__ . "\\Module");
//				return new $class($name, $path, $url, $dir);
//			}
//		}
//
//		return false;
	}


//REM	private function findConfigFile($name, ModulesLocation $md, &$hasDir) {
//		if (($hasDir = is_dir("$md->path$name"))) {
//			$md->path = "$md->path$name" . DS;
//			if (file_exists($file = "$md->path$name.yml")
//					|| file_exists($file = "config.yml")) {
//
//				return $file;
//			} else {
//				return null;
//			}
//		} else if (file_exists($file = "$md->path$name.yml")) {
//			return $file;
//		} else {
//			return false;
//		}
//	}
	
	/**
	 * Generates a default module class and instanciates it, according to its
	 * configuration file "class" item.
	 */
	public function createDefaultModule($config, $name, $namespace, ModulesDirectory $dir = null) {
//	public function createDefaultModule($config, $name, $namespace, ModulesDirectory $dir = null) {

		if ($dir === null) $dir = self::$moduleLocations[count(self::$moduleLocations) - 1];

		$config = Config::create($config);
		if (isset($config[$name])) $config = $config->node($name, true);

		if (substr($namespace, -1) !== '\\') $namespace .= '\\';
		$class = $namespace . $name;

		// get the base module, as defined by the "class" option in the the
		// config file
		if (!$config->class) {
			return false;
		}
		$baseModule = self::getModule($config->class);
		// use the base module to generate the default class
		$baseModule->generateDefaultModuleClass($class, $config);
		
		// create an instance of the newly created class
		$module = new $class(new ModuleLocation($dir, $name));

		$module->setConfig($config);

		return $module;
	}
	
}

class MissingModuleException extends \SystemException {
	
	public function __construct($name, \Exception $previous = null) {
		parent::__construct('Missing module: ' . $name, '', $previous);
	}
}

class InvalidModuleException extends \SystemException {
	
	public function __construct($name, $cause, \Exception $previous = null) {
		parent::__construct("Invalid module: $name ($cause)", '', $previous);
	}
}

class Location {

	public $path;
	public $url;
	public $namespace;

	function __construct($path, $url, $namespace) {
		$this->path = $path;
		$this->url = $url;
		$this->namespace = $namespace;
	}

	public function __toString() {
		return "Path: $this->path; URL: $this->url; Namespace: $this->namespace";
	}
}

/**
 * Represents the different locations of one named module.
 */
class ModuleLocation extends Location {

	public $moduleName;
	/** @var ModuleDirectory */
	public $directory;

	/** @var array[ModuleLocation] Cache for actual locations */
	private $actualLocations = null;

	/**
	 * Creates a new ModuleLocation. If no directory for the module exists in
	 * the location, the $path is set to NULL. This test can be bypassed by
	 * passing a $path to the constructor -- which should be avoided anyway,
	 * except if you know what you are doing.
	 * @param ModulesDirectory $dir
	 * @param string $moduleName
	 * @param string|boolean $path TRUE to let the constructor search for the
	 * module's path, or a value to force the location's path to be set
	 */
	function __construct(ModulesDirectory $dir, $moduleName, $path = true) {

		$this->directory = $dir;
		$this->moduleName = $moduleName;

		if ($path === true) {
			$path = is_dir($path = "$dir->path$moduleName") ? $path . DS : null;
		}

		parent::__construct(
			$path,
			$path !== null && $dir->url !== null ? "$dir->url$moduleName/" : null,
			"$dir->namespace$moduleName\\"
		);
	}

	/**
	 * Finds the module's config file path. The directory parents are not
	 * searched by this method.
	 * @return string The path of the found config file, or NULL if no config
	 * file is found in this location.
	 */
	public function searchConfigFile() {
		if ($this->isActual()) {
			if (file_exists($file = "$this->path$this->moduleName.yml")
					|| file_exists($file = "{$this->path}config.yml")) {

				return $file;
			} else {
				return null;
			}
		} else if (file_exists($file = "{$this->directory->path}$this->moduleName.yml")) {
			return $file;
		} else {
			return false;
		}
//		if (($hasDir = is_dir($path = "$this->path$moduleName"))) {
//			$path .= DS;
//			if (file_exists($file = "$path$moduleName.yml")
//					|| file_exists($file = "{$path}config.yml")) {
//
//				return $file;
//			} else {
//				return null;
//			}
//		} else if (file_exists($file = "$this->path$moduleName.yml")) {
//			return $file;
//		} else {
//			return false;
//		}
	}

	/**
	 * An actual ModuleLocation is a location that actually contains a directory
	 * for its module.
	 * @return boolean
	 * @see $moduleName
	 */
	public function isActual() {
		return $this->path !== null;
	}
	
	/**
	 * Gets the ModuleLocations of this location's module, starting from this
	 * locations and including only location in which a directory for this
	 * module exists.
	 * @return array[ModuleLocation]
	 */
	public function getActualLocations($includeSelf = true) {
		
		if ($this->actualLocations !== null) return $this->actualLocations;

		$this->actualLocations = array();

		// if _this_ is an actual location
		if ($includeSelf && $this->path !== null) {
			$this->actualLocations[] = $this;
		}

		// search parents
		$dir = $this->directory->parent;
		while ($dir) {
			if (is_dir($path = "$dir->path$this->moduleName")) {
				$this->actualLocations[] = new ModuleLocation($dir, $this->moduleName, $path . DS);
			}
			$dir = $dir->parent;
		}

		return $this->actualLocations;
	}

	/**
	 * Searches the location for a file matching the module class file pattern
	 * and, if one is found, returns the module class' name.
	 * @return string The qualified class name, or NULL.
	 * @throws InvalidModuleException if a matching file is found but doesn't
	 * contain a class that matches the module classes naming pattern.
	 */
	public function searchModuleClass() {

		foreach (array("$this->moduleName.class.php", 'module.class.php', "{$this->moduleName}Module.class.php") as $file) {
			if (file_exists($file = "$this->path$file")) {
				require_once $file;
				foreach (array($this->moduleName, 'module', "{$this->moduleName}module") as $class) {
					$class = $this->namespace . $class;
					if (class_exists($class, false)) {
						return $class;
					}
				}
				// A file matching the module filename pattern
				// (eg. GridModule.class.php) has been found and included, but
				// we cannot find the matching class...
				throw new InvalidModuleException($this->moduleName, 'cannot find module class');
			}
		}

		return null;
	}

	public function searchModuleSuperclass() {
		foreach ($this->getActualLocations(false) as $location) {
			if (null !== $class = $location->searchModuleClass()) {
				return $class;
			}
		}
		return null;
	}

}

class ModulesDirectory extends Location {

	/** @var ModulesDirectory */
	public $parent;

	function __construct($path, $url, $namespace, ModulesDirectory $prev = null) {
		if (substr($path, -1) !== DS) $path .= DS;
		if (substr($url, -1) !== '/') $url .= '/';
		if (substr($namespace, -1) !== '\\') $namespace .= '\\';
		parent::__construct($path, $url, $namespace);
		$this->parent = $prev;
	}

	/**
	 * Tests if the passed namespace is the same as this ModuleInfo's one.
	 * @param string $namespaceOrClass
	 * @return bool  TRUE if the passed namespace is the same this ModuleInfo's
	 * one, else FALSE.
	 */
	public function testNamespace($namespaceOrClass) {
		$ns = substr($namespaceOrClass, 0, strlen($this->namespace));
		if (substr($ns, -1) !== '\\') $ns .= '\\';
		return $ns === $this->namespace;
	}

//	/**
//	 * Finds the config file path for the given $moduleName. The directory
//	 * parents are not searched by this method.
//	 * @param string $moduleName
//	 * @param bool &$hasDir  will be set to TRUE if the config file is found in
//	 * its own directory, else will be set to FALSE (i.e. if the config file is
//	 * found in the location's base path).
//	 * @return string  the path of the found config file, or NULL if no config
//	 * file for the passed $moduleName is found in this location.
//	 */
//	public function findConfigFile($moduleName, &$hasDir) {
//		if (($hasDir = is_dir($path = "$this->path$moduleName"))) {
//			$path .= DS;
//			if (file_exists($file = "$path$moduleName.yml")
//					|| file_exists($file = "{$path}config.yml")) {
//
//				return $file;
//			} else {
//				return null;
//			}
//		} else if (file_exists($file = "$this->path$moduleName.yml")) {
//			return $file;
//		} else {
//			return false;
//		}
//	}

	public function getLineagePathsUrl($names) {
		$pathsUrl = array();
		foreach ($names as $name) {
			$pathsUrl = $pathsUrl + $this->getPathsUrl($name);
		}
		return $pathsUrl;
	}

	public function getPathsUrl($moduleName) {
		$pathsUrl = array();
		if (file_exists($path = "$this->path$moduleName" . DS)) {
			$pathsUrl[$path] = $this->url !== null ? "$this->url$moduleName/" : null;
		}
		if ($this->parent) {
			$pathsUrl = $pathsUrl + $this->parent->getPathsUrl($moduleName);
		}
		return $pathsUrl;
	}
	
	public function getLineageLocations($names) {
		$locations = array();
		foreach ($names as $name) {
			$locations = array_merge($locations, $this->getLocations($name));
		}
		return $locations;
	}

	private function getLocations($moduleName) {
		$locations = array();
		if (file_exists($path = "$this->path$moduleName" . DS)) {
			$locations[] = new ModuleLocation($this, $moduleName);
//REM			$locations[] = new Location(
//				$path,
//				$this->url !== null ? "$this->url$moduleName/" : null,
//				"$this->namespace$moduleName\\",
//				$moduleName
//			);
		}
		if ($this->parent) {
			$locations = array_merge($locations, $this->parent->getLocations($moduleName));
		}
		return $locations;
	}

	/**
	 * Gets parent path urls.
	 * @param string $moduleName
	 * @return array or NULL if there is no parent paths for the given module.
	 */
	public function getParentPathsUrl($moduleName) {
		if ($this->parent && count($pathUrls = $this->parent->doGetParentPathsUrl($moduleName))) {
			return $pathUrls;
		} else {
			return null;
		}
	}

	private function doGetParentPathsUrl($moduleName) {
		$pathUrls = array();
		if (file_exists($path = "$this->path$moduleName" . DS)) {
			$pathUrls[$path] = $this->url !== null ? "$this->url$moduleName/" : null;
		}
		if ($this->parent) {
			$pathUrls = $pathUrls + $this->parent->doGetParentPathsUrl($moduleName);
		}
		return $pathUrls;
	}

	public function __toString() {
		return "Path: $this->path, URL: $this->url, Namespace: $this->namespace, Parent: "
				. ($this->parent !== null ? "\n\t\t$this->parent" : "NULL");
	}
}

interface ModuleFactory {
	function generateModule($name);
}

class Module_ModuleFactory implements ModuleFactory {

	private $moduleName;

	function __construct($moduleName) {
		$this->moduleName = $moduleName;
	}

	public function generateModule($name) {
		// avoid infinite recursion
		if ($name !== $this->moduleName) {
			$module = ModuleManager::getModule($this->moduleName);
			return $module->generateModule($name);
		} else {
			return null;
		}
	}
}
