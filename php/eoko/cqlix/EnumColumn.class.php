<?php

namespace eoko\cqlix;

use ModelColumn;
use IllegalStateException;
use IllegalArgumentException;

class EnumColumn extends ModelColumn {

	const CFG_DEFAULT = 'default';
	const CFG_LABEL   = 'label';
	const CFG_CODE    = 'code';
	const CFG_VALUE   = 'value';

	private $labels = array();
	private $enumCodes = array();

	function __construct($columnName, $type, $length = null, $canNull = false,
			$default = null, $unique = false, $foreignKeyToTable = null,
			$primaryKey = false, $autoIncrement = false, $meta = null,
			$enumConfig = null) {

		if (!$enumConfig) throw new IllegalStateException('Missing enum configuration');

		parent::__construct($columnName, $type, $length, $canNull, $default,
				$unique, $foreignKeyToTable, $primaryKey, $autoIncrement, $meta);

		$this->type = self::T_ENUM;

		$this->configure($enumConfig);
	}

	private function configure($enumConfig) {
//	 dump($enumConfig);
		foreach ($enumConfig as $value => $config) {
			if (is_string($config)) {
				$this->labels[$value] = $config;
				$this->enumCodes[$value] = $value; // unsure
			} else {
				$this->labels[$value] = isset($config[self::CFG_LABEL]) ? $config[self::CFG_LABEL] : null;
				$this->enumCodes[$value] = isset($config[self::CFG_CODE]) ? $config[self::CFG_CODE] : $value;;
			}
		}
//		dump($this->labels);
	}

	public function getEnumCode($value) {
		if (isset($this->enumCodes[$value])) {
			return $this->enumCodes[$value];
		} else {
			$type = gettype($value);
			throw new IllegalArgumentException(
				"Enum has no code associated to value: ($type) $value."
			);
		}
	}
	
	public function getEnumLabelForValue($value) {
		if (isset($this->labels[$value])) {
			return $this->labels[$value];
		} else {
			throw new IllegalStateException(
				"Enum has no label for value: $value."
			);
		}
	}

	public function isEnum() {
		return true;
	}

	public function getCodeLabels() {
		return $this->labels;
	}

	public function createCqlixFieldConfig() {
		$r = parent::createCqlixFieldConfig();
		foreach ($this->getCodeLabels() as $value => $label) {
			$r['items'][] = array(
				'label' => $label,
				'default' => $value === $this->getDefault(),
				'code' => $this->getEnumCode($value),
				'value' => $value,
			);
		}
		return $r;
	}
	
	public function castValue($value) {
		return ModelFieldHelper::castValue($value, $this->sqlType);
	}
}