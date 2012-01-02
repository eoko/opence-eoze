<?php

namespace eoko\output;

use eoko\output\Adapter\EchoAdapter;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 24 nov. 2011
 */
class Output {
	
	private function __construct() {}
	
	/**
	 * @var Adapter
	 */
	private static $adapter;
	
	public static function out($string) {
		self::$adapter->out($string);
	}
	
	public static function setAdapter(Adapter $adapter) {
		self::$adapter = $adapter;
	}
}

Output::setAdapter(new EchoAdapter);
