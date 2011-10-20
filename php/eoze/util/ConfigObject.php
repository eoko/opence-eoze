<?php

namespace eoze\util;

use RuntimeException, IllegalArgumentException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 20 oct. 2011
 */
abstract class ConfigObject {
	
	public static function create() {
		$class = get_called_class();
		return new $class;
	}
	
	public function __call($name, $args) {
		$n = count($args);
		if (!property_exists($this, $name)) {
			throw new RuntimeException(get_class() . ' has no property ' . $name);
		}
		if ($n === 0) {
			return $this->$name;
		} else if ($n === 1) {
			$this->$name = $args[0];
			return $this;
		} else {
			throw new IllegalArgumentException(get_class() . 
					"::$name() Expects 0 or 1 arguments, $n given");
		}
	}
}
