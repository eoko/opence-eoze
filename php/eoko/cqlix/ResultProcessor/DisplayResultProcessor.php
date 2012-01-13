<?php

namespace eoko\cqlix\ResultProcessor;

use eoko\cqlix\EnumField;
use DateTime;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 21 déc. 2011
 */
class DisplayResultProcessor extends AbstractResultProcessor {
	
	protected $_dateFormat = 'd/m/Y';
	protected $_dateTimeFormat = 'd/m/Y H:i:s';
	
	protected function convertBoolean($value) {
		return $value ? 'Oui' : 'Non';
	}
	
	protected function convertEnum(EnumField $field, $value) {
		return $field->getEnumLabelForValue($value);
	}
	
	protected function convertDate($value) {
		if ($value === null) {
			return null;
		} else {
			$date = new DateTime($value);
			return $date->format($this->_dateFormat);
		}
	}

	protected function convertDateTime($value) {
		if ($value === null) {
			return null;
		} else {
			$date = new DateTime($value);
			return $date->format($this->_dateTimeFormat);
		}
	}
}
