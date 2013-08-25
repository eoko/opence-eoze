<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 */

use eoko\util\Files;

class FileHelper {

	public static function listFilesIfDirExists($directory, $pattern = null,
			$recursive = false, $absolute = false) {

		if (file_exists($directory)) {
			return self::listFiles($directory, $pattern, $recursive, $absolute);
		} else {
			return array();
		}
	}

	const LF_PATH_ABS_REL = 1;

	/**
	 * Convert the $pattern argument to the equivalent regex. The pattern format
	 * can be specified with the prefix glob: or re: (shortcut for regex:).
	 *
	 * If no prefix is specified, then the $pattern is assumed to be a regex. If
	 * the $pattern is a regex and a prefix is specified, then the delimiters
	 * can be either omitted, or else they MUST be '/'; if the delimiters are
	 * omitted in this fashion, then the i (case insensitive) modifier will be
	 * added to the regex.
	 * 
	 * If the $pattern is a regex but no prefix is used, then the delimiters
	 * MUST be present.
	 *
	 * @param string $pattern
	 * @return string
	 */
	private static function convertPattern($pattern) {
		if (preg_match('/^glob:(.+)$/', $pattern, $m)) {
			// TODO real glob support
			return Files::regex($m[1]);
		} else if (preg_match('/^(?:re|regex):(.+)$/', $pattern, $m)) {
			if (preg_match('@^/.+/[imsxeADSUXJu]*$@', $m[1])) {
				return $m[1];
			} else {
				return '/' . addcslashes($m[1], '/') . '/i';
			}
			return $m[1];
		} else {
			return $pattern;
		}
	}

	public static function listFiles($directory, $pattern = null, $recursive = false, $absolute = false) {

		$directory = rtrim(str_replace('\\', '/', $directory), '/') . '/';
		if (DIRECTORY_SEPARATOR != '/') $directory = str_replace('/', DIRECTORY_SEPARATOR, $directory);

		if (!is_dir($directory)) {
			Logger::get('FileHelper')->warn("$directory is not a directory (cannot listFiles)");
			return array();
		}

		$entries = Array();
		$dir = dir($directory);
		while (false !== ($entry = $dir->read())) {
			$entries[] = $entry;
		}
		$dir->close();

		$pattern = self::convertPattern($pattern);

		$matches = array();
		foreach ($entries as $entry) {

			$fullname = $directory . $entry;

			if ($entry != '.' && $entry != '..' && is_dir($fullname)) {
				if ($recursive) {
					$subMatches = self::listFiles($fullname, $pattern, $recursive, $absolute);
					if ($absolute) {
						$matches = array_merge($matches, $subMatches);
					} else {
						foreach ($subMatches as $m) {
							$matches[] = $entry . DS . $m;
						}
					}
				}
			} else {
				if (is_file($fullname) && ($pattern === null || preg_match($pattern, $entry))) {
					if ($absolute === true) {
						$matches[] = $fullname;
					} else if ($absolute === false) {
						$matches[] = $entry;
					} else if ($absolute === self::LF_PATH_ABS_REL) {
						$matches[] = array(
							$entry, $fullname
						);
					}
				}
			}
		}

		return $matches;
	}

	/**
	 *
	 * @param string $directory
	 * @param boolean $absolute
	 * @param string $pattern
	 * @param boolean $recursive
	 * @throws IllegalArgumentException
	 * @return string[]
	 */
	public static function listDirs($directory, $absolute = false, $pattern = null, $recursive = false) {

		$directory = rtrim(str_replace('\\', '/', $directory), '/') . '/';
		if (DIRECTORY_SEPARATOR != '/') $directory = str_replace('/', DIRECTORY_SEPARATOR, $directory);

		if (!is_dir($directory)) {
			Logger::get(get_called_class())->warn("$directory is not a directory (cannot listDirs)");
			return array();
		}

		$entries = Array();
		$dir = dir($directory);
		while (false !== ($entry = $dir->read())) {
			$entries[] = $entry;
		}
		$dir->close();

		$pattern = self::convertPattern($pattern);

		$matches = array();
		foreach ($entries as $entry) {

			$fullname = $directory . $entry;

			if ($entry != '.' && $entry != '..' && is_dir($fullname)) {

				$entry = utf8_encode(utf8_decode($entry));

				if ($pattern === null || preg_match($pattern, $entry)) {
					if ($absolute === true) {
						$matches[] = $fullname;
					} else if ($absolute === false) {
						$matches[] = $entry;
					} else if ($absolute === self::LF_PATH_ABS_REL) {
						$matches[] = array(
							$entry, $fullname
						);
					} else {
						throw new IllegalArgumentException('$absolute must be either '
							. '(boolean) true, either (boolean) false,'
							. ' or FileHelper::LF_PATH_ABS_REL'
						);
					}

					if ($recursive) {
						$subMatches = self::listDirs($fullname, $absolute, $pattern, $recursive);
						if ($absolute) {
							if ($absolute === self::LF_PATH_ABS_REL) {
								$matches[count($matches)-1][] = $subMatches;
							} else {
								$matches = array_merge($matches, $subMatches);
							}
						} else {
							foreach ($subMatches as $m) {
								$matches[] = $entry . DS . $m;
							}
						}
					}
				}
			}
		}

		return $matches;
	}

	/**
	 * http://php.net/manual/en/function.filesize.php filesize2bytes
	 * @param string $str eg 100MB
	 * @return int
	 */
	public static function filesizeToBytes($str) {
		$bytes = 0;

		$bytes_array = array(
			'B' => 1,
			'KB' => 1024,
			'MB' => 1024 * 1024,
			'GB' => 1024 * 1024 * 1024,
			'TB' => 1024 * 1024 * 1024 * 1024,
			'PB' => 1024 * 1024 * 1024 * 1024 * 1024,
		);

		$bytes = floatval($str);

		if (preg_match('#([KMGTP]?B)$#si', $str, $matches) && !empty($bytes_array[$matches[1]])) {
			$bytes *= $bytes_array[$matches[1]];
		}

		$bytes = intval(round($bytes, 2));

		return $bytes;
	}

	public static $sizeUnits = array(
		'o', 'ko', 'Mo', 'Go', 'To', 'Po', 'Eo', 'Zo', 'Yo'
	);

	/**
	 *
	 * @author Martin Sweeny
	 * @version 2010.0617
	 *
	 * returns formatted number of bytes.
	 * two parameters: the bytes and the precision (optional).
	 * if no precision is set, function will determine clean
	 * result automatically.
	 *
	 * http://www.php.net/manual/fr/function.filesize.php
	 */
	public static function formatSize($b,$p = null) {
		$units = array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
		if ($b == 0) return '0 ' . self::$sizeUnits[0];
		$c = 0;
		if (!$p && $p !== 0) {
			foreach ($units as $k => $u) {
				if (($b / pow(1024, $k)) >= 1) {
					$r["bytes"] = $b / pow(1024, $k);
					$r["units"] = self::$sizeUnits[$k];
					$c++;
				}
			}
			return number_format($r["bytes"], 2) . " " . $r["units"];
		} else {
			return number_format($b / pow(1024, $p)) . " " . self::$sizeUnits[$p];
		}
	}

	private static $normalizeChars = array(
		'Š' => 'S', 'š' => 's', 'Ð' => 'Dj', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
		 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I',
		 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U',
		 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
		 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i',
		 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u',
		 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', 'ƒ' => 'f'
	);

	public static function cleanFilename($toClean) {

		$toClean = str_replace('&', lang('-et-'), $toClean);
		$toClean = trim(preg_replace('/[^\w\d_ -]/si', '', $toClean)); //remove all illegal chars
		$toClean = str_replace(' ', '_', $toClean);
		$toClean = str_replace('__', '_', $toClean);
		$toClean = str_replace('--', '-', $toClean);

		return strtr($toClean, self::$normalizeChars);
	}

}
