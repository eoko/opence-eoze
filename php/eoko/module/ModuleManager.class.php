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
		self::$moduleLocations[] = new ModulesLocation($basePath, $baseUrl, $namespace, $parent);
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
		// these are not Modules class that are searched.
		if (!self::$instance) return false;

		if (false !== $module = self::$instance->testGetModuleNamespace($class)) {
			$module = self::getModule($module);
			class_extend(GET_MODULE_NAMESPACE . $module, get_class($module));
			return true;
		}

		foreach (self::$moduleLocations as $location) {
			$location instanceof ModulesLocation;
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
			$location instanceof ModulesLocation;
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
	 * @param ModulesLocation $inLocation the location to be searched for the
	 * module class
	 * @param ModulesLocation $baseLocation the base location of the module.
	 * This parameter should be given if a location for this module has already
	 * been found (that is a directory that could contains module information),
	 * but this location doesn't contains the information to instanciate the
	 * module.
	 * @return class
	 */
	private function tryGetModule($name, 
			ModulesLocation $inLocation, ModulesLocation $baseLocation = null) {
		
		if ($baseLocation === null) $baseLocation = $inLocation;

		$namespace = "$inLocation->namespace$name\\";

		if (false === $config = $inLocation->findConfigFile($name, $hasDir)) {
			return false;
		}

		if (!$hasDir) {
			// TODO probably need to be passed the inLocation also
			return $this->createDefaultModule($config, $name, null, $namespace, null, $baseLocation);
		} else {
			
			$path = $baseLocation->path . $name . DS;
			$url = "$baseLocation->url$name/";

			foreach (array("$name.class.php", 'module.class.php', "{$name}Module.class.php") as $file) {
				if (file_exists($file = "$path$file")) {
					require_once $file;
					foreach (array($name, 'module', "{$name}module") as $class) {
						$class = $namespace . $class;
						if (class_exists($class, false)) {
							$module = new $class($name, $path, $url, $baseLocation);
							$module->setConfig($config);
							return $module;
						}
					}
					throw new InvalidModuleException($name, 'cannot find module class');
				}
			}

			// If there is a config file, it will give us the information on
			// how to create the Module class.
			if ($config) {
				return $this->createDefaultModule($config, $name, $path, $namespace, $url, $baseLocation);

			// If there isn't one, that may be the case of a Module having a
			// folder overriding some parts of its parent module, so we must
			// search in parent paths to see if there is a module with that name.
			} else if ($inLocation->parent && (false !== $class = $this->tryGetModule($name, $inLocation->parent, $baseLocation))) {
				// TODO there's a problem here, when a module override a parent
				// module without declaring the module class itself
				// (eg. espanki\modules\root extends eoko\modules\root),
				// the intermediate module should be correctly generated. This is
				// not the case... (see bellow, a tentative on solving this issue --
				// not the time to make it work right now :/ )
				return $this->tryGetModule($name, $inLocation, $baseLocation);
				// TODO this is not very logical that the base module is instanciated
				// instead of being simply extended...
				$c = class_extend($newClass = "$namespace$name", get_class($class));
				$c = new $newClass($name, $path, $url, $baseLocation);

			// In last resort, we create an alias of the base Module for the
			// given module $name...
			} else {
				class_extend($class = "$namespace$name", __NAMESPACE__ . "\\Module");
				return new $class($name, $path, $url, $baseLocation);
			}
		}
		
		return false;
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
	 * Generate a default module class and instanciate it, according to its 
	 * configuration file "class" item.
	 */
	public function createDefaultModule($config, $name, $path, $namespace, $url,
			ModulesLocation $location = null) {

		if ($location === null) $location = self::$moduleLocations[count(self::$moduleLocations) - 1];

		$config = Config::create($config);
		if (isset($config[$name])) $config = $config->node($name, true);

		if (substr($namespace, -1) !== '\\') $namespace .= '\\';
		$class = $namespace . $name;

		// get the base module, as defined by the "class" option in the the
		// config file
		$baseModule = self::getModule($config->class);
		// use the base module to generate the default class
		$baseModule->generateDefaultModuleClass($class, $config);
		
		// create an instance of the newly created class
		$module = new $class($name, $path, $url, $location);

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

	public $name;

	function __construct($path, $url, $namespace, $name = null) {
		$this->path = $path;
		$this->url = $url;
		$this->namespace = $namespace;
		$this->name = $name;
	}

	public function __toString() {
		return "Path: $this->path; URL: $this->url; Namespace: $this->namespace";
	}
}

class ModulesLocation extends Location {

	/** @var ModulesLocation */
	public $parent;

	function __construct($path, $url, $namespace, ModulesLocation $prev = null) {
		if (substr($path, -1) !== DS) $path .= DS;
		if (substr($url, -1) !== '/') $url .= '/';
		if (substr($namespace, -1) !== '\\') $namespace .= '\\';
		$this->path = $path;
		$this->url = $url;
		$this->namespace = $namespace;
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

	/**
	 * Finds the config file path for the given $moduleName.
	 * @param string $moduleName
	 * @param bool &$hasDir  will be set to TRUE if the config file is found in
	 * its own directory, else will be set to FALSE (i.e. if the config file is
	 * found in the location's base path).
	 * @return string  the path of the found config file, or NULL if no config
	 * file for the passed $moduleName is found in this location.
	 */
	public function findConfigFile($moduleName, &$hasDir) {
		if (($hasDir = is_dir($path = "$this->path$moduleName"))) {
			$path .= DS;
			if (file_exists($file = "$path$moduleName.yml")
					|| file_exists($file = "{$path}config.yml")) {

				return $file;
			} else {
				return null;
			}
		} else if (file_exists($file = "$this->path$moduleName.yml")) {
			return $file;
		} else {
			return false;
		}
	}

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

	public function getLocations($moduleName) {
		$locations = array();
		if (file_exists($path = "$this->path$moduleName" . DS)) {
			$locations[] = new Location(
				$path,
				$this->url !== null ? "$this->url$moduleName/" : null,
				"$this->namespace$moduleName\\",
				$moduleName
			);
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