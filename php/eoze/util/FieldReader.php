<?php

namespace eoze\util;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
interface FieldReader {
	
	function read($element, $field);
}
