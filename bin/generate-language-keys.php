#!usr/bin/php
<?php

require_once 'init.inc.php';

$excludedDirs = array('lib');
$excludedFiles = array('Language.class.php');

function find_files($path, $pattern, $callback) {
  $path = rtrim(str_replace("\\", "/", $path), '/') . '/';
  $matches = Array();
  $entries = Array();
  $dir = dir($path);
  while (false !== ($entry = $dir->read())) {
    $entries[] = $entry;
  }
  $dir->close();
  foreach ($entries as $entry) {
    $fullname = $path . $entry;
    if ($entry != '.' && $entry != '..' && is_dir($fullname)) {
		if (!in_array($entry, $GLOBALS['excludedDirs'])) {
			find_files($fullname, $pattern, $callback);
		}
    } else if (is_file($fullname) && preg_match($pattern, $entry)) {
		if (!in_array($entry, $GLOBALS['excludedFiles'])) {
			call_user_func($callback, $fullname);
		}
    }
  }
}

$keys = array();

function extractKeys($filename) {

	global $keys;

	$source = file_get_contents($filename);
	$tokens = token_get_all($source);
	$source = null;

	$buffer = false;
	$extra = 0;
	$entries = array();

	foreach ($tokens as $token) {

//		if ($buffer !== false) {
//			if (!is_string($token)) $buffer[] = array(token_name($token[0]), $token[1]);
////			else $buffer[] = $token;
//		}

		if (is_string($token)) {
			if ($buffer !== false) {
				switch ($token) {
					case '(':
						$extra++;
						break;

					case ')':
						if (--$extra == 0) {
							$entries[] = $buffer;
							$buffer = false;
						}
						break;

//					default:
//						$buffer[] = $token;
				}
			}
		} else {
			list($id, $text, $line) = $token;
			if ($buffer === false) {
				if ($text == 'lang' || $test == '__' && $id == T_STRING) {
					$extra = 0;
					$buffer = array($line, array());
				}
			} else {
				if ($id == T_CONSTANT_ENCAPSED_STRING) {
					$buffer[1][] = $text;
				}
			}
		}
//
//		continue;
//
//		if (is_string($token)) {
//			if ($buffer !== false) {
//				$buffer .= $token;
//				if ($token == ')') {
//					echo "\t" . $buffer . PHP_EOL;
//					$buffer = false;
//				}
//			}
//		} else {
//			list($id, $text) = $token;
//
//			if ($id == T_FUNCTION) {
//				$buffer = '';
//			} else if ($buffer !== false) {
//				$buffer .= $text;
//			}
//		}
	}

	if (count($entries) > 0) {
		foreach ($entries as $i => $entry) {
			list($line,$strings) = $entry;

			$concat = '';
			foreach ($strings as $sub) {
				$concat .= $sub . '.';
			}
			$concat = substr($concat, 0, -1) . ';';
			
			$key = eval("return $concat");

			$name = "$filename:$line";

			if (!isset($keys[$key])) {
				$keys[$key] = array($name);
			} else {
				if (!in_array($name, $keys[$key])) array_push($keys[$key], $name);
			}
		}

//		echo $filename . PHP_EOL;
//		print_r($entries);
	}
}

find_files(ROOT, '/\.(php|js)$/', 'extractKeys');
//extractKeys(ROOT . DS . 'modules' . DS . 'users.class.php');

require_once ROOT . 'inc' . DS . 'YAML.class.php';

$yml = array();

foreach ($keys as $key => $locs) {
	$yml[$key] = $key;
//	foreach ($locs as $loc)
//		$yml[$key][$loc] = $key;
}

ksort($yml);

echo YAML::dump($yml);
//print_r($keys);
