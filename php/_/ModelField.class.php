<?php

use eoko\cqlix\FieldMetadata;

interface ModelField {

	const T_INT = 'int';
	const T_INTEGER = 'int';
	const T_STRING = 'string';
	const T_TEXT = 'text';
	const T_DATE = 'date';
	const T_TIME = 'time';
	const T_DATETIME = 'datetime';
	const T_BOOL = 'bool';
	const T_BOOLEAN = 'bool';
	const T_FLOAT = 'float';
	const T_DECIMAL = 'decimal';
	const T_ENUM = 'enum';

	function getName();

//	function select(ModelTableQuery $query);

//	function orderClause($dir, $tableAlias = null);

	function getType();
	
	function getSqlType();

	function isNullable();
	
	function castValue($value);
	
	/**
	 * @return FieldMetadata
	 */
	function getMeta();
	
	/**
	 * Gets the actual field object. This can be different from the object being
	 * referred, in the case of {@link ModelRelationInfoField} for example.
	 * @return ModelField
	 */
	function getActualField();
}