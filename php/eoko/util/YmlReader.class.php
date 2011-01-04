<?php

namespace eoko\util;

use \sfYaml;

require_once LIBS_PATH . 'sfYaml' . DS . 'sfYaml.php';

/**
 * Proxy class to enable class autoloading for YAML lib
 * @ignore
 */
class YmlReader extends sfYaml {

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
		require_once LIBS_PATH . 'sfYaml' . DS . 'sfYaml.php';
		return sfYaml::load(str_replace("\t", '  ', $input));
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
		require_once LIBS_PATH . 'sfYaml' . DS . 'sfYamlDumper.php';
		return sfYaml::dump($array, $inline);
	}

	public static function loadFile($filename) {
		return self::load(str_replace("\t", '  ', file_get_contents($filename)));
	}
}