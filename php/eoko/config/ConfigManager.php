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
const MANAGER_CONFIG_FILENAME = 'ConfigManager.config.php';

class ConfigManager {

	private $useCache = false;

	private static $instance = null;

	private $configPaths = array();
	private $localConfigPaths = array();
	private $defaultNodes = array();
	
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

//		Logger::get($this)->startTimer('LOAD', 'Config loaded in {}');
		
		// -- Paths --
		// First, add defaults path
		if ($init) {
			self::addPath(EOZE_PATH . 'config');
			$this->configureConfigPaths(EOZE_PATH . 'config');
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

//		Logger::get($this)->stopTimer('LOAD');

//		dump($this->data, 50);

		if ($this->modified) {
			$this->cacheData();
		}
	}

// Removed on 2012-11-23
//	public static function reset() {
//		$useCache = self::$useCache;
//		self::$useCache = false;
//		self::$instance = new ConfigManager(false);
//		self::$useCache = $useCache;
//	}

	private function configureConfigPaths($firstPath) {
		if (substr($firstPath, -1) !== DIRECTORY_SEPARATOR) {
			$firstPath .= DIRECTORY_SEPARATOR;
		}
		$file = $firstPath . MANAGER_CONFIG_FILENAME;
		if (!file_exists($file)) {
			throw new \RuntimeException('Default ConfigManager config file is missing. File not found: ' . $file);
		}
		$this->configure($file);
	}

	private $appliedConfigFiles = array();

	/**
	 * Configure the ConfigManager using the specified file.
	 *
	 * @param string $file
	 * @throws \RuntimeException
	 */
	private function configure($file) {

		$this->appliedConfigFiles[$file] = true;

		/** @noinspection PhpIncludeInspection */
		$config = require $file;
		if (!is_array($config)) {
			throw new \RuntimeException('Config manager config file must return an array: ' . $file);
		}

		// Cache
		if (isset($config['cache'])) {
			$this->useCache = $config['cache'];
		}

		// Locations
		if (isset($config['locations'])) {
			$extraConfigFiles = array();
			$eozePath = rtrim(EOZE_PATH, DIRECTORY_SEPARATOR);
			$appPath = rtrim(ROOT, DIRECTORY_SEPARATOR);
			foreach ($config['locations'] as $index => $path) {
				$configNode = null;
				if (is_array($path)) {
					extract($path);
				} else if (is_string($index)) {
					$configNode = $path;
					$path = $index;
				}
				// Replace path variables
				$path = rtrim($path, DIRECTORY_SEPARATOR);
				$path = str_replace(array('%EOZE%', '%APP%'), array($eozePath, $appPath), $path);
				// Store possible extra config
				$extraConfigFiles[] = $path . DIRECTORY_SEPARATOR . MANAGER_CONFIG_FILENAME;
				// Add config path
				$this->addPath($path, $configNode);
			}
			// See & apply new ConfigManager config files
			foreach ($extraConfigFiles as $file) {
				if (file_exists($file) && !isset($this->appliedConfigFiles[$file])) {
					$this->configure($file);
				}
			}
		}
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
	 * @param string|object|null $node
	 * @param string $key
	 * @param mixed $default
	 * @return array|mixed
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
		$target =& self::getInstance()->node($node);
		$target = $value;
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

	private function isLocalPath($path) {
		return substr($path, -6) === '.local';
	}

	private function addPath($path, $defaultNode = null) {
		if (self::$instance) {
			throw new IllegalStateException();
		}
		$trimmedPath = rtrim($path, '/\\');
		$path = $trimmedPath . DIRECTORY_SEPARATOR;
		if ($this->isLocalPath($path)) {
			$this->localConfigPaths[] = $path;
		} else {
			$this->configPaths[] = $path;
		}
		if ($defaultNode) {
			$this->defaultNodes[$path] = $defaultNode;
		}
	}

	/**
	 * @return string[]
	 */
	private function getConfigPaths() {
		return array_merge(
			$this->configPaths,
			$this->localConfigPaths
		);
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
	 *
	 * @return bool true if the cache exists and has been loaded, else FALSE, if the
	 * cache doesn't exist, or the settings are set to prevent cache usage
	 */
	private function loadCachedData() {
		if (false !== $cache = Cache::getPhpFilePath(self::getCacheNamespace(), CACHE_FILE)) {
			$mtime = filemtime($cache);

			if ($this->useCache === 'auto') {
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
			/** @noinspection PhpIncludeInspection */
			include $cache;
			return true;
		} else {
			return false;
		}
	}

	private function loadData() {
		if (!$this->useCache || !$this->loadCachedData()) {
			$this->modified = true;
			foreach ($this->listConfigFiles() as $path => $files) {
				$parentNodePath = isset($this->defaultNodes[$path]) ? $this->defaultNodes[$path] : '';
				$this->loadConfigDirectory($parentNodePath, $path, $files);
			}
		}
	}

	private function listConfigFiles() {

		if ($this->files === null) {
			$this->files = array();
			foreach ($this->getConfigPaths() as $path) {
				$this->files[$path] = Files::listFiles($path, 'glob:*.yml', true, false);
			}
			if (self::$extraFiles) {
				foreach (self::$extraFiles as $path) {
					foreach ($path as $file) {
						$this->files[$path] = $file;
					}
				}
			}
		}

		return $this->files;
	}

	/**
	 * Loads all config files from the given path.
	 *
	 * @param string $parentNodePath
	 * @param string $path
	 * @param string[] $files
	 */
	private function loadConfigDirectory($parentNodePath, $path, $files) {
		foreach ($files as $file) {
			$dirname = dirname($file);
			$basename = basename($file);

			// Node path from directory name
			$nodePath = dirname($file);
			if ($nodePath === '.') {
				$nodePath = $this->delimiter;
			} else {
				$nodePath = str_replace(DS, $this->delimiter, $nodePath);
			}

			// Node path from filename
			// Dotted notation in file name
			if (preg_match('/^(?<name>.*[.].*)\.\w+$/', basename($file), $matches)) {
				$nodePath .= str_replace('.', $this->delimiter, $matches['name']);
			}
			// Else, append the basename -- but only if we are not in the root directory
			else if ($nodePath !== $this->delimiter) {
				if (-1 !== strpos('.', $basename)) {
					$nodePath .= $this->delimiter . substr($basename, 0, strpos($basename, '.'));
				} else {
					$nodePath .= $this->delimiter . $basename;
				}
			}

			// Apply parent node path
			if ($parentNodePath) {
				$nodePath = rtrim($parentNodePath, $this->delimiter)
					. $this->delimiter
					. ltrim($nodePath, $this->delimiter);
			}

			$this->addConfigFile($nodePath, $path . $file);
		}
	}

	/**
	 * Cleans the passed nodePath by removing duplicated delimiters.
	 *
	 * @param string $nodePath
	 * @param bool $rtrim if TRUE, the returned node path will have no final
	 * delimiter, else one is appended (even if none is present in the passed path)
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

		// Read YAML content of the file
		$yml = YmlReader::loadFile($filename);
		if (isset($yml[NS_PROP])) {
			$nodePath = $this->cleanNodePath($yml[NS_PROP]);
			unset($yml[NS_PROP]);
			if (!self::isPathAbsolute($nodePath)) {
				$nodePath = rtrim($parentNodePath, $this->delimiter) . $this->delimiter . $nodePath;
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
	 *
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
	 *
	 * @param string $path
	 * @param bool $cleanedPath
	 * @throws \IllegalArgumentException
	 * @throws \ConfigurationException
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
	 *
	 * @param string $nodePath
	 * @param bool $cleanedPath
	 * @return string[]
	 */
	private function explodeNode($nodePath, $cleanedPath = false) {
		if (!$cleanedPath) $nodePath = $this->cleanNodePath($nodePath, true, true);
		return explode($this->delimiter, $nodePath);
	}

	/**
	 * Returns TRUE if the passed $nodePath is absolute (i.e. starts with a path delimiter).
	 *
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
