<?php

namespace eoko\config;

use eoko\util\YmlReader;
use eoko\util\Arrays;
use eoko\util\Files;
use eoko\cache\Cache;
use eoko\log\Logger;
use eoko\util\collection\Map, eoko\util\collection\ImmutableMap;

use IllegalArgumentException, IllegalStateException;
use InvalidConfigurationException;

const NS_PROP = 'config.node';
const CACHE_FILE = 'data';
const CACHE_NAMESPACE = 'cache';

class ConfigManager {

	public static $useCache = false;

	private static $instance = null;

	private static $configPaths = array();
	private static $defaultNodes = array();
	
	private static $extraFiles = null;
	private static $DELIMITERS = array('/', '\\');

	private $files = null;

	private $data;
	/**
	 * @var bool set to TRUE if the config data are modified, as compared as the
	 * cached values
	 */
	private $modified = false;
	private $delimiter;
	private $altDelimiters;

	private function __construct($init = true) {

		Logger::get($this)->startTimer('LOAD', 'Config loaded in {}');
		
		// Paths
		// First, add defaults path
		if ($init) {
			self::addPath(EOZE_CONFIG_PATH);
		}

		// Delimiters
		$delimiters = self::$DELIMITERS;
		$this->delimiter = array_shift($delimiters);
		if (count($delimiters)) {
			$this->altDelimiters = $delimiters;
		} else {
			$this->altDelimiters = null;
		}
		
		$this->loadData();

		Logger::get($this)->stopTimer('LOAD');

//		dump($this->data, 50);

		if ($this->modified) {
			$this->cacheData();
		}
	}
	
	public static function reset() {
		$useCache = self::$useCache;
		self::$useCache = false;
		self::$instance = new ConfigManager(false);
		self::$useCache = $useCache;
	}

	private function &node($node) {
		if (is_object($node)) {
			$node = get_class($node);
		}
		if ($node === null) return $this->data;
		else return $this->getNode($node);
	}

	/**
	 * @return ConfigManager
	 */
	public static function getInstance() {
		if (self::$instance === null) self::$instance = new ConfigManager();
		return self::$instance;
	}

	/**
	 * @return array|scalar
	 */
	public static function get($node, $key = null, $default = null) {
		$node = self::getInstance()->node($node);
		if ($key !== null) {
			if (!array_key_exists($key, $node)) {
				return $default;
			} else {
				return $node[$key];
			}
		} else {
			return $node;
		}
	}
	
	public static function put($node, $value) {
		$node =& self::getInstance()->node($node);
		$node = $value;
	}

	/**
	 * Get the given config values as a Map object.
	 * @param string|object $node
	 * @param string $key
	 * @param array $default
	 * @return Map
	 */
	public static function getConfigObject($node, $key = null, $default = null) {
		$a = self::get($node, $key, $default);
		return new ImmutableMap($a);
	}

	public static function addPath($path, $defaultNode = null) {
		if (self::$instance) {
			throw new IllegalStateException();
		}
//		if (func_num_args() > 1) {
//			foreach (func_get_args() as $path) {
//				self::addPath($path);
//			}
//		} else if (is_array($path)) {
//			foreach ($path as $path) {
//				self::addPath($path);
//			}
//		} else {
			if (substr($path, -1) !== DS) {
				$path .= DS;
			}
			self::$configPaths[] = $path;
			if ($defaultNode) {
				self::$defaultNodes[$path] = $defaultNode;
			}
//		}
	}

	public static function addFile($file) {
		if (self::$instance) throw new IllegalStateException();
		self::$extraFiles[dirname($file) . DS][] = basename($file);
	}

	private static function getCacheNamespace() {
		return __NAMESPACE__ . '\\' . CACHE_NAMESPACE;
	}

	private function cacheData() {
		Cache::cachePhpFile(
			self::getCacheNamespace(),
			CACHE_FILE,
			'<?php $this->data = ' . var_export($this->data, true) . ';'
		);
	}

	/**
	 * Loads $data properties with values contained in the cache.
	 * @return TRUE if the cache exists and has been loaded, else FALSE, if the
	 * cache doesn't exist, or the settings are set to prevent cache usage
	 */
	private function loadCachedData() {
		if (false !== $cache = Cache::getPhpFilePath(self::getCacheNamespace(), CACHE_FILE)) {
			$mtime = filemtime($cache);

			if (self::$useCache === 'auto') {
				foreach ($this->listConfigFiles() as $path => $files) {
					foreach ($files as $file) {
						if (filemtime("$path$file") > $mtime) {
							Logger::get($this)->info('Discarding config cache (modified: {})', "$path$file");
							return false;
						}
					}
				}
			}

			Logger::get($this)->debug('Using config cache last updated on {}', date('Y-m-d H:m:s', $mtime));
			include $cache;
			return true;
		} else {
			return false;
		}
	}

	private function loadData() {
		if (!self::$useCache || !$this->loadCachedData()) {
			$this->modified = true;
			foreach ($this->listConfigFiles() as $path => $files) {
				$parentNodePath = isset(self::$defaultNodes[$path]) ? self::$defaultNodes[$path] : '';
				$this->loadConfigDirectory($parentNodePath, $path, $files);
			}
		}
	}

	private function listConfigFiles() {

		if ($this->files !== null) return $this->files;

		$this->files = array();
		foreach (self::$configPaths as $path) {
			$this->files[$path] = Files::listFiles($path, 'glob:*.yml', true, false);
		}
		if (self::$extraFiles) {
			foreach (self::$extraFiles as $path) {
				foreach ($path as $file) {
					$this->files[$path] = $file;
				}
			}
		}

		return $this->files;
	}

	/**
	 * Loads all config files from the
	 * @param <type> $path
	 */
	private function loadConfigDirectory($parentNodePath, $path, $files) {
		foreach ($files as $file) {
			$nodePath = dirname($file);
			$file = $path . $file;
			if ($nodePath === '.') {
				$nodePath = '/';
			} else {
				$nodePath = str_replace(DS, $this->delimiter, $nodePath);
			}
			if ($parentNodePath) {
				if ($nodePath === '/') {
					$nodePath = '';
				}
				$nodePath = "$parentNodePath$this->delimiter$nodePath";
			}
			$this->addConfigFile($nodePath, $file);
		}
	}

	/**
	 * Cleans the passed nodePath by removing dupplicated delimiters. The pas
	 * @param string $nodePath
	 * @param bool $rtrim if TRUE, the returned node path will have no final
	 * delimiter, else one appended (even if none is present in the passed path)
	 * @param bool $replace if TRUE, all accepted delimiter alternatives will be
	 * replaced by the default delimiter.
	 * @return string
	 */
	private function cleanNodePath($nodePath, $rtrim = true, $replace = true) {

		if (substr($nodePath, -1) === ';') {
			$nodePath = substr($nodePath, 0, -1);
			Logger::get($this)->warn('Removing illegal ; at the end of config node property: ' . $nodePath);
		}

		if ($replace) {
			$nodePath = str_replace($this->altDelimiters, $this->delimiter, $nodePath);
		}

		$delimiter = preg_quote($this->delimiter, '/');
		$nodePath = preg_replace("/$delimiter$delimiter+/", $this->delimiter, $nodePath);

		if ($rtrim) {
			$nodePath = rtrim($nodePath, $this->delimiter);
		}

		return $nodePath;
	}

	private function addConfigFile($parentNodePath, $filename) {

		// Read yaml content of the file
		$yml = YmlReader::loadFile($filename);
		if (isset($yml[NS_PROP])) {
			$nodePath = $this->cleanNodePath($yml[NS_PROP]);
			unset($yml[NS_PROP]);
			if (!self::isPathAbsolute($nodePath)) {
				$nodePath = "$parentNodePath$this->delimiter$nodePath";
			}
		} else {
			$nodePath = $parentNodePath;
		}
        
		// Merge config file content in global config
        try {
			$this->addContent($nodePath, $yml);
		} catch (\Exception $ex) {
			// Add the file name to the exception
			throw new InvalidConfigurationException($filename, $nodePath,
					'Configuration file merging raised an error.', '', $ex);
		}
	}

	/**
	 * Adds values of $content in the node specified by $nodePath.
	 * @param string $nodePath
	 * @param array $content
	 */
	private function addContent($nodePath, array $content) {
		$nodePath = $this->cleanNodePath($nodePath);
		$node =& $this->getNode($nodePath, true);
		
//		$node = ConfigReader::read($nodePath, $content);
//		Arrays::apply($node, $content, false);
		Arrays::apply($node, ConfigReader::read($nodePath, $content), false);
	}

	/**
	 * Get the config node for the given $path. 
	 * @param string $path
	 * @return mixed Can return an array or a primitive value.
	 */
	private function &getNode($path = null, $cleanedPath = false) {
		$node =& $this->data;

		foreach (self::$DELIMITERS as $d) $path = ltrim($path, $d);
		if ($path) {
			if (!is_string($path)) {
				throw new IllegalArgumentException('$path must be a string');
			}
			$curPath = '';
			foreach ($this->explodeNode($path, $cleanedPath) as $sub) {
				if (!isset($node[$sub])) {
					$node[$sub] = array();
				} else if (!is_array($node)) {
					throw new \ConfigurationException(null, $path,
							"Cannot offset from the value node $curPath of type " 
							. gettype($node));
				}
				$node =& $node[$sub];
				$curPath .= self::$DELIMITERS[0] . $sub;
			}
		}

		return $node;
	}

	/**
	 * Explodes the given $nodePath string, returning an array containing
	 * successive nodes' name.
	 * @param string $nodePath
	 * @return array
	 */
	private function explodeNode($nodePath, $cleanedPath = false) {
		if (!$cleanedPath) $nodePath = $this->cleanNodePath($nodePath, true, true);
		return explode($this->delimiter, $nodePath);
	}

	/**
	 * Returns TRUE if the passed $nodePath is absolute (i.e. starts with a
	 * path delimiter).
	 * @param string $nodePath
	 * @return bool
	 */
	private static function isPathAbsolute($nodePath) {
		$c = substr($nodePath, 0, 1);
		foreach (self::$DELIMITERS as $d) {
			if ($c === $d) return true;
		}
		return false;
	}

}