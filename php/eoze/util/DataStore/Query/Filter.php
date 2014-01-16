<?php

namespace eoze\util\DataStore\Query;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
class Filter extends QueryElementOnField {

	protected $type = self::FILTER;

	protected $value;

	public function __construct($field, $value) {
		parent::__construct($field);
		$this->value = $value;
	}

	function test($element) {
		return $this->getFieldValue($element) == $this->value;
	}
}
