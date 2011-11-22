<?php

interface VirtualField extends ModelField {

	function select(ModelTableQuery $query, $alias = null, QueryAliasable $aliasable = null);

	function getClause(ModelTableQuery $query, QueryAliasable $aliasable = null);

	function getOrderFieldAlias(QueryAliasable $aliasable, $alias = null);
	
	function isCachable();
}

abstract class VirtualFieldBase extends ModelFieldBase implements VirtualField {

	protected $alias;
	protected $cachable = true;
	
	protected $type = null;
	protected $defaultAlias = null;

	function __construct($alias = null) {
		if ($alias !== null) {
			$this->alias = $alias;
		} else if ($this->alias) {
			if ($this->alias === true) {
				$this->alias = $this->guessAliasFromClassName();
			}
		} else {
			$this->alias = $alias !== null ? $alias : $this->defaultAlias;
		}
	}
	
	private function guessAliasFromClassName() {
		$class = get_class($this);
		if (preg_match('/(?:^|_)([^_]+?)(?:VirtualField)?$/', $class, $matches)) {
			return lcfirst($matches[1]);
		}
	}
	
	public function isCachable() {
		return $this->cachable;
	}
	
	public function getName() {
		if ($this->alias !== null) {
			return $this->alias;
		} else {
			throw new UnsupportedOperationException(get_class($this) . '::getName()');
		}
	}
	
	public function getType() {
		if ($this->type !== null) {
			return $this->type;
		} else {
			throw new UnsupportedOperationException(get_class($this) . '::getType()');
		}
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

class AbstractVirtualField extends VirtualFieldBase {
	
	protected $alias = true;
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

class FormattedVirtualField extends AbstractVirtualField {

	protected $format = null;
	protected $nullable = true;
	protected $type = ModelColumn::T_STRING;

	function __construct($format = null, $defaultAlias = null, $nullable = null) {
		parent::__construct($defaultAlias);
		$this->nullable = $nullable;
		if ($format !== null) {
			$this->format = $format;
		}
		if ($defaultAlias !== null) {
			$this->defaultAlias = $defaultAlias;
		}
		if ($nullable !== null) {
			$this->nullable = $nullable;
		}
	}

	public function isNullable() {
		return $this->nullable;
	}

	protected function doGetClause(QueryAliasable $aliasable) {
		return Query::format($this->format, $aliasable);
	}
}