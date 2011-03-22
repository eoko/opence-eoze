<?php

interface ModelField {

	function getName();

//	function select(ModelTableQuery $query);

//	function orderClause($dir, $tableAlias = null);

	function getType();

	function isNullable();
}