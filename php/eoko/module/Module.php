<?php

namespace eoko\module;

use Request;
use SystemException, SecurityException, UnsupportedOperationException,
	IllegalStateException, IllegalArgumentException;

use eoko\util\Files;
use eoko\file, eoko\file\FileType;
use eoko\config\Config;
use eoko\template\PHPCompiler;
use eoko\module\executor\Executor;
use eoko\log\Logger;
use eoko\cache\Cache;

use eoko\config\ConfigManager;
use eoko\config\Application;
use eoko\php\SessionManager;
use eoko\php\ClassLoader;

/**
 * Base class for Eoze modules.
 *
 * Configuration
 * =============
 *
 * Conditional configuration
 * --------------------------
 *
 * Conditional configuration allows for overrides depending on the execution
 * mode. Any configuration key beginning with a dot (e.g. .dev .prod) will be
 * considered conditional configuration. If the name (without the dot) matches
 * an active mode tag (as decided in {@link eoko\config\Application::isMode()}),
 * then it will be applied to the rest of the module config.
 *
 * Every condition configuration keys, applied or not, will be removed from the
 * actual module config, after it has been constructed.
 *
 * @see eoko\config\Application::isMode()
 */
/** @noinspection PhpInconsistentReturnPointsInspection */
/** @noinspection PhpInconsistentReturnPointsInspection */
class Module implements file\Finder {
	
	const DEFAULT_EXECUTOR           = '';
	const DEFAULT_INTERNAL_EXECUTOR  = '_';

	protected $requestActionParam = 'action';
	protected $defaultAction = 'index';

	protected $name;

	protected $namespace;
	/**
	 * Base path for this module's top level actual directory. If this module
	 * does not have a concrete directory in the top level location, $basePath
	 * will be NULL.
	 * @var string
	 */
	protected $basePath;
	protected $baseUrl;
	
	private $pathsUrl;
	/** @var array[ModulesLocation] */
	private $lineageLocations;
	/** @var ModuleLocation */
	private $location;
	
	private $dependantCacheFiles, $dependantCacheKey;

	/** @var Config */
	private $config = null;
	/** 
	 * @var Config this is used to allow injection of extra configuration,
	 * when the configuration inheritance will take place. The extra config
	 * will be the last config applied, that is, it will override every other
	 * existing items with same names.
	 */
	private $extraConfig = null;
	
	/** @var file\Finder */
	private $fileFinder = null;
	
	protected $createExecutorMethodNameFormat = 'create%sExecutor'; // TODO unused
	protected $defaultExecutor = 'html';
	protected $defaultInternalExecutor = self::DEFAULT_EXECUTOR;
	private $executorClassNames = null;
	
	public final function __construct(ModuleLocation $location) {
		
		$this->name = $location->moduleName;
		$this->basePath = $location->path;
		$this->baseUrl = $location->url;

		$this->location = $location;
		
		$this->namespace = get_namespace($this);

		$lineage = $this->getParentNames(true);
		// purge duplicates
		$lineageItems = array();
		foreach ($lineage as $item) $lineageItems[$item] = true;
		$lineage = array_keys($lineageItems);

		$this->pathsUrl = $location->getDirectory()->getLineagePathsUrl($lineage);
		$this->lineageLocations = $location->getDirectory()->getLineageLocations($lineage);
		
		$this->construct($location);
	}
    
    /**
     * Gets the application config used by this Module.
     * @return Application
     */
    public function getApplicationConfig() {
        return Application::getInstance();
    }
	
	/**
	 * Gets the configuration manager used by this Module.
	 * @return ConfigManager
	 */
	protected function getConfigManager() {
		return ConfigManager::getInstance();
	}
	
	/**
	 * @return SessionManager
	 */
	public function getSessionManager() {
		// We don't keep a reference of the SessionManager because we don't
		// want it to be serialized in the cache (because of the closures
		// it contains)
		return $this->getApplicationConfig()->getSessionManager();
	}
	
	/**
	 * Gets the Eoze's class loader.
	 * @return ClassLoader
	 */
	protected function getClassLoader() {
		return ClassLoader::getInstance();
	}
	
	public static function __set_state($vals) {
		$class = get_called_class();
		/** @var Module $o */
		$o = new $class($vals['location']);
		$o->setPrivateState($vals);
		if (is_array($vals)) {
			foreach ($vals as $k => $v) {
				$o->$k = $v;
			}
		}
		return $o;
	}
	
	protected function setPrivateState(&$vals) {}
	
	protected function construct(ModuleLocation $location) {}
	
	protected function getLocation() {
		return $this->location;
	}

	/**
	 * @return Module[]
	 */
	protected function getParentModules() {
		$r = array();
		foreach ($this->getParentNames(false) as $name) {
			if (null !== $module = ModuleManager::getModule($name, false)) {
				$r[] = $module;
			}
		}
		return $r;
	}
	
	/**
	 * Returns an array of configs for Ext4.Loader.setConfig.
	 * 
	 * Returns an associative array of the form:
	 * 
	 *     array(
	 *         JAVASCRIPT_NAMESPACE => BASE_URL,
	 *     )
	 * 
	 * @todo OCE-575 This should be done on a per-module basis (all modules 
	 *       do not necessarily expose Ext4 namespaces).
	 * 
	 * @return array
	 * @since 05/10/12 03:07
	 */
	public function getExt4LoaderConfig() {
		$dir = array();
		foreach ($this->getLocation()->getLineActualLocations(true) as $location) {
			/** @var ModuleLocation $location  */
			$namespace = str_replace(
				array('eoko.', 'rhodia.'), array('Eoze.', 'Opence.'),
				str_replace('\\', '.', rtrim($location->namespace, '\\'))
			);
			$url = $location->getDirectory()->url . $location->moduleName . '/ext';
			$dir[$namespace] = $url;
		}
		return $dir;
	}
	
	/**
	 * @return Module
	 */
	private function getParentModule() {
		if (null !== $name = $this->getParentModuleName()) {
			return ModuleManager::getModule($name);
		} else {
			return null;
		}
	}

	/**
	 * Get the name of the first module ancestor, if any.
	 * @internal This method walks up the php classes hierarchy, using
	 * ModuleManager to see if an ancestor is a module of its own. The name
	 * of the first class, that is not the same as this module's class (which
	 * means that the class is a parent in the lineage, the vertical inheritance
	 * chain) and that is an eoze module will be returned.
	 * @return string
	 */
	private function getParentModuleName() {
		foreach ($this->getParentNames(false) as $p) {
			if ($p !== $this->name && ModuleManager::getModule($p, false)) {
				return $p;
			}
		}
		return null;
	}
	
	public function getParentNames($includeSelf) {
		$parents = array();
		$lastRelative = get_relative_classname($this);
		if ($includeSelf) {
			$parents[] = $lastRelative;
		}
		$last = $this;
		while (false !== $class = get_parent_class($last)) {
			$last = $class;
			$rc = relative_classname($class);
			if ($lastRelative !== $rc) {
				$parents[] = $rc;
			}
			$lastRelative = $rc;
		}
		return $parents;
	}

	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function getParentClasses($includeSelf) {
		$classes = array();
		if ($includeSelf) {
			$classes[] = get_class($this);
		}
		$class = $includeSelf ? get_class($this) : get_parent_class($this);
		do {
			$classes[] = $class;
			$class = get_parent_class($class);
		} while ($class !== false);
//		while (false !== $class = get_parent_class($last)) {
//			$classes[] = $class;
//		}
		return $classes;
	}

	public static function create($name, $path, $url) {
		$class = get_called_class();
		return new $class($name, $path, $url);
	}
	
	public function setConfig() {
		Logger::get($this)->warn(<<<MSG
Module::setConfig() is not used anymore... Because the getConfig method has to
load it anyway, in order to account for horizontal (lineage) inheritance.
MSG
		);
		return;
	}
	
	public function isAbstract() {
		return $this->getConfig()->get('abstract', false);
	}
	
	public function isDisabled() {
		return $this->location->isDisabled();
	}

	public function setExtraConfig($config) {
		$this->extraConfig = $config;
	}
	
	/**
	 * @return Config
	 */
	public function getConfig() {
		if ($this->config) {
			return $this->config;
		} else if ($this->loadCachedConfig()) {
			return $this->config;
		}
		
		// generate
		$this->doConfig();

		// cache
		$this->cacheConfig($this->config);
		
		return $this->config;
	}
	
	private function doConfig() {
		$this->onConfig();
		$this->processConditionalConfig();
	}

	/**
	 * Builds module configuration.
	 * @return Config
	 */
	protected function onConfig() {
		$this->config = new Config();

		if (null !== $parent = $this->getParentModule()) {
			$this->config->apply($parent->getConfig());
		}

		unset($this->config['private']);
		unset($this->config['abstract']);
		unset($this->config['line']);
		unset($this->config['jsClass']);

		$this->config->apply($this->location->loadConfig());

		if ($this->extraConfig) {
			if (!$this->config) {
				$this->config = $this->extraConfig;
			} else {
				$this->config->apply($this->extraConfig);
			}
		}
		
		return $this->config;
	}
	
	/**
	 * Get the application config node path that overrides this module's hardcoded
	 * configuration. Defaults to the module namespace.
	 * @return string
	 */
	protected function getConfigNodePath() {
		return rtrim($this->namespace, '\\');
	}
	
	private function processConditionalConfig() {
		// Conditional configuration
		if (isset($this->config[''])) {
			$app = $this->getApplicationConfig();
			foreach ($this->config[''] as $tag => $envConfig) {
				if ($app->isMode($tag)) {
					$this->config->apply($envConfig);
				}
			}
			// clear
			unset($this->config['']);
		}
	}
	
	private function useCache() {
		return true;
	}
	
	/**
	 * Set the Module's config cache dependency. That is, if one of the given
	 * file changed later, the config cache for this module will be invalidated.
	 * If the second argument is passed, the dependency will be registered both
	 * way (warning, that would overwrite any existing monitor on the passed
	 * $configKey).
	 *
	 * @param array $dependantCacheFiles
	 * @param mixed $cacheKey If set, the dependency will be registered both
	 * way, that is the $cacheKey will be invalidated when this module's config
	 * cache is invalidated.
	 */
	public function setCacheDependencies($dependantCacheFiles, $cacheKey = null) {
		$this->dependantCacheFiles = $dependantCacheFiles;
		$this->dependantCacheKey = $cacheKey;
		Cache::flattenKey($this->dependantCacheKey);
	}
	
	public function getCacheMonitorFiles($parents = false) {
		$r = $this->location->listFileToMonitor();
		if ($parents) {
			foreach ($this->getParentModules() as $parent) {
				$r = array_merge($r, $parent->location->listFileToMonitor());
			}
		}
		return $r;
	}
	
	private function cacheConfig(Config $config) {
		if (!$this->useCache()) {
			return false;
		}
		$key = array($this, 'config');
		$cacheFile = Cache::cacheObject($key, $config);
		
		if ($this->dependantCacheFiles) {
			Cache::monitorFiles($key, $this->dependantCacheFiles);
		}
		
		// reciprocate dependency
		if ($this->dependantCacheKey) {
			Cache::monitorFiles($this->dependantCacheKey, $cacheFile);
		}
		return null;
	}
	
	private function loadCachedConfig() {
		if (!$this->useCache()) {
			return false;
		} else if (null !== $config = Cache::getCachedData(array($this, 'config'))) {
			$this->config = $config;
			return true;
		} else {
			return false;
		}
	}
	
	public function __toString() {
		return $this->name;
	}
	
	public function getName() {
		return $this->name;
	}

	public function getBasePath() {
		if ($this->basePath === null) {
			throw new IllegalStateException("Module $this->name has no directory");
		} else {
			return $this->basePath;
		}
	}
	
	public function getBaseUrl() {
		if ($this->basePath === null) {
			throw new IllegalStateException("Module $this->name url is undefined");
		} else {
			return $this->baseUrl;
		}
	}

	public function executeRequest(Request $request) {
		$executor = $this->createRequestExecutor($request);
		return $executor();
	}

	/**
	 * Securize an executor type name by forcing the absence of characters that
	 * could allow to resolve a path upper than the module's one, while
	 * searching for the executor class.
	 *
	 * If a forbidden character (that is, '.' '/' or '\') is found, a
	 * SecurityException is thrown.
	 *
	 * @param string $name
	 * @throws \SecurityException
	 * @return string
	 */
	protected static function sanitizeExecutorName($name) {
		if (preg_match('/[.\/\\\\]/', $name)) {
			throw new SecurityException("Illegal name: $name");
		}
		return $name;
	}

	/**
	 * @param \Request $request
	 * @param string $overrideExecutor
	 * @return Executor
	 */
	public function createRequestExecutor(Request $request, $overrideExecutor = null) {
		
		if ($overrideExecutor === null) {
			$overrideExecutor = $request->get('executor', self::DEFAULT_EXECUTOR);
		}
		
		return $this->createExecutor(
			$overrideExecutor,
			$request->get('action', null), 
			$request,
			false
		);
	}

	/**
	 * Creates an Executor for the given $type, with the given parameters.
	 *
	 * @param string $type
	 * @param string $action
	 * @param Request $request
	 * @param bool $internal
	 *
	 * @throws MissingExecutorException
	 * @return Executor
	 */
	public function createExecutor($type, $action = null, Request $request = null, $internal = false) {
		
		if ($type === null || $type === self::DEFAULT_EXECUTOR) $type = $this->defaultExecutor;
		else if ($type === self::DEFAULT_INTERNAL_EXECUTOR) $type = $this->defaultInternalExecutor;

		$class = $this->loadExecutorTopLevelClass($type);

		if (!$class) {
			throw new MissingExecutorException($this, $type);
		}

		return new $class($this, $type, $internal, $action, $request);
	}

	/**
	 * @param string $type
	 * @param string $action
	 * @param \Request $opts
	 * @param string $fallbackExecutor
	 * @return Executor
	 */
	public function getInternalExecutor($type, $action, Request $opts = null,
				/** @noinspection PhpUnusedParameterInspection */ $fallbackExecutor = self::DEFAULT_INTERNAL_EXECUTOR) {
		if ($type === null) {
			$type = $this->defaultInternalExecutor;
		}
		return $this->createExecutor($type, $action, $opts, true);
	}

	/**
	 * Tries to find the class file/code for the Executor of the given $type at this
	 * Module own level in its lineage. That is, this method should not try to
	 * load lower level classes.
	 *
	 * This method can be overridden by each level implementation of the Module.
	 *
	 * The method must return either the path to the file that contains the
	 * declaration of the class (that is the file to be included), or a
	 * {@link PHPCompiler} containing its code. If a PHPCompiler is returned,
	 * it must not be executed since some operations must be perform before
	 * (setting the base class loaders).
	 *
	 * If the method fails to find a class for the given $type at its own level,
	 * it should return its parent::loadExecutorClass(), so that all its lineage
	 * can be walked down.
	 *
	 * @param string $type
	 * @param string $path
	 * @return string|PHPCompiler
	 */
	protected function loadExecutorClass($type, $path) {
	}

	private function findExecutorClassFile($type, $path, $name) {

		if (!$path) {
			return null;
		}

		$possibleClassFiles = array(
			"$name.$type.class.php",
			"$type.class.php",
			$type . "Executor.class.php",
			"$name.$type.php",
			"$type.php",
			"{$type}Executor.php",
			ucfirst($type) . "Executor.class.php",
		);

		foreach ($possibleClassFiles as $file) {
			if (file_exists($classPath = "$path$file")) {
				return $classPath;
			}
		}

		return null;
	}

	/**
	 * Finds the class name for the Executor of the given type, at this Module
	 * own level (not lower in its lineage).
	 *
	 * This method is called after the class code has been loaded, so it should
	 * absolutely not try to load it. It must just try all allowed class names
	 * for an Executor of this type. If you use the PHP class_exists method to
	 * test for class existence, do not forget to specify the second argument
	 * as FALSE, in order to prevent it from trying to autoload the class
	 * (which, as said, as already been loaded).
	 *
	 * If it fails to find the class name, the method could either delegate the
	 * job to its parent::findExecutorClassName method, or return NULL. Mind
	 * that the later case will eventually result in a MissingExecutorException,
	 * though.
	 *
	 * @param string $type
	 * @param $ns
	 * @throws \IllegalStateException
	 * @return string
	 */
	public function findExecutorClassName($type, $ns) {

		$type = ucfirst($type);

		$allowedClassNames = array(
			"$ns$type",
			"$ns$this->name$type",
			"$ns{$type}Executor",
			"$ns$this->name{$type}Executor",
		);

		foreach ($allowedClassNames as $class) {
			if (class_exists($class, false)) return $class;
		}

		throw new IllegalStateException("Cannot find class for executor $type in $ns");
	}

	/**
	 * This method can be used to generate a base class for the Executor of the
	 * given type. If a class is generated, it will be used as the base class
	 * for the top level class for this Executor type.
	 *
	 * The top level class is the class defined in the top level module
	 * directory, if there is one. If no such class exists, then the top level 
	 * class will be a class aliasing the first defined class found in the 
	 * module lineage (that would end to be the Executor class if no class is 
	 * defined for this executor type in the module lineage).
	 * 
	 * {@internal The complete Executor classes hierarchy will be as follow:
	 * 
	 * Executor 
	 * [<- TypeExecutor0i] 
	 * [<- GenExecutorBase] 
	 * <- [Executor defined in TL dir] || [Alias prev Executor in lineage] 
	 * 
	 * Executor
	 * 
	 * <- TypeExecutor0 <- GeneratedExecutor0
	 * <- TypeExecutor1 <- GeneratedExecutor1
	 * ...
	 * 
	 * <- [TL executor [<- TL GeneratedExecutor]] || Alias of the last Executor in the lineage
	 * }
	 * 
	 * The legitimate use of a generated base class is to push the values of
	 * configuration options into the PHP code. This method must return boolean
	 * FALSE if no generated base class must be used.
	 * 
	 * If a generated base class is to be used, this method can return either
	 * a {@link PHPCompiler} or a string containing the code of the class.
	 * 
	 * @param string $type Executor type
	 * @param string $namespace the namespace in which the class must be 
	 * generated
	 * @param string $className the name that the generated class must use
	 * @param string $baseClass the fully qualified name of the class the
	 * generated class must extend
	 *
	 * @return PHPCompiler|string|boolean
	 */
	protected function generateExecutorBase($type, $namespace, $className, $baseClass) {
		if (method_exists($this, $m = "generate{$type}ExecutorBase")
			|| method_exists($this, $m = "generate{$type}ExecutorBaseClass")) {
				
			$namespace = trim($namespace, '\\');
			if (substr($baseClass, 0, 1) !== '\\') $baseClass = "\\$baseClass";

			return $this->compile(
				$this->$m($namespace, $className, $baseClass)
			);
		} else {
			return false;
		}
	}

	/**
	 * Centralizes the compilation of PHPCompiler or code fragments, to enable
	 * the implementation of a coherent caching strategy for these compilation
	 * operations.
	 *
	 * @param PHPCompiler $src
	 * @throws \IllegalArgumentException
	 * @throws \UnsupportedOperationException
	 * @return boolean TRUE if the passed $src was successfully compiled, FALSE
	 * if the passed $src is not compilable (actually if $src is neither a
	 * string or a PHPCompiler)
	 */
	private function compile($src) {
		if ($src === false || $src === null) {
			return false;
		} else if ($src instanceof PHPCompiler) {
			$src->compile();
			return true;
		} else if (is_string($src)) {
			throw new UnsupportedOperationException('Not implemented yet');
		} else {
			throw new IllegalArgumentException('$src must be a PHPCompiler or a string');
		}
	}

	/**
	 * Walks down the Module's lineage to find the top level executor class for
	 * the given $type.
	 * @param string $type
	 * @return string the fully qualified class name
	 */
	private function loadExecutorTopLevelClass($type) {
		
		if (isset($this->executorClassNames[$type])) {
			// The class has already been loaded
			return $this->executorClassNames[$type];
		} else {
			return $this->executorClassNames[$type] = $this->doLoadExecutorTopLevelClass($type);
		}
	}
	
	private function doLoadExecutorTopLevelClass($type) {
		
		$type = self::sanitizeExecutorName($type);

		$locations = $this->lineageLocations;

		if (!$locations) {
			$locations = array();
		} else {
			if ($locations[0]->namespace === $this->namespace) {
				array_shift($locations);
			}
		}

		$basePath = $this->location->findTopLevelActualLocation()->path;
		
		// if there is a user-defined class in the top level module directory
		//
		// NB. We must not search if this file exist in $this->basePath, but
		// in $basePath, which is the $path of the TopLevelActualLocation of
		// this module.
		// 
		// In fact, I'm not sure for now that it is the right place to search
		// but, for sure, $this->path is not, since it can easily be NULL when
		// the Module is not overridden at the topmost level(s).
		//
		if (null !== $classFile = $this->findExecutorClassFile($type, $basePath, $this->name)) {
			$finalClassLoader = function() use($classFile) {
				/** @noinspection PhpIncludeInspection */
				require_once $classFile;
			};
		} else {
			$ns = $this->namespace;
			$finalClassLoader = function() use($type, $ns) {
				// We must test that the class doesn't already exist, for the 
				// case where the module itself has not been overridden at all.
				// 
				// eg. if the module root is not overridden at all
				// (ie. there is only one dir root in only one modules dir), then 
				// $this->namespace will be the same as the original module,
				// and the class will have already been loaded).
				// 
				// TODO: what if the module is just not present at the topmost
				// level ?
				//
				if (!class_exists("$ns$type")) {
					$type = ucfirst($type);
					class_extend($type, "$ns{$type}Base", $ns);
				}
			};
		}
        
		$prevNamespace = null;

		$baseLoaders = array();
        
		foreach ($locations as $location) {
			if (null !== $classFile = $this->findExecutorClassFile(
					$type, $location->path, $location->moduleName)) {

				$baseLoaders[] = array(
					$classFile,
					$prevNamespace,
					$location->namespace,
				);
				$prevNamespace = $location->namespace;
			}
		}

		$myBaseClass = ucfirst("{$type}Base");

		foreach (array_reverse($baseLoaders) as $file) {
			list($file, $ns, $searchNS) = $file;
			/** @noinspection PhpIncludeInspection */
			require_once $file;

			$baseClass = $this->findExecutorClassName($type, $searchNS);

			if ($ns !== null) {
				// 04/12/11 05:51
				// Adding if (!class_exists(...))
				if (!class_exists("$ns$myBaseClass", false)) {
					class_extend("$ns$myBaseClass", $baseClass);
				}
			} else {
				// We are finally creating the base class for the TL executor
				if (!$this->generateExecutorBase($type, $this->namespace, $myBaseClass, $baseClass)) {
					class_extend("$this->namespace{$type}Base", $baseClass);
				}
			}
		}
		
		$finalClassLoader();

		return $this->findExecutorClassName($type, $this->namespace);
	}

	protected function doGenerateModuleClass($class, /** @noinspection PhpUnusedParameterInspection */ $config) {
		return class_extend($class, get_class($this));
	}

	/**
	 * Generates the default class for a module extending this one.
	 *
	 * @param string $class
	 * @param Config $config
	 * @return string
	 */
	public final function generateDefaultModuleClass($class, $config) {
		return $this->doGenerateModuleClass($class, $config);
		// TODO cache the returned code in file
	}

	public function searchPath($name, $type = null, &$getUrl = false, $forbidUpward = null, $require = false) {
		return $this->getFileFinder()->searchPath($name, $type, $getUrl, $forbidUpward, $require);
	}
	
	public function findPath($name, $type = null, &$getUrl = false, $forbidUpward = null) {
		return $this->getFileFinder()->findPath($name, $type, $getUrl, $forbidUpward);
	}
	
	public function resolveRelativePath($relativePath, $type = null, $forbidUpward = null) {
		return $this->getFileFinder()->resolveRelativePath($relativePath, $type, $forbidUpward);
	}
	
	private function getFileFinder() {
		if ($this->fileFinder) {
			return $this->fileFinder;
		} else {
			return $this->fileFinder = $this->createFileFinder();
		}
	}
	
	/**
	 * @todo Make that a real FileFinder method
	 */
	public function listFiles($pattern, $dir, $type) {
		$files = array();
		foreach ($this->pathsUrl as $basePath => $baseUrl) {
			// TODO use real declared path for $type
			$path = "$basePath" . strtolower($type) . DS . $dir;
			$files = array_merge(
				$files, 
				Files::listFilesIfDirExists($path, $pattern, false, true)
			);
		}
		return $files;
	}
	
	public function listFilesUrl($pattern, $dir, $type = null) {
		$r = array();
		foreach ($this->pathsUrl as $basePath => $baseUrl) {
			if ($baseUrl === null) continue;
			// TODO use real declared path for $type
			$dir = str_replace('\\', '/', $dir);
			$typeDir = ($type ? strtolower($type) . '/' : '') . $dir . ($dir ? '/' : '');
			$path = "$basePath$typeDir";
			$baseUrl .= $typeDir;
			$urls = Files::listFilesIfDirExists($path, $pattern, false, false);
			foreach ($urls as &$url) $url = "$baseUrl$url";
			$r = array_merge($r, $urls);
		}
		return $r;
	}

	public function listLineFilesUrl($pattern, $dir, $recursive = false) {
		$r = array();
		if ($dir) {
			$urlDir = str_replace('\\', '/', $dir) . '/';
		} else {
			$urlDir = '';
		}
		foreach (array_reverse($this->location->getLineActualLocations()) as $loc) {
			/** @var ModuleLocation $loc */
			if (!$loc->url) continue;
			$baseUrl = $loc->url . $urlDir;
			$urls = Files::listFilesIfDirExists($loc->path . $dir, $pattern, $recursive, false);
			foreach ($urls as &$url) $url = "$baseUrl$url";
			$r = array_merge($r, $urls);
		}
		return $r;
	}
	
	private function createTypeFinder($path, $url, $fallbackFinder) {
		return new file\TypeFinder(
			$path, $url, 
			array(
				null => array($path => ''),
				FileType::CSS      => 'css',

				FileType::JS       => 'js',
				FileType::JS_TPL   => 'js',

				FileType::HTML     => 'html',
				FileType::HTML_TPL => 'html',

				FileType::PHP      => array($path => null),

				FileType::IMAGE    => 'images',
				FileType::PNG      => 'images',
				FileType::JPG      => 'images',
				FileType::GIF      => 'images',
			),
			array(
				'forbidUpwardResolution' => true,
				'fallbackFinder' => $fallbackFinder,
			),
			array(
				'forbidUpwardResolution' => true,
				'fallbackFinder' => $fallbackFinder,
			)
		);
	}
	
	protected function createFileFinder() {
		
		$fallbackFinder = $this->getApplicationConfig();
		
		$upperPathsUrl = array_reverse($this->pathsUrl, true);
		if ($this->basePath) array_pop($upperPathsUrl);
		foreach ($upperPathsUrl as $path => $url) {
			$fallbackFinder = $this->createTypeFinder($path, $url, $fallbackFinder);
		}
		
		return $this->fileFinder = new file\ObjectFinder(
			$this, 
			array(
				'forbidUpwardResolution' => true,
			),
			// fallback
			$this->createTypeFinder($this->basePath, $this->baseUrl, $fallbackFinder)
		);
	}
	
}

class MissingExecutorException extends SystemException {
	
	public function __construct($module, $executor, \Exception $previous = null) {
		parent::__construct(
			'Missing executor "' . $executor . '" for module "' . $module . '"', 
			'', $previous
		);
	}
}
