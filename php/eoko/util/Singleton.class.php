<?php
/**
 *
 * PHP versions 5.3
 *
 * LICENSE: This source file is subject to version 3.0 of the GPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/licenses/gpl.txt .  If you did not receive a copy of
 * the GPL v3 License and are unable to obtain it through the web, please
 * send a note to contact@eoko.fr so we can mail you a copy immediately.
 * 
 */

namespace eoko\util;


/**
 *
 * The singleton pattern is a design pattern used to implement the 
 * concept of a singleton, by restricting the instantiation of a class to one 
 * object.
 * 
 * @category   design pattern
 * @package    util
 * @author     Éric Ortéga <eric@eoko.fr>
 * @author     Romain DARY <romain@eoko.fr>
 * @copyright  2011 The Eoko Group <http://eoko-studio.fr>
 * @license    http://www.gnu.org/licenses/gpl.txt  see GPL v3
 * @version    v1.0
 * @link       Lien du fichier de description
 * 
 */

abstract class Singleton {
	
	private static $instances = array();

	private function __construct() {}

	abstract protected function construct();
	
	protected static function createInstance() {
		$o = self::createObject();
		$o->construct();
	}

	final protected static function createObject() {
		$class = get_called_class();
		return new $class();
	}

//	public static function __callStatic($name, $args) {
//		$class = get_called_class();
//
//		if (!isset(self::$instances[$class])) {
//			self::$instances[$class] = $class::createInstance();
//		}
//
//		if (0 === $n = count($args)) {
//			return self::$instances[$class]->$name();
//		} else if (1 === $n = count($args)) {
//			return self::$instances[$class]->$name($args[0]);
//		} else if (2 === $n = count($args)) {
//			return self::$instances[$class]->$name($args[0], $args[1]);
//		} else if (3 === $n = count($args)) {
//			return self::$instances[$class]->$name($args[0], $args[1], $args[3]);
//		} else {
//			return call_user_func_array(array($class, $name), $args);
//		}
//	}
	
	public final static function getInstance() {
		$class = get_called_class();
		if (!isset(self::$instances[$class])) {
			return self::$instances[$class] = $class::createInstance();
		} else {
			return self::$instances[$class];
		}
	}
	
}