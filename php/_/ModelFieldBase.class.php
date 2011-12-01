<?php

/**
 * Base class implementation for {@link ModelField}.
 * 
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 22 nov. 2011
 */
abstract class ModelFieldBase implements ModelField {
	
	public function castValue($value) {
		return eoko\cqlix\ModelFieldHelper::castValue($value, $this->getType());
	}
}
