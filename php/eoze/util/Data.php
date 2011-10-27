<?php

namespace eoze\util;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 27 oct. 2011
 */
interface Data {
	
	function has($key);
	
	function get($key);
	
	function getOr($key, $default = null);
}
