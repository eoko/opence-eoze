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

class Module implements file\Finder {
	
	const DEFAULT_EXECUTOR           = '';
	const DEFAULT_INTERNAL_EXECUTOR  = '_';

	protected $requestActionParam = 'action';
	protected $defaultAction = 'index';

	protected $name;


	protected $namespace;
	protected $basePath;
	protected $baseUrl;
	
	private $pathsUrl;
	/** @var array[ModulesLocation] */
	private $lineageLocations;

	/** @var Config */
	private $config = null;
	
	/** @var file\Finder */
	private $fileFinder = null;
	
	protected $createExecutorMethodNameFormat = 'create%sExecutor'; // TODO unused
	protected $defaultExecutor = 'html';
	protected $defaultInternalExecutor = self::DEFAULT_EXECUTOR;
	private $executorClassNames = null;

	public function __construct($name, $basePath, $url, ModulesLocation $baseLocation) {

		// These 3 could be known without having to pass them as params
		$this->name = $name;
		$this->basePath = $basePath;
		$this->baseUrl = $url;

		$this->namespace = get_namespace($this);

		$lineage = $this->getParentNames(true);
		// purge dupplicates
		$lineageItems = array();
		foreach ($lineage as $item) $lineageItems[$item] = true;
		$lineage = array_keys($lineageItems);

		$this->pathsUrl = $baseLocation->getLineagePathsUrl($lineage);
		$this->lineageLocations = $baseLocation->getLineageLocations($lineage);

//		dumpl(array(
//			$name,
//			$this->pathsUrl,
////			$basePath,
////			$baseLocation,
////			$baseLocation->getParentPathsUrl($name),
////			$this->basePath,
//		));

	}

	private function getParentNames($includeSelf) {
		$parents = array();
		if ($includeSelf) $parents[] = get_relative_classname($this);
		$last = $this;
		while (false !== $class = get_parent_class($last)) {
			$last = $class;
			$parents[] = relative_classname($class);
		}
		return $parents;
	}

//REM	protected static function addPathsUrl(&$pathsUrl, $path, $url = null) {
//		if ($pathsUrl === null) $pathsUrl = array();
//		if (is_array($path)) {
//			if ($url !== null) throw new IllegalArgumentException(
//				'If the first argument is an array, then only one argument must be passed'
//			);
//			foreach ($path as $path => $url) {
//				$pathsUrl[$path] = $url;
//			}
//		} else {
//			$pathsUrl[$path] = $url;
//		}
//		return $pathsUrl;
//	}
	
	public static function create($name, $path, $url) {
		$class = get_called_class();
		return new $class($name, $path, $url);
	}
	
	public function setConfig($config) {
		if (!($config instanceof \Config) && !(is_string($config) && file_exists($config))) {
			$config = new Config();
		}
		$this->config = $config;
	}

	/**
	 * @return Config
	 */
	public function getConfig() {
		if ($this->config) {
			if ($this->config instanceof \Config) {
				return $this->config;
			} else if (is_string($this->config)) {
				$this->config = Config::load($this->config);
				if (isset($this->config[$this->name])) {
					$this->config = $this->config->node($this->name);
				}
				return $this->config;
			} else {
				throw new IllegalStateException('Invalid type for $this->config: ' 
						. get_class($this->config));
			}
		} else {
			throw new IllegalStateException(get_class($this) . ' module has no config');
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

// <editor-fold defaultstate="collapsed" desc="REM">
	/**
	 * Get this Module's {@link Executor} as specified by its $type.
	 * 
	 * An empty string or NULL for the $executorClass means the default
	 * Executor; '_' for the $executorClass means the default 
	 * {@link InternalExecutor}.
	 * 
	 * @param string $type            the class of the Executor to get.
	 * NULL or an empty string means this Module's default Executor (that is 
	 * the html executor, in the base Module implementation).
	 * 
	 * @param boolean $fallbackExecutor        name of the Executor to fall back
	 * on, if a declared Executor for the given $executorClass is not found. 
	 * Set to FALSE to disable the fallback mecanism and require the specified
	 * $executorClass (a MissingExecutorException will be thrown if the 
	 * specified executor doesn't exist).
	 * 
	 * This parameter doesn't have any effect if the default Executor or the
	 * default internal Executor is required by setting $executorClass to 
	 * {@link self::DEFAULT_EXECUTOR} {@internal (or NULL)} or 
	 * {@link self::DEFAULT_INTERNAL_EXECUTOR}
	 * 
	 * @return Executor
	 */
//	private function doGetExecutor($type, $internal, $action, Request $request = null, $fallbackExecutor = false) {
//
//		if ($type === null) $type = self::DEFAULT_EXECUTOR;
//		else if ($type instanceof Executor) $type = $type->getName(); // we're creating a new executor
//
//		if (isset($this->executorClassNames[$type])) {
//			if ($type === self::DEFAULT_EXECUTOR && $this->defaultExecutor) {
//				$type = $this->defaultExecutor;
//			} else if ($type === self::DEFAULT_INTERNAL_EXECUTOR && $this->defaultInternalExecutor) {
//				$type = $this->defaultInternalExecutor;
//			}
//			// already loaded
//			return $this->createExecutor($type, $internal, $action, $request);
//
//		} else if ($type === self::DEFAULT_EXECUTOR) {
//			// redirect (we cannot find a file with the type)
//			return $this->getDefaultExecutor($internal, $action, $request);
//
//		} else if ($type === self::DEFAULT_INTERNAL_EXECUTOR) {
//			// redirect (we cannot find a file with the type)
//			return $this->getDefaultInternalExecutor($internal, $action, $request);
//
//		} else if ($this->loadExecutorClass($type)) {
//			// bingo!
//			return $this->createExecutor($type, $internal, $action, $request);
//
//		} else if ($fallbackExecutor !== false) {
//			// fallback
//			throw new Exception('Deprecated!!!');
//			return $this->getExecutor($fallbackExecutor, false);
//
//		} else {
//			throw new MissingExecutorException($this, $type);
//		}
//	}
//
//	protected function generateDefaultExecutorClass($type, $config) {
//		$type = ucfirst($type);
//		if (method_exists($this, $m = "generateDefault{$type}ExecutorClass")) {
//			return $this->$m($config);
//		} else {
//			return false;
//		}
//	}
//
//	/**
//	 * Create the default executor for this controller.
//	 *
//	 * The following applies to the base Module implementation, this behaviour
//	 * may be overridden by Module subclasses:
//	 *
//	 * The default executor that is the Module's html Executor. If no such
//	 * executor is declared by this Module, a default BasicHtmlExecutor will be
//	 * instanciated and returned.
//	 *
//	 * @return Executor
//	 */
//	protected function getDefaultExecutor($internal, $action, Request $request = null) {
//
//		if (null !== $type = $this->defaultExecutor) {
//
//			$this->executorClassNames[self::DEFAULT_EXECUTOR] =&
//					$this->executorClassNames[$type];
//
//			return $this->doGetExecutor($type, $internal, $action, $request, false);
//		} else {
//			throw new MissingExecutorException($this, self::DEFAULT_EXECUTOR);
//		}
//	}
//
//	/**
//	 * @return Executor
//	 */
//	protected function getDefaultInternalExecutor($internal, $action, Request $request) {
//		if (null !== $type = $this->defaultInternalExecutor) {
//
//			$this->executorClassNames[self::DEFAULT_INTERNAL_EXECUTOR] =&
//					$this->executorClassNames[$type];
//
//			return $this->doGetExecutor($type, $internal, $action, $request, false);
//		} else {
//			throw new MissingExecutorException($this, self::DEFAULT_INTERNAL_EXECUTOR);
//		}
//	}
// </editor-fold>

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
		}

		$type = self::sanitizeExecutorName($type);

		$locations = $this->lineageLocations;

		if (!$locations) {
			return false;
		}

		if ($locations[0]->namespace === $this->namespace) {
			array_shift($locations);
		}

		// if there is a user-defined class in the top level module directory
		if (null !== $classFile = $this->findExecutorClassFile($type, $this->basePath, $this->name)) {
			$finalClassLoader = function() use($classFile) {
				require_once $classFile;
			};
		} else {
			$ns = $this->namespace;
			$finalClassLoader = function() use($type, $ns) {
				$type = ucfirst($type);
				class_extend($type, "$ns{$type}Base", $ns);
			};
		}

		$prevNamespace = null;

		$baseLoaders = array();

		foreach ($locations as $location) {
			if (null !== $classFile = $this->findExecutorClassFile(
					$type, $location->path, $location->name)) {

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

		
		if (null !== $classCode = $this->loadExecutorClass($type)) {
			$this->registerExecutorBaseClassLoader($type, $class);

			$this->executorClassNames[$type] = $class = $this->findExecutorClassName($type);
			return $class;
		} else {
			throw new MissingExecutorException($this, $type);
		}

//		$parents = array($prev = get_class($this));
//		$parents[] = $prev = get_parent_class($prev);
//		$parents[] = $prev = get_parent_class($prev);
//
//		dump(array(
//			$parents,
//			self::each($parents, function($name) {
//				return class_exists($name, false);
//			}),
//		));

////		dump($this->location);
//
//		if (($filename = $this->searchPath($possibleClassFiles, FileType::PHP))) {
//			require_once $filename;
//			return true;
//		}
//		// If a parent superclass exists, a filename must be found. That would
//		// indeed be the case if either the parent or the child module declares
//		// a file matching the executor $type.
//
//		// TODO this doesn't seem like a good idea
//		return class_exists($this->namespace . $type);
//
//		return false;
	}

//	/**
//	 * Creates the executor of the specified $type for this controller.
//	 * @param string $type
//	 * @return Executor
//	 */
//	protected final function doCreateExecutor($type, $internal, $action, Request $request = null) {
//
//		if ((isset($this->executorClassNames[$type]))) {
//			$class = $this->executorClassNames[$type];
//			return new $class($this, $type, $internal, $action, $request);
//		}
//
//		foreach ($this->getAllowedExecutorClassNames() as $class) {
//			if (class_exists($class, false)) {
//				$this->executorClassNames[$type] = $class;
//				return new $class($this, $type, $internal, $action, $request);
//			}
//		}
//
//		// Alias the parent class
//		if ($this->namespace !== MODULES_NAMESPACE && (
//				class_exists($class = MODULES_NAMESPACE . "$this->name\\$type")
//				|| class_exists($class = MODULES_NAMESPACE . "$this->name\\{$type}Executor")
//		)) {
//
//			$ns = rtrim($this->namespace, '\\');
//			// do not use class_alias, to be able to set the namespace
//			// eval("namespace $ns; class $type extends \\$class {}");
//			class_extend($type, $class, $ns);
//
//			$class = "$ns\\$type";
//			return new $class($this, $type, $internal, $action, $request);
//		}
//
//		throw new SystemException(
//			"Missing class for executor: $this->name.$type "
//			. "(expected name: $this->namespace " . implode('|', $this->getAllowedExecutorClassNames()) . ')'
//		);
//	}
	
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
		$this->doGenerateModuleClass($class, $config);
		// TODO cache the returned code

//		class_extend($class, get_class($this));
//		$namespace = get_namespace($class, $class, \GET_NAMESPACE_RTRIM);
//		$r = $this->doGenerateModuleClass($class, $namespace, $config);
//		if ($r instanceof PHPCompiler) {
//			$r->compile();
//			return true;
//		} else {
//			return $r;
//		}
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
			$executor = $controller;
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
	
//	/**
//	 * @param string $controller
//	 * @param boolean|string|Executor $defaultExecutor   FALSE to not try to
//	 * get a default Executor.
//	 * @param boolean $require
//	 * @return Executor
//	 */
//	public static function parseAction($controller, $action, $request, $defaultExecutor = Module::DEFAULT_EXECUTOR, $require = true) {
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
		
		dump("Not yet implemented");
		
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
	
	public function listFilesUrl($pattern, $dir, $type) {
		$r = array();
		foreach ($this->pathsUrl as $basePath => $baseUrl) {
			if ($baseUrl === null) continue;
			// TODO use real declared path for $type
			$typeDir = strtolower($type) . '/' . $dir . ($dir ? '/' : '');
			$path = "$basePath$typeDir";
			$baseUrl .= $typeDir;
			$urls = Files::listFilesIfDirExists($path, $pattern, false, false);
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

				FileType::HTML     => 'html',
				FileType::HTML_TPL => 'html',

				FileType::PHP      => array($path => null),

				FileType::IMAGE    => 'images',
				FileType::PNG      => 'images',
				FileType::JPG      => 'images',
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
