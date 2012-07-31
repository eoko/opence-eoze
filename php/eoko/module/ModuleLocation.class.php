<?php

namespace eoko\module;

use eoko\module\exceptions\InvalidModuleException;
use eoko\config\Config, eoko\config\ConfigManager;

/**
 * Represents the different locations of one named module.
 */
class ModuleLocation extends Location {

	public $moduleName;
	/** @var ModulesDirectory */
	public $directory;

	/** @var array[ModuleLocation] Cache for actual locations */
	private $actualLocations = null, $locations = null;

	/**
	 * @var mixed Cache for the {@link loadConfig()} method. FALSE if the config
	 * has not been loaded yet, else the value of loadConfig() (that can be NULL).
	 */
	private $configCache = false;
	
	private $configFileListCache = null;

	/**
	 * Creates a new ModuleLocation. If no directory for the module exists in
	 * the location, the $path is set to NULL. This test can be bypassed by
	 * passing a $path to the constructor -- which should be avoided anyway,
	 * except if you know what you are doing.
	 * @param ModulesDirectory $dir
	 * @param string $moduleName
	 * @param string|boolean $path TRUE to let the constructor search for the
	 * module's path, or a value (that can be NULL) to force the location's 
	 * path to be set
	 */
	protected function construct(ModulesDirectory $dir, $moduleName, $path = true) {

		$this->directory = $dir;
		$this->moduleName = $moduleName;

		if ($path === true) {
			$path = is_dir($path = "$dir->path$moduleName") ? $path . DS : null;
		}

		parent::construct(
			$path,
			$path !== null && $dir->url !== null ? "$dir->url$moduleName/" : null,
			"$dir->namespace$moduleName\\"
		);
	}
	
	/**
	 * @return ModulesDirectory
	 */
	public function getDirectory() {
		return $this->directory;
	}
	
	protected function setPrivateState(&$vals) {
		foreach ($vals as $k => $v) {
			$this->$k = $v;
		}
		$vals = array();
	}

	/**
	 * @param ModulesDirectory $dir
	 * @param type $moduleName
	 * @param type $path
	 * @return ModuleLocation
	 */
	public static function create(ModulesDirectory $dir, $moduleName, $path = true) {
		$o = self::createInstance();
		$o->construct($dir, $moduleName, $path);
		return $o;
	}	
	
	/**
	 * Creates a new ModuleLocation object, starting from the top level 
	 * directory, that is, the first directory in the parent hierarchy that
	 * actually contains either a directory or a config file for the given
	 * $moduleName.
	 * @param ModulesDirectory $dir
	 * @param type $moduleName
	 * @return ModuleLocation 
	 */
	public static function createTopLevelLocation(ModulesDirectory $dir, $moduleName) {
		throw new \Exception('This weird method has been deprecated and is planified for removal');
		$location = ModuleLocation::create($dir, $moduleName);
		while ($location->directory->parent 
				&& !$location->isActual() && !$location->searchConfigFile()) {
			
			$location = ModuleLocation::create($location->directory->parent, $moduleName);
		}
		return $location;
	}

	/**
	 * Find the first directory in the parent hierarchy (including this location)
	 * that actually contains either a directory or a config file for this
	 * Location's module (as in $this->moduleName).
	 * 
	 * If no such location can be found, a ModuleLocation set with this one's
	 * directory and a NULL path will be returned.
	 * 
	 * @return ModuleLocation 
	 */
	public function findTopLevelActualLocation() {
		$location = $this;
		while (!$location->isActual() && !$location->searchConfigFile()) {
			if ($location->directory->parent) {
				$location = ModuleLocation::create($location->directory->parent, $this->moduleName);
			} else {
				return ModuleLocation::create($this->directory, $this->moduleName, null);
			}
		}
		return $location;
	}
	
	public function __toString() {
		return "$this->moduleName << $this->directory";
	}
	
	private $configExtensionFiles = null;
	
	private function searchConfigExtensionFiles() {
		if ($this->configExtensionFiles === null) {
			$this->configExtensionFiles = array();
			if ($this->isActual()) {
				$regex = '/^' . preg_quote($this->moduleName) . '\..+\.yml$/';
				foreach (glob($this->path . '*.yml') as $file) {
					$basename = basename($file);
					if (preg_match($regex, $basename)
							&& $basename !== $this->moduleName . '.bak.yml') {
						$this->configExtensionFiles[] = $file;
					}
				}
			}
		}
		return $this->configExtensionFiles;
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
	}
	
	/**
	 * Load the config of all the module's {@link ModuleManager line}. The 
	 * returned Config object is cached for subsequent call to the method.
	 * @return Config or NULL
	 */
	public function loadConfig() {
		if ($this->configCache === false) {
			$this->configCache = $this->doLoadConfig();
		}
		return $this->configCache;
	}
	
	public function listConfigFiles() {
		if ($this->configFileListCache !== null) {
			return $this->configFileListCache;
		}
		$r = array();
		foreach (array_reverse($this->getLocations()) as $location) {
			$r = array_merge($r, $location->searchConfigExtensionFiles());
			$config = $location->searchConfigFile();
			if ($config) {
				$r[] = $config;
			}
		}
		return $this->configFileListCache = $r;
	}
	
	private function doLoadConfig() {
//REM		$r = null;
//		foreach (array_reverse($this->getLocations()) as $location) {
//			$config = $location->searchConfigFile();
//			if ($config) {
//				$config = Config::createForNode($config, $this->moduleName);
//				if ($r === null) {
//					$r = $config;
//				} else {
//					$r->apply($config, false);
//				}
//			}
//		}
		$r = null;
		foreach ($this->listConfigFiles() as $config) {
			$config = Config::createForNode($config, $this->moduleName);
			if ($r === null) {
				$r = $config;
			} else {
				$r->apply($config, false);
			}
		}
		return $r;
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
	 * A module location can be ignored by placing a file named "disabled" in
	 * its directory. This method will return FALSE if such a file is present,
	 * OR if the module doesn't exist in the current location (independantly of
	 * its existence in parent locations). Namely, a module will exists, if it
	 * has a directory with its name in the location, or a configuration file
	 * in the root of the location directory.
	 * @return boolean
	 */
	public function isDisabled() {
		return $this->path === null ? 
				($this->directory->path === null || !$this->searchConfigFile())
				: file_exists($this->path . 'disabled');
	}
	
	/**
	 * Gets the ModuleLocations of this location's module, starting from this
	 * locations and including only location in which a directory for this
	 * module exists.
	 * @return array[ModuleLocation]
	 */
	public function getActualLocations($includeSelf = true) {
		
		if ($this->actualLocations !== null) {
			if ($includeSelf || !$this->path !== null) {
				return $this->actualLocations;
			} else {
				$r = $this->actualLocations;
				array_unshift($r);
				return $r;
			}
		}

		$this->actualLocations = array();

		// if _this_ is an actual location
		if ($this->path !== null) {
			$this->actualLocations[] = $this;
		}

		// search parents
		$dir = $this->directory->parent;
		while ($dir) {
			if (is_dir($path = "$dir->path$this->moduleName")) {
				$this->actualLocations[] = ModuleLocation::create($dir, $this->moduleName, $path . DS);
			}
			$dir = $dir->parent;
		}

		return $this->getActualLocations($includeSelf);
	}

	public function getLineActualLocations($includeSelf = true) {
		$r = array();
		foreach ($this->getActualLocations($includeSelf) as $loc) {
			if ($loc->moduleName === $this->moduleName) $r[] = $loc;
		}
		return $r;
	}

	public function getLocations($includeSelf = true) {

		if ($this->locations !== null) {
			if ($includeSelf) {
				return $this->locations;
			} else {
				$r = $this->locations;
				array_unshift($r);
				return $r;
			}
		}

		$this->locations = array($this);

		$dir = $this->directory->parent;
		while ($dir) {
			$this->locations[] = ModuleLocation::create($dir, $this->moduleName);
			$dir = $dir->parent;
		}

		return $this->locations;
	}
	
	private function getModuleClassPattern() {
		return array(
			"$this->moduleName.class.php", 
			'module.class.php', 
			"{$this->moduleName}Module.class.php",
			"$this->moduleName.php", 
		);
	}

	/**
	 * Searches the location for a file matching the module class file pattern
	 * and, if one is found, returns the module class' name.
	 * @return string The qualified class name, or NULL.
	 * @throws InvalidModuleException if a matching file is found but doesn't
	 * contain a class that matches the module classes naming pattern.
	 */
	public function searchModuleClass(&$cacheDeps = null) {

		foreach ($this->getModuleClassPattern() as $file) {
			if (file_exists($file = "$this->path$file")) {
				require_once $file;
				if (is_array($cacheDeps)) {
					$cacheDeps[] = "require_once '$file';";
				}
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

	public function listFileToMonitor() {
		$r = array();
		
		$dirPaths = array();
		foreach (ModuleManager::listModuleDirectories() as $dir) {
			$dirPaths[] = $dir->path;
		}
		
		$ds = DIRECTORY_SEPARATOR;
		foreach ($dirPaths as $path) {
			// add the directory with the module name
			// (all the content will be checked)
			$r[] = $path . $this->moduleName;
			// and the simple module config file
			$r[] = $path . $this->moduleName . '.yml';
		}
		
		return $r;
	}

	public function searchModuleSuperclass(&$cacheDeps) {
		foreach ($this->getActualLocations(false) as $location) {
			if (null !== $class = $location->searchModuleClass($cacheDeps)) {
				return $class;
			}
		}
		return null;
	}

}