<?php

namespace eoze\util\FieldReader;

use eoze\util\FieldReader;

use IllegalArgumentException;

use ArrayAccess;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
class ArrayFieldReader implements FieldReader {

	public function read($element, $field) {
		if (is_array($element)) {
			if (array_key_exists($field, $element)) {
				return $element[$field];
			} else {
				throw new IllegalArgumentException("Array key $field does not exists");
			}
		} else if ($element instanceof ArrayAccess) {
			if ($element->offsetExists($field)) {
				return $element[$field];
			} else {
				throw new IllegalArgumentException("Array key $field does not exists");
			}
		} else {
			throw new IllegalArgumentException('$element must be an array');
		}
	}
}
