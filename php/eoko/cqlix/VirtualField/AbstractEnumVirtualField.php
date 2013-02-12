<?php

namespace eoko\cqlix\VirtualField;

use ModelField;
use eoko\cqlix\EnumField;

use IllegalStateException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 22 déc. 2011
 */
abstract class AbstractEnumVirtualField extends AbstractVirtualField implements EnumField {

	protected $type = ModelField::T_ENUM;

	protected $sqlType = ModelField::T_STRING;

	public function getSqlType() {
		return $this->sqlType;
	}

	public function getCodeLabels() {
		return $this->getMeta()->enum;
	}

	public function getEnumCode($value) {
		return $value;
	}

	public function getEnumLabelForValue($value) {
		$codeLabels = $this->getCodeLabels();
		if (isset($codeLabels[$value])) {
			return $codeLabels[$value];
		} else {
			throw new IllegalStateException(
				"Enum has no label for value: $value."
			);
		}
	}
}
