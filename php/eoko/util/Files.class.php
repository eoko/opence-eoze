<?php

namespace eoko\util;
use \SecurityException;

class IllegalPathException extends SecurityException {

	const MSG_FORBIDDEN_UPWARD_RESOLUTION = 'Forbidden upward resolution for path: ';
	
	public function __construct($path, $cause = 'Illegal path: ') {
		parent::__construct($cause . $path);
	}
	
}

class Files extends \FileHelper {
	
	private function __construct() {}
	
	/**
	 * Resolves the absolute path for the given $relativePath relative to the
	 * $basePath. This method does test that a file actually exists.
	 * 
	 * The $forbidUpward parameter can be set to TRUE to disallow resolution of
	 * a path upper than $basePath. In this case, a {@link IllegalPathException}
	 * would be thrown.
	 * 
	 * @param string $basePath
	 * @param string $relativePath
	 * @param boolean $forbidUpward
	 * @return string 
	 * @throws IllegalPathException
	 */
	public static function resolveRelativePath($basePath, $relativePath, $forbidUpward = false) {
		
		$path = preg_replace(
			'@(?:\w+/\.\./)|(?:/\.)@', '', 
			str_replace('\\', '/', $basePath . $relativePath)
		);
		
		if ($forbidUpward && substr($path, 0, strlen($basePath) !== $basePath)) {
			throw new IllegalPathException(
				$relativePath, 
				IllegalPathException::MSG_FORBIDDEN_UPWARD_RESOLUTION
			);
		}
		
		return $path;
	}
	
	/**
	 * Get the absolute filename of the file with the $given name if it exists
	 * in the given $basePath, optionnaly searching for different $extensions.
	 * 
	 * The $name parameter can be any valid filesystem path, that will be 
	 * resolved from the $basePath. It can be forbidden for this path to
	 * resolve upper than the $basePath by setting $forbidUpward. In this case,
	 * if $name resolve upper that the $basePath, a {@link IllegalPathException}
	 * will be thrown.
	 * 
	 * @param string $basePath		the base path to the directory to be searched in
	 * @param string $name			
	 * @param boolean|array|string $tryExtensions	
	 * @param boolean $forbidUpward	
	 * @param boolean $forceExtension  if set to TRUE, the found filename will
	 * be required to end up with one of the $tryExtensions. If $tryExtension is
	 * not set, this param has no effect.
	 * @return string the abolute filename of the file if one has been found,
	 * or NULL
	 * @throws IllegalPathException
	 */
	public static function findIn($basePath, $name, 
			$tryExtensions = false, $forbidUpward = false, $forceExtension = true) {
		
		$target = null;
		
		if (($target = realpath("$basePath$name"))) {
			if ($tryExtensions && $forceExtension && !self::testExtension($target, $tryExtensions)) {
				$target = false;
			}
		}

		if ($target === false) {
			if ($tryExtensions !== false) {
				if (is_array($tryExtensions)) foreach ($tryExtensions as $ext) {
					if (false !== $target = realpath("$basePath$name.$ext")) break;
				} else {
					$target = realpath("$basePath$name.$tryExtensions");
				}
				if (!$target) return null;
			} else {
				return null;
			}
		}
		
		if ($forbidUpward) {
			// $basePath = realpath($basePath);
			if (substr($target, 0, strlen($basePath)) !== $basePath) {
				dump(array($target, $basePath));
				throw new IllegalPathException(
					$name, 
					IllegalPathException::MSG_FORBIDDEN_UPWARD_RESOLUTION
				);
			}
		}
		
		if (substr($name, -1) === DS && substr($target, -1) !== DS) {
			$target .= DS;
		}
		
		return $target;
	}
	
	public static function testExtension($filename, $extension) {
		if (substr($extension, 0, 1) !== '.') $extension = ".$extension";
		if (is_array($extension)) foreach ($extension as $extension) {
			if (substr($filename, -strlen($extension)) === $extension) return true;
		} else if (substr($filename, -strlen($extension)) === $extension) {
			return true;
		} else {
			return false;
		}
	}

	public static function getExtension($filename) {
		if (preg_match('/\.([^.]+)$/', $filename, $m)) {
			return $m[1];
		} else {
			return null;
		}
	}
	
	/**
	 * @author http://www.php.net/manual/en/function.realpath.php#97885
	 * @author Éric Ortéga
	 * @param string $targetPath
	 * @param string $basePath
	 * @return string
	 */
	public static function getRelativePath($basePath, $targetPath, $forbidUpward = false) {
		// clean arguments by removing trailing and prefixing slashes
		if (DS !== '/') {
			\str_replace(DS, '/', array($targetPath, $basePath));
		}
		
		$lastDS = null;
		if (substr($targetPath, -1) == '/') {
			$lastDS = '/';
			$targetPath = substr($targetPath, 0, -1);
		}
		if (substr($targetPath, 0, 1) == '/') {
			$targetPath = substr($targetPath, 1);
		}

		if (substr($basePath, -1) == '/') {
			$basePath = substr($basePath, 0, -1);
		}
		if (substr($basePath, 0, 1) == '/') {
			$basePath = substr($basePath, 1);
		}

		// simple case: $compareTo is in $path
		if (strpos($targetPath, $basePath) === 0) {
			$offset = strlen($basePath) + 1;
			return substr($targetPath, $offset);
		} else if ($forbidUpward) {
			throw IllegalPathException::createForbiddenUpwardResolution($basePath);
		}

		$relative = array();
		$pathParts = explode('/', $targetPath);
		$compareToParts = explode('/', $basePath);

		foreach ($compareToParts as $index => $part) {
			if (isset($pathParts[$index]) && $pathParts[$index] == $part) {
				continue;
			}

			$relative[] = '..';
		}

		foreach ($pathParts as $index => $part) {
			if (isset($compareToParts[$index]) && $compareToParts[$index] == $part) {
				continue;
			}

			$relative[] = $part;
		}

		return implode('/', $relative) . $lastDS;
	}

	public static function isAbsolute($path) {
		return preg_match('@^(?:/|\w+://|\w:)@', $path);
	}
	
	/**
	 * Convert a string containing system filename jokers (namely: * and ?) to
	 * an equivalent regex.
	 * @param string $s
	 * @return string
	 */
	public static function regex($s, $caseSensitive = false, $delim = '/') {
		$s = preg_split('/([?*])/', $s, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		$pre = $post = null;
		$caseSensitive = $caseSensitive ? '' : 'i';
		if ($s[0] !== '*') $pre = '^';
		else array_shift($s);
		if ($s[count($s)-1] !== '*') $post = '$';
		else array_pop($s);
		foreach ($s as &$v) {
			if ($v === '?') $v = '.';
			else if ($v === '*') $v = '.*';
			else $v = preg_quote($v, $delim);
		}
		return "$delim$pre" . implode('', $s) . "$post$delim$caseSensitive";
	}
}