<?php

namespace eoze\util\ObjectRepository\Query;

use eoze\util\ObjectRepository;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
abstract class QueryElementOnField extends QueryElement {
	
	protected $field;
	
	protected function __construct($field) {
		$this->field = $field;
	}
	
	protected function getFieldValue($element) {
		return $this->getRepository()->getElementFieldValue($element, $this->field);
	}
}
