<?php

namespace eoko\cqlix;

use ModelField;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 22 nov. 2011
 */
class ModelFieldHelper {
	
	private final function __construct() {}
	
	public static function castValue($value, $type) {
		switch ($type) {
			case ModelField::T_INT:
				return (int) $value;
			case ModelField::T_BOOL:
				return (bool) $value;
			case ModelField::T_FLOAT:
				return (double) $value;
			case ModelField::T_ENUM:
				throw new UnsupportedOperationException('Not implemented type: ' . $this->getType());
			case ModelField::T_DATE:
			case ModelField::T_DATETIME:
//				throw new UnsupportedOperationException('Not implemented yet');
			case ModelField::T_DECIMAL:
			case ModelField::T_STRING:
			case ModelField::T_TEXT:
				return $value;
			default:
				throw new Exception('Unknown type: ' . $type);
				return $value;
		}
	}
}
