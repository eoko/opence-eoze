<?php

namespace eoko\util;

use Symfony\Component\Yaml\Yaml;
use eoko\log\Logger;

if (!class_exists('Symfony\\Component\\Yaml\\Yaml')) {
	throw new \SystemException('Missing dependency: Symfony\Component\Yaml.');
}

/**
 * Proxy class to enable class autoloading for YAML lib
 * @ignore
 */
class YmlReader {

	/**
	 * Loads YAML into a PHP array.
	 *
	 * The load method, when supplied with a YAML stream (string or file),
	 * will do its best to convert YAML in a file into a PHP array.
	 *
	 *  Usage:
	 *  <code>
	 *   $array = sfYaml::load('config.yml');
	 *   print_r($array);
	 *  </code>
	 *
	 * @param string $input Path of YAML file or string containing YAML
	 *
	 * @return array The YAML converted to a PHP array
	 *
	 * @throws InvalidArgumentException If the YAML is not valid
	 */
	public static function load($input) {
		return Yaml::parse($input);
	}

	/**
	 * Dumps a PHP array to a YAML string.
	 *
	 * The dump method, when supplied with an array, will do its best
	 * to convert the array into friendly YAML.
	 *
	 * @param array   $array PHP array
	 * @param integer $inline The level where you switch to inline YAML
	 *
	 * @return string A YAML string representing the original PHP array
	 */
	public static function dump($array, $inline = 0) {
		return Yaml::dump($array, $inline);
	}

	public static function loadFile($filename) {
		$input = file_get_contents($filename);
		if (strstr($input, "\t")) {
			$input = str_replace("\t", '  ', $input);
			Logger::get(get_class())->warn('Yaml file should not contain tabs: ' . $filename);
		}
		return self::load($input);
	}
}