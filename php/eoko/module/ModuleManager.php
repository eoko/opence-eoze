<?php

namespace eoko\module;

const GET_MODULE_NAMESPACE = 'eoko\\_getModule\\';

use eoko\config\Config, eoko\config\ConfigManager;
use eoko\php\generator\ClassGeneratorManager;
use eoko\util\Files;
use eoko\cache\Cache;

use eoko\module\exceptions\MissingModuleException;
use eoko\module\exceptions\InvalidModuleException;

use IllegalStateException;
use Logger;

/**
 * Terminology:
 *   - Module _line_: vertical inheritance (eg. r\m\GridModule => eoko\modules\GridModule, ...)
 *   - Module _lineage_: horizontal inheritance (eg. r\m\Membres => r\m\GridModule, ...)
 */
class ModuleManager {

	private static $modulesDirectories = null;
	private static $infoLocked = false;
	
	private static $instance = null;

	private static $moduleFactories = null;
	
	private static $modules = null;

	private $getModuleNamespaceRegex;

	private function __construct() {

		$this->getModuleNamespaceRegex = '/^' . preg_quote(GET_MODULE_NAMESPACE, '/') . '(.+)$/';
		
		$this->loadConfig();
		
		self::$modulesDirectories = array_reverse(self::$modulesDirectories, true);
		self::$infoLocked = true;
	}

	/**
	 * Returns the first ModulesDirectory from the registered ones list.
	 * @return ModulesDirectory
	 */
	public static function getTopLevelDirectory() {
		return self::$modulesDirectories[count(self::$modulesDirectories) - 1];
	}

	private function loadConfig() {
		$config = ConfigManager::get(__NAMESPACE__);
		foreach (array_reverse($config['locations']) as $dirName => $location) {
			self::addModuleLocationInfo($dirName, $location['path'], $location['url'], $location['namespace']);
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
	 * @param string $dirName
	 * @param string $basePath
	 * @param string $baseUrl
	 * @param string $namespace
	 */
	private static function addModuleLocationInfo($dirName, $basePath, $baseUrl, $namespace) {
		if (self::$infoLocked) {
			throw new IllegalStateException(
				'All module locations must be added before the first use of ModuleManager'
			);
		}
		$parent = self::$modulesDirectories === null ? null : self::$modulesDirectories[count(self::$modulesDirectories) - 1];
		self::$modulesDirectories[] = ModulesDirectory::create($dirName, $basePath, $baseUrl, $namespace, $parent);
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

		foreach (self::$modulesDirectories as $location) {
			$location instanceof ModulesDirectory;
			if ($location->testNamespace($class)) {

				$classPath = substr($class, strlen($location->namespace));
				$classPath = $location->path . str_replace('\\', DS, $classPath);
				
				$cp2 = $location->path . 'php' . DS . $classPath;
				$cp2 = substr($class, strlen($location->namespace));
				$cp2 = str_replace('\\', '/', $cp2);
				if (preg_match('@^([^/]+)/(.*)$@', $cp2, $m)) {
					$cp2 = implode(DS, array($m[1], 'php', $m[2]));
					$cp2 = $location->path . str_replace('/', DS, $cp2);
				} else {
					$cp2 = false;
				}
				
				if (file_exists($path = "$classPath$suffix.php")
						|| $cp2 && file_exists($path = "$cp2$suffix.php")) {
					require_once $path;
					return true;
				}

				// Resolution of automatic parent class ___
				if (preg_match(
					'/^' . preg_quote($location->namespace, '/') . '(?P<moduleName>.+)\\\\_{1,3}$/',
					$class,
					$matches
				)) {
					$moduleName = $matches['moduleName'];
					if (null !== $config = self::getModuleConfig($moduleName)) {
						$parentModuleName = self::getModuleConfig($moduleName)->class;
						$parentModule = self::getModule($parentModuleName);
						$parentClass  = get_class($parentModule);
						class_extend($class, $parentClass);
						return true;
					} else {
						throw new IllegalStateException(
								"Cannot find configuration for module: $moduleName");
					}
				}
			}
		}
		return false;
	}

	/**
	 * @return array[ModulesLocation]
	 * @deprecated this method should follow the listModulesLocations naming
	 * convention
	 */
	private static function getModulesLocations() {
		self::getInstance();
		return self::$modulesDirectories;
	}

	public static function listModules($onlyWithDir = false) {
		$self = self::getInstance();
		$config = ConfigManager::get(__NAMESPACE__);
		
		$r = array();

		foreach (self::$modulesDirectories as $modulesDir) {
			//$modulesDir instanceof ModulesDirectory;
			$usedModules = isset($config['used'][$modulesDir->name]) ? $config['used'][$modulesDir->name] : null;
			foreach($modulesDir->listModules($usedModules, $onlyWithDir) as $module) {
				$r[$module->getName()] = $module;
			}
		}
		
		// Children modules
		foreach ($r as $module) {
			if ($module instanceof HasChildrenModules) {
				$r = array_merge($r, $module->listChildrenModules());
			}
		}

		return array_values($r);
	}

	public static function registerModuleFactory(ModuleFactory $factory) {
		self::$moduleFactories[] = $factory;
	}

	/**
	 * @return ModuleManager
	 */
	private static function createInstance() {
		self::$infoLocked = false;
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
	 * Modules which class is explicitely declared in a {ModuleName}.php
	 * file can override another module by extending it without importing
	 * any specific class with the <b>use</b> keyword. The ModuleManager will
	 * resolve the highest (the nearest to the application level) implementation
	 * of the Module.
	 *
	 * Modules of which the class is implicitely created (that is, which do not
	 * have a {ModuleName}.php file) must declare the name of their base
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
	 * @param boolean $required
	 * @return Module
	 */
	public static function getModule($name, $required = true) {
		
		$me = self::getInstance();
		
		if ($name instanceof Module) {
			$module = $name;
		} else if (isset(self::$modules[$name])) {
			$module = self::$modules[$name];
		} else {
			// this one must return, because doGetModule is filling in the
			// dependancy pile by itself
			return self::$modules[$name] = $me->doGetModule($name, $required);
		}
		
		// save in cache dependancy pile, if needed
		if ($me->cachePile !== null
			&& $module 
			&& $me->useCache()
			&& (null !== $cacheFile = Cache::getCacheFile($me->makeCacheKey($name)))
		) {
			$me->cachePile[] = $cacheFile;
		}
		
		return $module;
	}
	
	private function useCache() {
		return true;
//		return false;
	}
	
	private $cachePile = null;
	
	public function makeCacheKey($moduleName) {
		return array($this, "cachedModule_$moduleName");
	}
	
	private function doGetModule($name, $required) {
		
		$cacheKey = $this->makeCacheKey($name);
		
		// try the cache
		if ($this->useCache()
				&& (null !== $module = Cache::getCachedData($cacheKey))) {
			return $module;
		}
		
		if ($this->useCache() && $this->cachePile === null) {
			$this->cachePile = array();
			$rootCall = true;
		} else {
			$rootCall = false;
		}

		$deps = array();
		$module = $this->doInstantiateModule($name, $required, $deps);
		
		// module that don't support caching will set cacheDeps to FALSE
		if ($module && $this->useCache() && $deps !== false) {
			
			// The module cache doesn't need to monitor itself, so we use the
			// current cache pile
			$monitors = array_merge($this->cachePile, $module->getCacheMonitorFiles(true));
			
			// 1. The module config needs to depend on the module cache file
			// 2. The dependancies needs to be kept in the cache!!!
			// => That's why we must use Cache::getCacheFile to add the cacheFile
			// to the pile *before* caching the module
			if (null !== $cacheFile = Cache::getCacheFile($cacheKey, false)) {
				$this->cachePile[] = $cacheFile;
			}
			$module->setCacheDependencies($this->cachePile);
			
			Cache::monitorFiles($cacheKey, $monitors);
			Cache::cacheObject($cacheKey, $module, $deps);
			// the cachePile has already been updated
		}
		
		if ($rootCall) {
			$this->cachePile = null;
		}

		return $module;
	}

	/**
	 * @param string $name
	 * @param bool $required
	 * @param mixed $cacheDeps
	 * @return Module|null
	 * @throws exceptions\MissingModuleException
	 * @throws \Exception
	 */
	private function doInstantiateModule($name, $required, &$cacheDeps) {

		if (strstr($name, '\\')) {
			throw new \Exception('DEPRECATED');
		}
		
		// try to delegate
		if (self::$moduleFactories) foreach (self::$moduleFactories as $factory) {
			if (null !== $module = $factory->generateModule($name, $cacheDeps)) {
				Logger::get($this)->debug('Module generated by factory: {}', $name);
				return $module;
			}
		}

		// ... or do the job
		foreach (self::$modulesDirectories as $dir) {
			if (($module = $this->tryGetModule($name, $dir, $cacheDeps))) {
				return $module;
			}
		}

		if ($required) throw new MissingModuleException($name);
		else return null;
	}
	
	/**
	 * List all directories where modules can possibly be declared.
	 * @return string[]
	 */
	public function listModuleDirectories() {
		return self::$modulesDirectories;
	}
	
	private static function getModuleConfig($moduleName) {
		foreach (self::$modulesDirectories as $dir) {
			$location = ModuleLocation::create($dir, $moduleName);
			if (!$location->isDisabled()) {
				return $location->loadConfig();
			}
		}
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
	 * @param &$cacheDeps
	 * @return class
	 */
	private function tryGetModule($name, ModulesDirectory $dir, &$cacheDeps) {

		$location = ModuleLocation::create($dir, $name);
		$config = $location->loadConfig();
		
		if ($location->isDisabled()) {
			return null;
		}

		if (!$location->isActual()) {
			$namespace = "$dir->namespace$name\\";
			return $this->createDefaultModule($location, $config, false, $cacheDeps);
		} else {

			$module = null;
			if (null !== $class = $location->searchModuleClass($cacheDeps)) {
				if (null !== $module = $this->createModule($name, $class)) {
					return $module;
				}
			}

			// try to create the module from the config file information
			if ($config && ($m = $this->createDefaultModule($location, $config, false, $cacheDeps))) {
				return $m;
			}

			if (!$module) {
				// generate the module class in the namespace
				$superclass = $location->searchModuleSuperclass($cacheDeps);
				if ($superclass === null) {
					$superclass = __NAMESPACE__ . '\\Module';
				}
				if (is_array($cacheDeps)) {
					$cacheDeps[] = class_extend($class = "$location->namespace$name", $superclass);
				}
				$module = $this->createModule($name, $class);
			}

			// if the method has not returned yet, that means that the module
			// has not been created from config: the config must be set!
			// 17/02/11 01:46 This has been deprecated...
			// $module->setConfig($config);
			return $module;
		}
	}

	private function createModule($name, $class) {
		return new $class(ModuleLocation::create($this->getTopLevelDirectory(), $name));
		
		// if this is used, that may break the possibility to find parent Modules
		// configuration files, during configuration inheritance processing,
		// in Module->getConfig (eg. for SMInstance autogenerated child, the
		// TLDirectory found would be the last in the list, because these
		// autogenerated modules don't have any concrete file... So the 
		// configuration for SMInstance, lying in a directory at the application
		// level would be ignored if using the following line).
		// 
		// In the first place, I did that to handle Module class loading in
		// Module::loadExecutorTopLevelClass()...
		// $module->basePath will be taken from the first location on the stack
		// If we send the raw Location stack (which solve the aforementioned 
		// SMInstance problem), $module->basePath will be null, and that will
		// cause some problems.
		// 
		// < The last problem mentionned was that the Module class hierarchy
		// loading was broken by $module->basePath being NULL. I have since added
		// a bit of documentation specifying that $basePath had to be NULL when
		// the module didn't have a concrete directory in the top level 
		// location.
		// 
		// That fixes both problems mentionned here, and probably responds to
		// the expectations of existing methods which use $basePath. That's 
		// quite weird however... $basePath and existing methods using it should
		// probably be refactored so that $basePath always point to the top
		// level concrete directory in the location hierarchy, while keeping the
		// whole parent location hierarchy information in the module->location,
		// as done above. That would more acurately meet developpers' 
		// expectations!
		// 
		return new $class(ModuleLocation::createTopLevelLocation($this->getTopLevelDirectory(), $name));
	}
	
	/**
	 * Generates a default module class and instanciates it, according to its
	 * configuration file "class" item.
	 * @param ModuleLocation $location
	 * @param Config $config
	 * @param boolean setExtraConfig if set to TRUE, the passed configuration
	 * will be applied as the last (topmost) layer, thus overridding every
	 * config inherited by parent modules. If set to FALSE, the passed $config
	 * will only be used to drive the module creation. It is not necessary to
	 * apply as an extra config the natural configuration of the module (that
	 * is, the config naturally falling under the rules of the module 
	 * configuration inheritance), since this configuration item will naturally
	 * be the topmost one. It can be useful to force the inheritance though, in
	 * the case of autogenerated configuration values that the normal config
	 * inheriting process would not consider.
	 * @params &$cacheDeps
	 */
	public function createDefaultModule(ModuleLocation $location, $config, 
			$setExtraConfig = true, &$cacheDeps = null) {
		
		$config = Config::createForNode($config, $location->moduleName);
		if (isset($config[$location->moduleName])) $config = $config->node($location->moduleName, true);
		
		if (!isset($config['class'])) {
			// this is a base module, in the vertical hierarchy (direct descendant
			// of Module) -- it cannot be created from config, let's return false
			// to let the ModuleManager find and instanciate the module class
			return false;
		}
		
		$class = $location->namespace . $location->moduleName;

		// Generate the module class, if needed
		if (!class_exists($class)) {
			$baseModule = self::getModule($config->class);
			$cacheDeps[] = $this->inNamespace("ModuleManager::getModule('$config->class');");
			// use the base module to generate the default class
			if (is_array($cacheDeps)) {
				$cacheDeps[] = $baseModule->generateDefaultModuleClass($class, $config);
			}
		}
		
		// create an instance of the newly created class
		$module = $this->createModule($location->moduleName, $class);
		if ($setExtraConfig) $module->setExtraConfig($config);
		return $module;
	}
	
	private function inNamespace($code) {
		$ns = __NAMESPACE__;
		return "namespace $ns { $code }";
	}

}
