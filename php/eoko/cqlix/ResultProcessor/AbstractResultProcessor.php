<?php

namespace eoko\cqlix\ResultProcessor;

use eoko\cqlix\ResultProcessor;

use ModelTable;
use ModelField;
use eoko\cqlix\EnumField;

use IllegalArgumentException;
use IllegalStateException;

use DateTime;

/**
 * Base implementation of {@link ResultProcessor}.
 * 
 * This implementation loops through the result array's row and fields and relies
 * on its abstract method implementations to convert the actual values.
 * 
 * When the `convert*` method are called, it will already have been checked that the
 * value is either not null or allowed to be so and, depending on the field's type,
 * it will have been checked that the raw value complies to what is allowed for the
 * field's type. For certain field's type, the raw value will also have undergonne
 * some preprocessing (see the `convert*` methods documentation for details.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 21 déc. 2011
 */
abstract class AbstractResultProcessor implements ResultProcessor {
	
	/**
	 * @var ModelTable
	 */
	private $table;
	
	private $fields;
	
	public function __construct(ModelTable $table, $fields, array $options = null) {
		$this->table = $table;
		$this->fields = $fields;
		
		if ($options) {
			foreach ($options as $k => $v) {
				$k = "_$k";
				$this->$k = $v;
			}
		}
	}
	
	public function process(array $result) {
		
		$return = array();
		
		foreach ($this->fields as $name) {
			$field = $this->table->getField($name, true);
			foreach ($result as $i => $row) {
				$return[$i][$name] = $this->convertValue($field, $row[$name]);
			}
		}
		
		return $return;
	}
	
	protected function convertValue(ModelField $field, $value) {
		
		// It is not possible to check for NULL value based on field's
		// nullable param, because Relation->fields may be NULL because
		// the _relation_ itself is NULL.
		if ($field->getActualField() === $field) {
			if ($value === null && !$field->isNullable()) {
				throw new IllegalStateException(
					"Field `{$field->getName()}` is not allowed to be NULL."
				);
			}
		}
		
		switch ($field->getType()) {
			
			case ModelField::T_BOOL:
				if ($value === null) {
					return $this->convertNullBoolean();
				} else if ($value == 1) {
					return $this->convertBoolean(true);
				} else if ($value === 0 || $value === '0' || $value === false) {
					return $this->convertBoolean(false);
				} else {
					$class = gettype($value);
					throw new IllegalStateException(
							"Not a valid boolean value: ($class) $value"); 
				}
				
			case ModelField::T_ENUM:
				$field = $field->getActualField();
				if (!($field instanceof EnumField)) {
					$class = get_class($field);
					throw new IllegalStateException(
						"Field should be of class EnumField (actual: $class)."
					);
				}
				return $value === null
						? $this->convertNullEnum($field)
						: $this->convertEnum($field, $value);
				
			case ModelField::T_DATE:
				return $value === null
						? $this->convertNullDate()
						: $this->convertDate($value);
				
			case ModelField::T_DATETIME:
				return $value === null
						? $this->convertNullDateTime()
						: $this->convertDateTime($value);
		}
	}

	/**
	 * Converts a boolean value. This method is guaranteed never to be called
	 * with `null` value.
	 * @param bool $value 
	 * @return mixed
	 */
	abstract protected function convertBoolean($value);
	
	protected function convertNullBoolean() {
		return null;
	}
	
	/**
	 * Converts an enum value. This method is guaranteed never to be called
	 * with `null` value.
	 * @param EnumColumn $column
	 * @param mixed $value
	 * @return mixed
	 */
	abstract protected function convertEnum(EnumField $column, $value);
	
	protected function convertNullEnum(EnumField $column) {
		return null;
	}
	
	/**
	 * Converts a date value. This method is guaranteed never to be called
	 * with `null` value.
	 * @param string $value Date string in the format 'Y-m-d'.
	 * @return mixed
	 */
	abstract protected function convertDate($value);
	
	protected function convertNullDate() {
		return null;
	}
	
	/**
	 * Converts a datetime value. This method is guaranteed never to be called
	 * with `null` value.
	 * @param string|null $value Date time string in the format 'Y-m-d H:i:s'.
	 * @return mixed
	 */
	abstract protected function convertDateTime($value);
	
	protected function convertNullDateTime() {
		return null;
	}
}
