<?php

interface VirtualField extends ModelField {

	function select(ModelTableQuery $query, $alias = null, QueryAliasable $aliasable = null);

	function getClause(ModelTableQuery $query, QueryAliasable $aliasable = null);

	function getOrderFieldAlias(QueryAliasable $aliasable, $alias = null);
}

abstract class VirtualFieldBase implements VirtualField {

	protected $alias;

	function __construct($alias) {
		$this->alias = $alias;
	}
	
	public function getName() {
		if ($this->alias !== null) return $this->alias;
		else throw new UnsupportedOperationException(get_class($this) . '::getName()');
	}
	
	public function getType() {
		throw new UnsupportedOperationException(get_class($this) . '::getType()');
	}

	public function isNullable() {
		return true;
	}

	public function select(ModelTableQuery $query, $alias = null, QueryAliasable $aliasable = null) {
		if ($alias === null) $alias = $this->alias;
		return $query->select(
			new QuerySelectRaw(
				$this->getClause($query, $aliasable) . " AS `$alias`"
			)
		);
	}

	public function getClause(ModelTableQuery $query, QueryAliasable $aliasable = null) {
		return $this->doGetClause($aliasable !== null ? $aliasable : $query);
	}

	protected function doGetClause(QueryAliasable $aliasable) {
		throw new UnsupportedOperationException(get_class($this) . '::doGetClause()');
	}

	public function getOrderFieldAlias(QueryAliasable $aliasable, $alias = null) {
		if ($alias === null) $alias = $this->alias;
		return $this->doGetOrderFieldAlias($aliasable, $alias);
	}

	protected function doGetOrderFieldAlias(QueryAliasable $aliasable, $alias) {
		return Query::quoteName($alias);
	}
}

//class FieldAliasVirtualField extends VirtualFieldBase {
//
//	protected $field;
//
//	function __construct($field, $alias) {
//		parent::__construct($alias);
//		$this->field = $field;
//	}
//
//	protected function doGetClause(QueryAliasable $aliasable) {
//		return $aliasable->getQualifiedName($this->field);
//	}
//
//}

class AgeVirtualField extends VirtualFieldBase {

	protected $dateField, $alias;

	function __construct($dateField, $alias = 'age') {
		parent::__construct($alias);
		$this->dateField = $dateField;
	}

	public function getType() {
		return ModelColumn::T_STRING;
	}

	public function isNullable() {
		return true;
	}

	protected function doGetOrderFieldAlias(QueryAliasable $aliasable, $alias) {
		// Age virtual field is just for display... Sorting on this field would
		// result in sorting alphabetically, not chronologically.
		// ... So let's sort on the real date field, instead.
		if (count($parts = explode('->', $alias)) > 1) {
			array_pop($parts);
			array_push($parts, $this->dateField);
			return Query::quoteName(implode('->', $parts));
		} else {
			return $aliasable->getQualifiedName($this->dateField);
		}
	}

	protected function doGetClause(QueryAliasable $aliasable) {
		$dateField = $aliasable->getQualifiedName($this->dateField);
		return <<<SQL
(SELECT( CONVERT(CONCAT(IF((@years := (DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT($dateField, '%Y')
- (@postBD := (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT($dateField, '00-%m-%d')))
)) > 0,CONCAT(@years,' an',IF(@years>1,'s','')),''),IF(0, '', CONCAT(
IF((@months := FLOOR((@days := DATEDIFF(NOW(),DATE_FORMAT($dateField,
CONCAT(YEAR(CURRENT_DATE()) - @postBD,'-%m-%d')))) / 30.4375)) >= 0
,CONCAT(' ',@months,' mois'),''),IF(0, '', CONCAT(' '
,(@days := FLOOR(MOD(@days, 30.4375))),CONCAT(' jour',IF(@days>0,'s',''))
))))) USING utf8) ))
SQL;
//		return <<<SQL
//(SELECT( CONVERT(CONCAT(IF((@years := (DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT($dateField, '%Y')
//- (@postBD := (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT($dateField, '00-%m-%d')))
//)) > 0,CONCAT(@years,' an',IF(@years>1,'s','')),''),IF(@years > 18, '', CONCAT(
//IF((@months := FLOOR((@days := DATEDIFF(NOW(),DATE_FORMAT($dateField,
//CONCAT(YEAR(CURRENT_DATE()) - @postBD,'-%m-%d')))) / 30.4375)) >= 0
//,CONCAT(' ',@months,' mois'),''),IF(@years >= 3, '', CONCAT(' '
//,(@days := FLOOR(MOD(@days, 30.4375))),CONCAT(' jour',IF(@days>0,'s',''))
//))))) USING utf8) ))
//SQL;
//		return <<<SQL
//(SELECT( CONVERT(CONCAT(IF((@years := (DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT($dateField, '%Y')
//- (@postBD := (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT($dateField, '00-%m-%d')))
//)) > 0,CONCAT(@years,' an',IF(@years>1,'s','')),''),IF(@years >= 21, '', CONCAT(
//IF((@months := FLOOR((@days := DATEDIFF(NOW(),DATE_FORMAT($dateField,
//CONCAT(YEAR(CURRENT_DATE()) - @postBD,'-%m-%d')))) / 30.4375)) >= 0
//,CONCAT(IF(@year>1,' ',''),@months,' mois'),''),IF(@years >= 3, '', CONCAT(' '
//,(@days := FLOOR(MOD(@days, 30.4375))),CONCAT(' jour',IF(@days>0,'s',''))
//))))) USING utf8) ))
//SQL;
	}
}

class FormattedVirtualField extends VirtualFieldBase {

	protected $format;
	private $nullable;

	function __construct($format, $defaultAlias = null, $nullable = true) {
		parent::__construct($defaultAlias);
		$this->nullable = $nullable;
		$this->format = $format;
	}

	public function getType() {
		return ModelColumn::T_STRING;
	}

	public function isNullable() {
		return $this->nullable;
	}

	protected function doGetClause(QueryAliasable $aliasable) {
		return Query::format($this->format, $aliasable);
	}
}