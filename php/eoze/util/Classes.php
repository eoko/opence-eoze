<?php

namespace eoze\util;

use ReflectionClass;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 27 oct. 2011
 */
class Classes {
	
	private function __construct() {}
	
	public static function getImplementedInterfaces($class) {
		$rc = new ReflectionClass($class);
		$r = array($class => true);
		foreach ($rc->getInterfaceNames() as $interface) {
			$r[$interface] = true;
		}
		while ($rc = $rc->getParentClass()) {
			$r[$rc->getName()] = true;
		}
		return array_keys($r);
	}
}
