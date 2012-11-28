<?php

namespace eoko\file;
use eoko\options\Options;
use eoko\util\Files, eoko\util\Arrays;
use \Logger;
use \IllegalArgumentException, \IllegalStateException;

//const TYPE_JS		= 'js';
//const TYPE_CSS		= 'css';
//const TYPE_HTML		= 'html';
//const TYPE_PHP		= 'php';
//const TYPE_HTML_TPL	= 'htmltpl';

interface Finder {

	/**
	 * Resolves the absolute path of the given $relativePath, relative to this
	 * Finder's base path. This method doesn't test that the target file
	 * actually exists.
	 * @return string|array the absolute path relative to this Finder's base 
	 * path, or an array of paths when it is the only meaningful return 
	 * (this depends on the nature of the Finder)
	 */
	function resolveRelativePath($relativePath, $type = null, $forbidUpward = null);

	/**
	 * Finds the filename of a file that exists in this Finder's base path or
	 * alternate paths. The returned filename is relative to this finder
	 * base path
	 * @return string the absolute path relative to this Finder's base path
	 * or NULL if this path doesn't point to an actual file (or dir)
	 */
	function searchPath($name, $type = null, &$getUrl = false, $forbidUpward = null, $require = false);

	function findPath($name, $type = null, &$getUrl = false, $forbidUpward = null);
}

/**
 * Boilerplate for Finder implementations.
 */
abstract class FinderBase implements Finder {

	public $cacheFinders = null;
	public $forbidUpwardResolution = false;
	public $fallbackFinder = null;

	public $aliases = null;

	/** @var Finder */
	private static $rootFinder = null;

	public function __construct($opts) {
		Options::apply($this, $opts);
		if ($this->cacheFinders) $this->cacheFinders = array();
	}

	protected function getAliasData($alias) {
		if (!$this->aliases) return null;
		else if (isset($this->aliases[$alias])) $aliasData = $this->aliases[$alias];
		else if (isset($this->aliases[$altName = substr($alias, 1)])) $aliasData = $this->aliases[$altName];
		else return null;
	}

	private function searchAlias($name, $type = null, &$getUrl = false, $forbidUpward = null, $require = false) {

		if ($getUrl === false) {
			throw new IllegalStateException("Missing reference variable to fetch files for alias ($name)");
		}

		if (!($aliasData = $this->getAliasData($name))) {
			return null;
		}

		$paths = array();
		$getUrl = array();

		foreach ($aliasData as $type => $names) {

			if (is_string($names) && substr($names, 0, 1) === '@') {
				// another alias!
				self::$rootFinder->searchPath($names, null, $url, $forbidUpward, $require);
				foreach ($url as $typeName => $pathsUrl) {
					foreach ($pathsUrl as $path => $url) {
						$getUrl[$typeName][$path] = $url;
					}
				}
				continue;
			}

			$typeName = FileType::parse($type, $tryExtensions);

			if (Arrays::isAssoc($names)) {
				foreach ($names as $name => $extra) {
					if (($path = $this->searchPath($name, $type, $url, $forbidUpward, $require))) {
						$getUrl[$typeName][$path] = array(
							'url' => $url,
							'extra' => $extra,
						);
					}
				}
			} else {
				foreach ($names as $name) {
					if (($path = $this->searchPath($name, $type, $url, $forbidUpward, $require))) {
						$getUrl[$typeName][$path] = $url;
					}
				}
			}
		}

		return true;
	}

	public final function searchPath($name, $type = null, &$getUrl = false, $forbidUpward = null, $require = false) {

		if (!self::$rootFinder) {
			self::$rootFinder = $this;
			$rootSearch = true;
		} else {
			$rootSearch = false;
		}

		$typeName = FileType::parse($type, $tryExtensions);

		if (($finder = $this->getFileFinderFor($type))) {
			$r = $finder->searchPath($name, $type, $getUrl, $forbidUpward, $require);
			if ($rootSearch) self::$rootFinder = null;
			return $r;
		}

		$forbidUpward = $forbidUpward || $this->forbidUpwardResolution;

		if (is_string($name) && substr($name, 0, 1) === '@'
				&& $this->searchAlias($name, $type, $getUrl, $forbidUpward, $require)) {
			if ($rootSearch) self::$rootFinder = null;
			return FileType::ALIAS();
		} else if (null !== $path = $this->doSearch($name, $tryExtensions, $getUrl, $forbidUpward, $typeName, $type)) {
			if ($rootSearch) self::$rootFinder = null;
			return $path;
		} else if ($this->fallbackFinder) {
			$r = $this->fallbackFinder->searchPath($name, $type, $getUrl, $forbidUpward, $require);
			if ($rootSearch) self::$rootFinder = null;
			return $r;
		} else if ($require) {
			if ($rootSearch) self::$rootFinder = null;
			throw new CannotFindFileException($name, $type);
		} else {
			if ($rootSearch) self::$rootFinder = null;
			return null;
		}
	}

	public final function findPath($name, $type = null, &$getUrl = false, $forbidUpward = null) {
		return $this->searchPath($name, $type, $getUrl, $forbidUpward, true);
	}

	protected function doSearch($name, $tryExtensions, &$getUrl, $forbidUpward, $typeName, $type) {
		// default behaviour is to find nothing... that will fallback to the
		// fallback finder if one is set
		return null;
	}

	public final function resolveRelativePath($relativePath, $type = null, $forbidUpward = null) {

		if ($type !== null && (($finder = $this->getFileFinderFor($type)) || ($finder = $this->fallbackFinder))) {
			return $finder->resolveRelativePath($relativePath, $type, $forbidUpward);
		}

		if ($forbidUpward === null) $forbidUpward = $this->forbidUpwardResolution;

		return $this->doResolveRelativePath($relativePath, $forbideUpward);
	}

	protected function doResolveRelativePath($relativePath, $forbideUpward) {
		// default behaviour is to find nothing... that will fallback to the
		// fallback finder if one is set
		return null;
	}

	/**
	 * @param string $type
	 * @return Finder
	 */
	protected final function getFileFinderFor($type, $typeName = null) {

		// if type is null, there's no point in parsing it
		if ($type && $typeName === null) $typeName = FileType::parse($type);

		if ($this->cacheFinders) {
			if (array_key_exists($typeName, $this->cacheFinders)) {
				return $this->cacheFinders[$typeName];
			} else {
				return $this->cacheFinders[$typeName] = $this->doGetFinderFor($type, $typeName);
			}
		} else {
			return $this->doGetFinderFor($type, $typeName);
		}
	}

	protected function doGetFinderFor($type, $typeName) {
		// default behaviour is to have no finder for any type, this will let
		// the current finder perform the search
		return null;
	}
}

/**
 * Base class providing utility methods to Finders which need a base path and
 * url for resolving internal paths and urls, but for which these unique path
 * and url are not meaningful per se -- and so should not be used for
 * external resolutions. This is the case, for example, Finders that can 
 * resolve in multiple paths...
 */
abstract class BasePathFinder extends FinderBase {

	protected $basePath;
	protected $baseUrl;

	/**
	 * @param mixed $opts 
	 */
	public function __construct($basePath, $baseUrl, $opts = null) {
		parent::__construct($opts);
		if (substr($basePath,-1) !== DS) $basePath .= DS;
		if (substr($baseUrl, -1) !== '/') $baseUrl .= '/';
		$this->basePath = $basePath;
		$this->baseUrl = $baseUrl;
	}

	/**
	 * Makes the given path and url relative to this finder base path and url,
	 * if they are not absolute. Absolute path follow os standards, but url are
	 * considered absolute if their first char is a '/'. If they are found
	 * absolute in this way, the extra first '/' will be trimmed. This method
	 * also ensure the processed $path and $url are terminated by a 
	 * DIRECTORY_SEPARATOR and a '/'.
	 * @param string $path
	 * @param string $url 
	 * @param boolean $testPathExistence
	 * @return FALSE if $testPathExistence is set to TRUE and the resolved $path
	 * doesn't exist, else return TRUE
	 */
	protected function resolveRelativeIf(&$path = false, &$url = false, $testPathExistence = true) {
		if ($path !== false) {
			if (!Files::isAbsolute($path)) {
				if ($this->basePath === null) {
					Logger::get($this)->warn(
						'Trying to resolve a relative path from a Finder with no base path set ({})',
						$path
					);
					return false;
				} else {
					$path = "$this->basePath$path";
				}
			}
			if ($testPathExistence && !file_exists($path)) {
				return false;
			}
			self::cleanPath($path);
		}
		if ($url !== false) {
			if (substr($url, 0, 1) === '/') {
				// absolute url... we must clean the superfluous / marker though
				$url = substr($url, 1);
				self::cleanUrl($url);
			} else if ($this->baseUrl === null) {
				$url = null;
			} else {
				$url = "$this->baseUrl$url";
				self::cleanUrl($url);
			}
		}
		return true;
	}

	private static function cleanUrl(&$url) {
		if (substr($url, -1) !== '/') $url .= '/';
		return $url;
	}

	private static function cleanPath(&$path) {
		if (substr($path, -1) !== DS) $path .= DS;
		return $path;
	}

// <editor-fold defaultstate="collapsed" desc="REM">
//	protected static function cleanAbsolutePathsUrl($pathsUrl, $testPathsExistence) {
//		$cleanPathsUrl = array();
//		foreach ($pathsUrl as $path => $url) {
//			if (!Files::isAbsolute($path)) {
//				throw new IllegalArgumentException('Paths and urls must be absolute');
//			}
//			if (!$testPathsExistence || file_exists($path)) {
//				$cleanPathsUrl[self::cleanPath($path)] = $url ? self::cleanUrl($url) : null;
//			}
//		}
//		return $cleanPathsUrl;
//	}
// </editor-fold>

	protected function cleanPathsUrl($pathsUrl, $testPathsExistence) {
		$cleanPathsUrl = array();
		foreach ($pathsUrl as $path => $url) {
			if (is_int($path)) {
				if (is_array($url)) {
					// subarray
					if (($cleanSub = $this->cleanPathsUrl($url, $testPathsExistence))) {
						$cleanPathsUrl = array_merge($cleanSub);
					}
				} else {
					// shortcut string form; $path = $url
					$path = $url;
				}
			}
			if ($this->resolveRelativeIf($path, $url, $testPathsExistence)) {
				$cleanPathsUrl[$path] = $url;
			}
		}
		return $cleanPathsUrl;
	}

	protected static function tryFind($basePath, $baseUrl, $name, $tryExtensions, &$getUrl, $forbidUpward, $typeName, $type) {

		if (is_array($name)) {
			foreach ($name as $n) {
				if (($filename = self::tryFind($basePath, $baseUrl, $n, $tryExtensions, $getUrl, $forbidUpward, $typeName, $type))) {
					return $filename;
				}
			}

			return null;
		}

		if (($filename = Files::findIn($basePath, $name, $tryExtensions, $forbidUpward))) {
			if ($getUrl !== false) {
				if ($baseUrl === null) {
					$getUrl = null;
				} else {
					$getUrl = $baseUrl . Files::getRelativePath($basePath, $filename);
					if (DS !== '/') $getUrl = str_replace(DS, '/', $getUrl);
				}
			}

			if ($tryExtensions) {
				$goodExtension = false;
				if (is_array($tryExtensions)) {
					foreach ($tryExtensions as $ext) {
						if (substr($filename, -strlen($ext)) === $ext) {
							$goodExtension = true;
							break;
						}
					}
				} else {
					$goodExtension = substr($filename, -strlen($tryExtensions)) === $tryExtensions;
				}

				if (!$goodExtension) return null;
			}

			return $filename;
		}

		$getUrl = null;
		return null;
	}
}

/**
 * BasicFinder performs a search in its base path.
 */
class BasicFinder extends BasePathFinder {

	protected function doSearch($name, $tryExtensions, &$getUrl, $forbidUpward, $typeName, $type) {
		return self::tryFind(
			$this->basePath, $this->baseUrl, 
			$name, $tryExtensions, $getUrl, $forbidUpward, $typeName, $type
		);
	}

	protected function doResolveRelativePath($relativePath, $forbideUpward) {
		return Files::resolveRelativePath($this->basePath, $relativePath, $forbidUpward);
	}
}

/**
 * MultipathFinder performs a search in each of its base path before failing.
 */
class MultipathFinder extends BasePathFinder {

	private $pathsUrl;

	public $testPathsExistence = true;

	/**
	 * @param string $basePath	the base path from which relative path contained
	 * in $pathsUrl will be resolved
	 * @param string $baseUrl	the base url from which relative urls contained
	 * in $pathsUrl will be resolved
	 * @param array|string $pathsUrl	an array
	 * @param array $opts 
	 * @param boolean $cleanedPathsUrl	if set to TRUE, the $pathsUrl argument
	 * won't be cleaned, and will be set directly as this Finder's internal
	 * property
	 */
	public function __construct($basePath, $baseUrl, $pathsUrl, $opts = null, $cleanedPathsUrl = false) {

		parent::__construct($basePath, $baseUrl, $opts);

		$this->pathsUrl = $cleanedPathsUrl ? $pathsUrl
				: $this->cleanPathsUrl($pathUrls, $this->testPathsExistence);
	}

//REM	public static function createAbsoluteIf($pathsUrl, $testPathsExistence, $opts = null) {
//		if (($cleanPathsUrl = self::cleanAbsolutePathsUrl($pathsUrl, $testPathsExistence))) {
//			if (count($cleanPathsUrl) === 1) {
//				$url = reset($cleanPathsUrl);
//				return new BasicFinder(key($cleanPathsUrl), $url, $opts);
//			} else {
//				return new MultipathFinder(null, null, $cleanPathsUrl, $opts, true);
//			}
//		}
//	}
//
	public static function createAbsolute($pathsUrl, $opts = null, $cleanedPathsUrl = false) {
		return new MultipathFinder(null, null, $pathsUrl, $opts, $cleanedPathsUrl);
	}

	public static function createIf(BasePathFinder $owner, $pathsUrl, 
			$testPathsExistence, $opts = null) {

		if (($cleanPathsUrl = $owner->cleanPathsUrl($pathsUrl, $testPathsExistence))) {
			if (count($cleanPathsUrl) === 1) {
				$url = reset($cleanPathsUrl);
				return new BasicFinder(key($cleanPathsUrl), $url, $opts);
			} else {
				return new MultipathFinder(
					$owner->basePath, $owner->baseUrl, $cleanPathsUrl, $opts, true
				);
			}
		} else {
			return null;
		}
	}

	protected function doSearch($name, $tryExtensions, &$getUrl, $forbidUpward, $typeName, $type) {

		foreach ($this->pathsUrl as $path => $url) {
			if (($r = self::tryFind(
					$path, $url, 
					$name, $tryExtensions, $getUrl, $forbidUpward, $typeName, $type)
			)) return $r;
		}

		$getUrl = null;
		return null;
	}

	protected function doResolveRelativePath($relativePath, $forbideUpward) {
		$r = array();
		foreach (array_keys($this->pathsUrl) as $path) {
			$r[] = Files::resolveRelativePath($path, $relativePath, $forbidUpward);
		}
		return $r;
	}
}

class TypeFinder extends BasePathFinder {

	private $types;

	public $testPathExistence = true;
	public $typeFindersOpts = null;

	public function __construct($basePath, $baseUrl, $typesPathsUrl, $opts = null, $typeFindersOpts = null) {
		parent::__construct($basePath, $baseUrl, $opts);
		if ($typeFindersOpts) $this->typeFindersOpts = $typeFindersOpts;
		$this->types = $typesPathsUrl;
	}

	public static function createAbsolute($typesPathsUrl, $opts = null, $typeFindersOpts = null) {
		return new TypeFinder(null, null, $typesPathsUrl, $opts, $typeFindersOpts);
	}

	/**
	 * Creates the appropriate Finder, according to the nature of the $pathsUrl
	 * argument.
	 * @param string|array $pathsUrl
	 * @return Finder the created Finder, or NULL if no existent path was found
	 * in the $pathsUrl argument, and if the {@link $testPathExistence} option 
	 * is set to TRUE for this TypeFinder
	 */
	private function createTypeFinder($pathsUrl) {
		if (is_string($pathsUrl)) {
			// shortcut: type Finder relative path and url are the same
			$url = $pathsUrl;
			$path = $pathsUrl;
			if ($this->resolveRelativeIf($path, $url, $this->testPathExistence)) {
				return new BasicFinder($path, $url, $this->typeFindersOpts);
			}
		} else if (is_array($pathsUrl)) {
			if (count($pathsUrl) == 1) {
				$url = reset($pathsUrl);
				$path = key($pathsUrl);
				if ($this->resolveRelativeIf($path, $url, $this->testPathExistence)) {
					return new BasicFinder($path, $url, $this->typeFindersOpts);
				}
			} else {
				return MultipathFinder::createIf(
					$this, $pathsUrl, $this->testPathExistence, $this->typeFindersOpts
				);
			}
		} else {
			throw new IllegalArgumentException(
				"\$pathsUrl must be an array or a string (here: $pathsUrl)"
			);
		}
	}

	protected function doGetFinderFor($type, $typeName) {
		if (isset($this->types[$typeName]) 
				&& ($finder = $this->createTypeFinder($this->types[$typeName]))) {

			return $finder;
		} else {
			return parent::doGetFinderFor($type, $typeName);
		}
	}
}


/**
 * This Finder tries to get type-specific Finders with its owner object's 
 * methods, and delegates processing to its default finder if it finds none.
 */
class ObjectFinder extends FinderBase {

	private $owner;
	/** @var Finder */

	public $finderFnName = 'getFileFinderFor%s';
	public $aliasFnName = 'resolveFileFinderAlias';

	public function __construct($owner, $opts = null, $fallbackFinder = null) {
		$this->owner = $owner;
		parent::__construct($opts);
		if ($fallbackFinder) $this->fallbackFinder = $fallbackFinder;
	}

	/**
	 * Default implemention. Override to implement your own strategie. This
	 * implementation search for methods named get{type}Finder in this
	 * object and returns their result.
	 * @param string $type
	 * @return Finder
	 */
	protected function doGetFinderFor($type, $typeName) {
		if (method_exists($this->owner, $m = sprintf($this->finderFnName, ucfirst($typeName)))) {
			return $this->owner->$m(parent::doGetFinderFor($type, $typeName));
		} else if (method_exists($this->owner, $m = sprintf($this->finderFnName, null))) {
			return $this->owner->$m($type, $typeName, parent::doGetFinderFor($type, $typeName));
		} else {
			return parent::doGetFinderFor($type, $typeName);
		}
	}

	protected function getAliasData($alias) {
//		dumpl(array(
//			$this, $this->owner, $alias,
//			method_exists($this->owner, $this->aliasFnName)
//		));
		if (method_exists($this->owner, $this->aliasFnName)
				&& ($r = $this->owner->{$this->aliasFnName}($alias))) {

			return $r;
		} else {
			return parent::getAliasData($alias);
		}
	}
}
