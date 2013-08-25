<?php

namespace eoko\cache;

use eoko\log\Logger;

const PATH = CACHE_PATH;

/**
 * @todo real implementation
 */
class Cache {

	public static $mode = 0777;
	public static $debugInfo = false;

	private static $useValidityMonitors = true;

	private function __construct() {}

	public static function getClassName() {
		return get_called_class();
	}

	public static function cachePhpClass($class, $code) {
		return self::cachePhpFile(
			parseNamespace($class, $class, GET_NAMESPACE_TRIM),
			"$class.php",
			$code
		);
	}

	private static function getNamespacePath($ns) {
		return PATH . 'php' . DS . str_replace('\\', DS, trim($ns, '\\'));
	}

	private static function createNamespaceDir($ns) {
		$nsPath = self::getNamespacePath($ns);
		if (!file_exists($nsPath)) mkdir($nsPath, self::$mode, true);
		return $nsPath . DS;
	}

	private static function getCacheFileInNamespace($namespace, $filename) {
		$nsPath = self::createNamespaceDir($namespace);
		if (!is_writeable($nsPath)) {
			Logger::get(get_called_class())->warn('Cannot write cache: {}', $nsPath);
			return false;
		}
		return "$nsPath$filename";
	}

	public static function cachePhpFile($namespace, $filename, $code) {
		$path = self::getCacheFileInNamespace($namespace, $filename);
		if (file_exists($path)) {
			@unlink($path);
		}
		if (substr($filename, -4) !== '.php') $filename .= '.php';
		file_put_contents($path, $code);
		return $path;
	}

	public static function getPhpFilePath($namespace, $filename, $onlyIfExists = true) {
		$path = self::getNamespacePath($namespace) . DS . $filename;
		if ($onlyIfExists && file_exists($path)) return $path;
		else return false;
	}

	private static function parseClassAndKey(&$cacheKey, &$namespace, &$key) {
		if (is_array($cacheKey)) {
			list($cacheKey, $key) = $cacheKey;
		}
		if (is_object($cacheKey)) {
			$cacheKey = get_class($cacheKey);
			$namespace = get_namespace($cacheKey);
		}
		if (preg_match('/^(.+)\\\\([^\\\\]+)$/', $cacheKey, $m)) {
			$cacheKey = $m[2];
			$namespace = $m[1];
		} else {
			$namespace = null;
		}
	}

	/**
	 * Removes any reference to an instanciated object in the given cache key,
	 * replacing then with the full class name string. This is useful to avoid
	 * storing uselessly whole object, when storing a cache key.
	 * @param object|class|string $key
	 * @return mixed
	 */
	public static function flattenKey(&$key) {
		if ($key) {
			if (is_array($key)) {
				$key[0] = self::flattenKey($key[0]);
			} else if (is_object($key)) {
				$key = get_class($key);
			}
		}
		return $key;
	}

	private static function appendKey(&$cacheKey, $key) {
		if (is_array($cacheKey)) {
			$cacheKey = array(
				$cacheKey[0],
				$cacheKey[1] . ".$key",
			);
		} else {
			$cacheKey = array(
				$cacheKey, $key
			);
		}
		return $cacheKey;
	}

	public static function cacheData($class, $data, $version = null, $requires = null) {

		Logger::get(Cache::getClassName())->warn('cacheData() is deprecated');

		self::parseClassAndKey($class, $namespace, $key);

		if ($version === null) {
			if (is_object($class) && isset($class->cacheVersion)) {
				$version = $class->cacheVersion;
			} else if (property_exists($class, 'cacheVersion')) {
				$version = $class::$cacheVersion;
			}
		}

//		$cache = new Item($data, $version);

		self::cachePhpFile(
			$namespace, $class .
			'.DataCache' . (isset($key) ? ".$key" : '') . '.php',
			'<?php return ' . var_export($data, true) . ';'
		);
	}

	public static function cacheObject($cacheKey, $object, $extraCode = null) {

		$debug = '';

		if (Logger::get(get_called_class())->isActive(Logger::DEBUG, false)) {
			$debug = print_r($object, true);
			$debug = "\n\n/* === Debug Informations ===\n\n$debug\n*/";
		}

		return self::cacheDataRaw(
			$cacheKey,
			'return unserialize(\'' 
				. str_replace('\'', '\\\'', serialize($object))
				. '\');'
				. $debug,
			$extraCode
		);
	}

	/**
	 * Get the name of the main cache file for the given $cacheKey.
	 * @param string|array|object $cacheKey
	 * @return string
	 */
	public static function getCacheFile($cacheKey, $ifExist = true) {
		self::parseClassAndKey($cacheKey, $namespace, $key);
		$filename = self::getCacheFileInNamespace(
			$namespace,
			"$cacheKey.DataCache" . (isset($key) ? ".$key" : '') . '.php'
		);
		if (!$ifExist || file_exists($filename)) {
			return $filename;
		} else {
			return null;
		}
	}

	public static function cacheDataRaw($cacheKey, $code, $extraCode = null) {

		self::parseClassAndKey($cacheKey, $namespace, $key);

		$filenameBase = "$cacheKey.DataCache" . (isset($key) ? ".$key" : '');

		if ($extraCode) {
			$depFileName = "$filenameBase.inc.php";
			// cache dep file
			if (is_array($extraCode)) {
				$tmp = '';
				foreach ($extraCode as $xc) {
					$tmp .= PHP_EOL . $xc;
				}
				$extraCode = $tmp;
			}
			self::cachePhpFile(
				$namespace, 
				$depFileName, 
				"<?php $extraCode"
			);

			// add the require to the base code
			$code = PHP_EOL . "require dirname(__FILE__) . DS . '$depFileName';" 
				. PHP_EOL . $code;
		}

		return self::cachePhpFile(
			$namespace,
			"$filenameBase.php",
			"<?php $code"
		);
	}

	public static function cacheDataEx($cacheKey, $data, $extraCode) {
		self::cacheDataRaw($cacheKey, 'return ' . var_export($data, true) . ';', $extraCode);
	}

	/**
	 * Set the validity of the cache node specified by $cacheKey dependant on
	 * modifications of the files in $files.
	 * @param mixed $cacheKey
	 * @param array[string] $files 
	 */
	public static function monitorFiles($cacheKey, $files) {
		if (self::$useValidityMonitors) {
			self::appendKey($cacheKey, 'validity');
			$validator = new FileValidator($files);
			self::cacheObject($cacheKey, $validator);
		}
	}

	public static function getCachedData($cacheKey) {

		self::parseClassAndKey($cacheKey, $namespace, $key);
		$baseFilename = $cacheKey . '.DataCache' . (isset($key) ? ".$key" : '');

		if (self::$useValidityMonitors) {
			$file = self::getPhpFilePath(
				$namespace, 
				"$baseFilename.validity.php"
			);
			if ($file) {
				$validator = require $file;
//				dump($validator);
				if (!$validator->test()) {
					Logger::get(get_called_class())->debug(
						'Cache invalidated for: {}.{}', $cacheKey, $key
					);
					self::doClearCachedData($cacheKey, $namespace, $key);
					return null;
				}
			}
		}

		$file = self::getPhpFilePath(
			$namespace, 
			$baseFilename . '.php'
		);

		if ($file) return require $file;
		else return null;
	}

	public static function clearCachedData($cacheKey) {
		self::parseClassAndKey($cacheKey, $namespace, $key);
		return self::doClearCachedData($cacheKey, $namespace, $key);
	}

	private static function doClearCachedData($cacheKey, $namespace, $key) {
		$base = $cacheKey . '.DataCache' . (isset($key) ? ".$key" : '');

		foreach (array(
			'',
			'.inc',
			'.validity',
		) as $ext) {
			$file = self::getPhpFilePath(
				$namespace,
				"$base$ext.php"
			);
			if ($file) {
				Logger::get(get_called_class())->debug(
					'clearCachedData: deleting file {}', $file
				);
				@unlink($file);
			}
		}
	}

	private static function makeObjectCacheFilename($class, $version, $index) {
		return "{$class}__{$version}__{$index}.cache.php";
	}

	private function parseObjectInfo($filename, &$version = null, &$index = null) {
		$parts = explode('__', $filename);
		if (count($parts) === 3) {
			list(, $version, $index) = $parts;
			if ($index === '') $index = null;
		} else {
			throw new IllegalStateException('Invalid cache filename: ' . $filename);
		}
	}

	public static function cacheSingleton($object, $version) {
		return self::cachePhpFile(
			get_namespace(get_class($object), $class),
			self::makeObjectCacheFilename($class, $version, null),
			'<?php ' . var_export($object, true) . ';'
		);
	}

	public static function getCachedSingleton($class, $version) {
		$nsPath = self::getNamespacePath(get_namespace($class, $class)) . DS;
		$filename = self::makeObjectCacheFilename($class, $version, null);
		if (file_exists($path = $nsPath . $filename)) return require($path);
		else return null;
	}
}

Cache::$debugInfo = Logger::get(Cache::getClassName())->isActive(Logger::DEBUG);
