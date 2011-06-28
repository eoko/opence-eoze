<?php

namespace eoko\module;

use eoko\util\Files;

class ModulesDirectory extends Location {

	/** @var ModulesDirectory */
	public $parent;
	/**
	 * @var string The module name, as it is referred in the config. This name
	 * is either extracted from the "locations" param in the config, or an
	 * arbitrary bame can be given when the ModulesDirectory instance is
	 * created. This name can be used in other configuration items to refer to
	 * this particular ModulesDirectory, so it must be unique.
	 */
	public $name;

	protected function construct($name, $path, $url, $namespace, ModulesDirectory $prev = null) {
		if (substr($path, -1) !== DS) $path .= DS;
		if (substr($url, -1) !== '/') $url .= '/';
		if (substr($namespace, -1) !== '\\') $namespace .= '\\';
		parent::construct($path, $url, $namespace);
		$this->parent = $prev;
		$this->name = $name;
	}
	
	public static function create($name, $path, $url, $namespace, ModulesDirectory $prev = null) {
		$o = self::createInstance();
		$o->construct($name, $path, $url, $namespace, $prev);
		return $o;
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
			$locations[] = ModuleLocation::create($this, $moduleName, $path);
		} else if (file_exists($path = "$this->path{$moduleName}.yml")) {
			$locations[] = ModuleLocation::create($this, $moduleName, null);
		}
		if ($this->parent) {
			$locations = array_merge($locations, $this->parent->getLocations($moduleName));
		}
		return $locations;
	}

	/**
	 * List the Modules in this directory. This method should be avoided when
	 * performance is desired, since it will instanciate all the modules in the
	 * directory, which requires quite a bit of file parsing and config file
	 * reading...
	 * @return array[Module]
	 */
	public function listModules($usedModules = null, $onlyWithDir = false) {
		if ($usedModules === '*') $usedModules = null;
		$r = array();
		if ($this->path) {
			foreach (Files::listDirs($this->path) as $dir) {
				if (($usedModules === null || array_search($dir, $usedModules, true) !== false)
						&& null !== $module = ModuleManager::getModule($dir, false)) {
					$r[] = $module;
				}
			}
			if (!$onlyWithDir) {
				foreach (Files::listFiles($this->path, 'glob:*.yml') as $file) {
					if (($usedModules === null || array_search($dir, $usedModules, true) !== false)
							&& null !== $module = ModuleManager::getModule(substr($file, 0, -4), false)) {
						$r[] = $module;
					}
				}
			}
		}
		return $r;
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