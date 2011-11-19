<?php

namespace eoko\module;

use Request;
use SystemException, SecurityException, UnsupportedOperationException,
	IllegalStateException, IllegalArgumentException;

use eoko\util\Files;
use eoko\file, eoko\file\FileType;
use eoko\config\Application as ApplicationConfig;
use eoko\config\Config;
use eoko\template\PHPCompiler;
use eoko\module\executor\Executor;
use eoko\log\Logger;
use eoko\cache\Cache;

class Module implements file\Finder {
	
	const DEFAULT_EXECUTOR           = '';
	const DEFAULT_INTERNAL_EXECUTOR  = '_';

	protected $requestActionParam = 'action';
	protected $defaultAction = 'index';

	protected $name;

	protected $namespace;
	/**
	 * Base path for this module's top level actual directory. If this module
	 * doesn't have a concrete directory in the top level location, $basePath 
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
		// purge dupplicates
		$lineageItems = array();
		foreach ($lineage as $item) $lineageItems[$item] = true;
		$lineage = array_keys($lineageItems);

		$this->pathsUrl = $location->directory->getLineagePathsUrl($lineage);
		$this->lineageLocations = $location->directory->getLineageLocations($lineage);
		
		$this->construct($location);
	}
	
	public static function __set_state($vals) {
		$class = get_called_class();
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
	 * chain) and that is an eoze module will be returned).
	 * @return string
	 */
	private function getParentModuleName() {
		foreach ($this->getParentNames(false) as $p) {
			if ($p !== $this->name
					&& ModuleManager::getModule($p, false)) {
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

	public static function create($name, $path, $url) {
		$class = get_called_class();
		return new $class($name, $path, $url);
	}
	
	public function setConfig($config) {
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
		
		$config = new Config();
		
		if (null !== $parent = $this->getParentModule()) {
			$config->apply($parent->getConfig());
		}
		
		unset($config['abstract']);
		unset($config['line']);
		
		$config->apply($this->location->loadConfig());
		
		if ($this->extraConfig) {
			if (!$config) {
				$config = $this->extraConfig;
			} else {
				$config->apply($this->extraConfig);
			}
		}
		
		$this->cacheConfig($config);
		
		return $this->config = $config;
	}
	
	private function useCache() {
		return true;
	}
	
	/**
	 * Set the Module's config cache dependancy. That is, if one of the given
	 * file changed later, the config cache for this module will be invalidated.
	 * If the second argument is passed, the dependancy will be registered both
	 * way (warning, that would overwrite any existing monitor on the passed
	 * $configKey).
	 * @param array $dependantCacheFiles
	 * @param mixed $cacheKey If set, the dependancy will be registered both
	 * way, that is the $cacheKey will be invalidated when this module's config
	 * cache is invalidated.
	 */
	public function setCacheDepencies($dependantCacheFiles, $cacheKey = null) {
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
		
		// reciproque dependancy
		if ($this->dependantCacheKey) {
			Cache::monitorFiles($this->dependantCacheKey, $cacheFile);
		}
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
	 * @return string
	 */
	protected static function sanitizeExecutorName($name) {
		if (preg_match('/[.\/\\\\]/', $name)) {
			throw new SecurityException("Illegal name: $name");
		}
		return $name;
	}

	/**
	 * @return Executor
	 */
	public function createRequestExecutor(Request $request, $overideExecutor = null) {
		
		if ($overideExecutor === null) {
			$overideExecutor = $request->get('executor', self::DEFAULT_EXECUTOR);
		}
		
		return $this->createExecutor(
			$overideExecutor,
			$request->get('action', null), 
			$request,
			false
		);
	}
	
	/**
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
	 * @return Executor
	 */
	public function getInternalExecutor($type, $action, Request $opts = null, $fallbackExecutor = self::DEFAULT_INTERNAL_EXECUTOR) {
		if ($type === null) $type = $this->defaultInternalExecutor;
		return $this->createExecutor($type, $action, $opts, true);
	}

	/**
	 * Tries to find the class file/code for the Executor of the given $type at this
	 * Module own level in its lineage. That is, this method should not try to
	 * load lower level classes.
	 *
	 * This method can be overriden by each level implementation of the Module.
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
	 * absolutly not try to load it. It must just try all allowed class names
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
	 * Centralizes the compilation of PHPCompiler or code framgents, to enable
	 * the implementation of a coherent caching strategy for these compilation
	 * operations.
	 * @param PHPCompiler $src
	 * @return boolean TRUE if the passed $src was successfuly compiled, FALSE
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
		// the Module is not overriden at the topmost level(s).
		//
		if (null !== $classFile = $this->findExecutorClassFile($type, $basePath, $this->name)) {
			$finalClassLoader = function() use($classFile) {
				require_once $classFile;
			};
		} else {
			$ns = $this->namespace;
			$finalClassLoader = function() use($type, $ns) {
				// We must test that the class doesn't already exist, for the 
				// case where the module itself has not been overriden at all. 
				// 
				// eg. if the module root is not overriden at all
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
			require_once $file;

			$baseClass = $this->findExecutorClassName($type, $searchNS);

			if ($ns !== null) {
				class_extend("$ns$myBaseClass", $baseClass);
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
	
	protected function doGenerateModuleClass($class, $config) {
		return class_extend($class, get_class($this));
	}

	/**
	 * Generates the default class for a module extending this one.
	 * @param string $class
	 * @param Config $config
	 * @return void
	 */
	public final function generateDefaultModuleClass($class, $config) {
		return $this->doGenerateModuleClass($class, $config);
		// TODO cache the returned code in file
	}
	
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
	
	public static function parseModuleName($controller) {
		self::parseModule($controller, $module);
		if ($module instanceof Module) return $module->getName();
		else return $module;
	}
	
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
			throw new \eoko\routing\InvalidRequestException(
				"Invalid controller information: {$request->req('controller')}"
			);
		}

		if (!($module instanceof Module)) {
			$module = ModuleManager::getModule($module);
		}
		
		return $module->createRequestExecutor($request, $executor);
	}
	
	public static function parseInternalAction($controller, Request $request, $defaultExecutor = Module::DEFAULT_EXECUTOR) {
		
		self::parseModule($controller, $module, $executor);
		
		if (!($module instanceof Module)) {
			$module = ModuleManager::getModule($module);
		}
		
		throw new IllegalStateException('Not implemented yet');
		
//		$module->getInternalExecutor($executor, $action, $opts, $fallbackExecutor)
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
			$loc instanceof ModuleLocation;
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
		
		$fallbackFinder = ApplicationConfig::getInstance();
		
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
	
	public function __construct($module, $executor, Exception $previous = null) {
		parent::__construct(
			'Missing executor "' . $executor . '" for module "' . $module . '"', 
			'', $previous
		);
	}
}