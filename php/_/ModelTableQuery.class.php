<?php

class ModelTableQuery extends Query implements QueryAliasable {

	public $dbTable;
	
	/** @var myModelTable */
	public $table;

	function  __construct(myModelTable $table = null, array $context = null) {
		parent::__construct($table, $context);
		$this->dbTable = $table->getDBTableName();
		$this->table = $table;
	}

	/**
	 *
	 * @param SqlVariable $field_s
	 * @param <type> $aliasPrefix
	 * @return ModelTableQuery
	 */
	public function select($field_s = null, $aliasPrefix = null) {
		
		if ($aliasPrefix !== null) {
			throw new IllegalArgumentException('DEPRECATED');
		}

		if ($this->action === null) {
			$this->action = self::SELECT;
		}

		if ($field_s === null) {
			if ($aliasPrefix === null) {
				$this->select = null;
			} else {
				throw new UnsupportedActionException();
			}
		} else if ($field_s instanceof SqlVariable) {
			$this->select[] = $field_s;
		} else if ($field_s === '*') {
			$this->select[] = new QuerySelectRaw("`$this->dbTable`.*");
		} else if (!is_array($field_s)) {
			$this->selectField($field_s);
		} else if (is_array($field_s)) { // $field_s is an array
			foreach ($field_s as $k => $v) {
				$this->selectField(
					$v,
					is_string($k) ? $k : null
				);
			}
		} else {
			throw new IllegalArgumentException($field_s . (is_object($field_s) ?
					'(' . get_class($field_s) . ')' : null));
		}

		return $this;
	}
	
	/**
	 * @param string $sql
	 * @return ModelTableQuery
	 */
	public function selectRaw($sql) {
		return $this->select(new QuerySelectRaw($sql));
	}

	public function selectField($fieldName, $fieldAlias = null) {
		
		// SqlVar will select itself as it intends
		if ($fieldName instanceof SqlVar) {
			$this->select[] = $fieldName;
		} 

		// So, we've got a string...
		else {
			// Is this a multipart relation?
			if (count($fieldParts = explode('->', $fieldName)) > 1) {
				$fieldAlias = $fieldAlias !== null ? "$fieldAlias" : "$fieldName";
				$joinFieldName = array_pop($fieldParts);
				$fieldName = implode('->', $fieldParts);
			}
			
			// Process assoc relations (in the type <AssocRelationName>fieldName)
			else if (ModelTable::parseAssocRelationField($fieldName, $relation, $field)) {
				$this->table->getRelationInfo($relation)->getAssocRelationInfo()
						->selectAssocFields(
							$this,
							array($fieldAlias !== null ? "$fieldAlias" : "$fieldName" => $field)
						);
				return;
			}
			
			// else, $fieldName must point to a raw relation (eg. Contact) or a known
			// column

			$field = $this->table->getField($fieldName);
			
			// ModelColumn
			if ($field instanceof ModelColumn) {
				parent::select(new QuerySelect($fieldName, $fieldAlias, $this->table));
			}
			// ModelRelationInfo
			else if ($field instanceof ModelRelationInfo) {
				$alias = $fieldAlias !== null ? $fieldAlias : $fieldName;
				// 
				if (isset($joinFieldName)) {
					$field->selectFields($this, array($alias => $joinFieldName));
				}
				// If $fieldName point to a plain relation (eg. Contact), then use
				// ModelRelationInfo::selectName
				else {
					$field->selectName($this, $alias);
				}
			}
			// VirtualField
			else if ($field instanceof VirtualField) {
				$field->select($this);
			}
			// Unexpected...
			else {
				throw new IllegalStateException();
			}
		}
	}

	protected function buildCountField() {
		if ($this->table->hasPrimaryKey()) {
			return $count = 'DISTINCT ' . $this->getQualifiedName($this->table->getPrimaryKeyName());
		} else {
			return $count = '*';
		}
	}

//	public function orWhere($field, $value, $operator = '=') {
//		if ($this->modelTable->hasColumn($field)) {
//			parent::orWhere("`$this->table`.`$field` $operator ?", $value);
//		} else {
//			$relation = $this->modelTable->getRelationInfo($field);
//			parent::orWhere($this->join($relation)->formatWhere(
//					$relation->getTargetTable()->getNameFieldName(), $value, $operator),
//					$value);
//		}
//	}

	public function setFromField($col, $fieldFrom) {

		$field = $this->table->getField($fieldFrom);

		if ($field instanceof ModelColumn) {
			return $this->set($col, new SqlVariable($this->getQualifiedName($fieldFrom)));
		} else if ($field instanceof ModelRelationInfo) {
			return $this->set($col, new SqlVariable($field->getNameClause($this)));
		} else if ($field instanceof VirtualField) {
			// TODO: Untested
			return $this->set($col, new SqlVariable($field->getClause($this)));
		} else {
			throw new IllegalStateException();
		}
	}

	public function cb_convertQualifiedNames($in) {
		return $in[1] . $this->getQualifiedName($in[2]) . $in[3];
	}

	public function cb_convertQualifiedNamesUnquoted($in) {
		return $in[1] . $this->getQualifiedName($in[2]) . $in[3];
	}

	public function doConvertQualifiedNames($preSql,
			QualifiedNameConverter $converter) {

		$staticPlaceHolder = '#S#T#A#T#I#C#P#H#';
		$preSql = str_replace('``', $staticPlaceHolder, $preSql);
		
		$preSql = preg_replace_callback(
			'/(^|\s)([^ `#]+?->[^` ]+?)([ !=]|$)/',
//			array($this, 'cb_convertQualifiedNamesUnquoted'),
			array($converter, 'convert'),
			preg_replace_callback(
					'/(^|[^.])`([a-zA-Z0-9\-_>]*)`([^.]|$)/',
//					array($this, 'cb_convertQualifiedNames'),
					array($converter, 'convert'),
					$preSql
			)
		);
		
		return str_replace($staticPlaceHolder, '`', $preSql);
	}

	public function convertQualifiedNames($preSql, &$bindings) {
		return $this->doConvertQualifiedNames($preSql,
				new QualifiedNameConverter($this, $bindings));
	}

	public function getOrderFieldAlias($field) {
		if ($this->table->hasColumn($field)) {
			$tableAlias = $this->tableAlias !== null ? $this->tableAlias : $this->dbTable;
			return "`$tableAlias`.`$field`";
		} else if ($this->table->hasVirtual($field)) {
			return $this->table->getVirtual($field)->getOrderFieldAlias($this);
//			return "`$field`";
		} else {
			if (count($parts = explode('->', $field)) > 1) {
				$fieldName = array_pop($parts);
				$relationName = implode('->', $parts);
				$relation = $this->table->getRelationInfo($relationName);
				if ($relation->targetTable->hasRelation($fieldName)) {
					// TODO rx search on linked fields
					// That won't work... the alias is not authorized in the where clause.
					// Either a @variable must be used, or the aliased thing must be
					// repeated... In both cases, it must be found what has been
					// aliased by the relation. That means:
					// - The type of select that has been done
					// The @variable solution is preferable to avoid code dupplication
					// and potential naming problem (though a name clash doesn't
					// seem possible).
					return Query::quoteName($field);
				} else if ($relation->targetTable->hasVirtual($fieldName)) {
					// Virtual field on foreign table
					// eg. Contact->age
					return $relation->targetTable->getVirtual($fieldName)
							->getOrderFieldAlias($this, $field);
//					return Query::quoteName($field);
				} else if ($relation->targetTable->hasColumn($fieldName)) {
					// to be analysed and probably corrected
					return Query::quoteName($field);
				} else {
					throw new IllegalArgumentException("No field '$field' in $this->dbTable");
				}
			} else if (count($parts = explode('.', $field)) == 2) {
				throw new IllegalArgumentException('DEPRECATED');
				list($relationName, $fieldName) = $parts;
			} else {
				$relationName = $field;
				$fieldName = null;
			}

			if ($this->table->hasRelation($relationName)) {
				$relation = $this->table->getRelationInfo($relationName);
				if ($fieldName === null) $fieldName = $relation->getTargetTable()->getNameFieldName();
				return $this->join($relation)->getQualifiedName($fieldName);
			}
		}
	}

	public function getQualifiedAlias($field) {
		if ($this->table->hasColumn($field)) {
			$tableAlias = $this->tableAlias !== null ? $this->tableAlias : $this->dbTable;
			return "`$tableAlias`.`$field`";
		} else if ($this->table->hasVirtual($field)) {
			return "`$field`";
		} else {
			if (count($parts = explode('->', $field)) > 1) {
				$fieldName = array_pop($parts);
				$relationName = implode('->', $parts);
				$relation = $this->table->getRelationInfo($relationName);
				if ($relation->targetTable->hasRelation($fieldName)) {
					// TODO rx search on linked fields
					// That won't work... the alias is not authorized in the where clause.
					// Either a @variable must be used, or the aliased thing must be
					// repeated... In both cases, it must be found what has been
					// aliased by the relation. That means:
					// - The type of select that has been done
					// The @variable solution is preferable to avoid code dupplication
					// and potential naming problem (though a name clash doesn't
					// seem possible).
					return Query::quotename($field);
				} else if ($relation->targetTable->hasVirtual($fieldName)) {
					// Virtual field on foreign table
					// eg. Contact->age
					return "`$field`";
				} else if ($relation->targetTable->hasColumn($fieldName)) {
					throw new IllegalArgumentException("No field '$field' in $this->dbTable");
				}
			} else if (count($parts = explode('.', $field)) == 2) {
				throw new IllegalArgumentException('DEPRECATED');
				list($relationName, $fieldName) = $parts;
			} else {
				$relationName = $field;
				$fieldName = null;
			}

			if ($this->table->hasRelation($relationName)) {
				$relation = $this->table->getRelationInfo($relationName);
				if ($fieldName === null) $fieldName = $relation->getTargetTable()->getNameFieldName();
				return $this->join($relation)->getQualifiedName($fieldName);
			}
		}
	}

	/**
	 * @return string
	 */
	public function getQualifiedName($field, $ignored = QueryJoin::TABLE_RIGHT) {
		if ($this->table->hasColumn($field)) {
			$tableAlias = $this->tableAlias !== null ? $this->tableAlias : $this->dbTable;
			return "`$tableAlias`.`$field`";
		} else if ($this->table->hasVirtual($field)) {
			return $this->table->getVirtual($field)->getClause($this);
		} else {
			if (count($parts = explode('->', $field)) > 1) {
				$fieldName = array_pop($parts);
				$relationName = implode('->', $parts);
				$relation = $this->table->getRelationInfo($relationName);
				if ($relation->targetTable->hasRelation($fieldName)) {
					// relation name
					$targetRelation = $relation->getRelationInfo($fieldName);
					return $targetRelation->getNameClause($this, $field);
				} else if ($relation->targetTable->hasVirtual($fieldName)) {
					// virtual
					return $relation->targetTable->getVirtual($fieldName)->getClause(
						$this, $this->getJoin($relationName)
					);
				} else {
					// field
					return $this->getJoin($relationName)->getQualifiedName($fieldName);
				}
//				$relation = $this->table->getRelationInfo($relationName);
//				if ($relation->targetTable->hasRelation($fieldName)) {
//					// TODO rx search on linked fields
//					// That won't work... the alias is not authorized in the where clause.
//					// Either a @variable must be used, or the aliased thing must be
//					// repeated... In both cases, it must be found what has been
//					// aliased by the relation. That means:
//					// - The type of select that has been done
//					// The @variable solution is preferable to avoid code dupplication
//					// and potential naming problem (though a name clash doesn't
//					// seem possible).
//					return "`$field`";
//				} else if (!$relation->targetTable->hasColumn($fieldName)) {
//					throw new IllegalArgumentException("No field '$field' in $class");
//				}
			} else if (count($parts = explode('.', $field)) == 2) {
				throw new IllegalArgumentException('DEPRECATED');
				list($relationName, $fieldName) = $parts;
			} else {
				$relationName = $field;
				$fieldName = null;
			}

			if ($this->table->hasRelation($relationName)) {
				$relation = $this->table->getRelationInfo($relationName);
				
				// 16/03/12 18:00 Removed:
				
				// if ($fieldName === null) {
				// 	$fieldName = $relation->getTargetTable()->getNameFieldName();
				// }
				// return $this->join($relation)->getQualifiedName($fieldName);
				
				// 16/03/12 18:00 Added:
				
				return $relation->getNameClause($this);
			}
		}

		// not found
		$class = get_class($this->table);
		throw new IllegalArgumentException("No field '$field' in $class");
	}

	public function getRelationInfo($relationName, $requireType = false) {
		return $this->table->getRelationInfo($name, $requireType);
	}

	function makeRelationName($targetRelationName) {
		return $targetRelationName;
	}

	public function createQualifiedFormattedSelect($alias, $format) {
		return new QueryFormattedSelect($this, $alias, $format, $this->table);
	}

	protected $joinReferences = array();

	/**
	 * @return QueryJoin
	 * 
	 * 11/06/12 12:25 Changed 3rd param $leftAlias to $leftTableName
	 */
	public function join(ModelRelationInfo $relation, $alias = null, $leftTableName = null) {
		$index = $alias !== null ? $alias : $relation->name;
//		dump_after(func_get_args());
//		dump_after(array("$relation", $alias, $leftAlias));
//		dump_trace(false);
//		dumpl(func_get_args());
//		if ($alias === 'Cache->Conjoint*') {
//			dumpl(array(
//				$index,
//				$this->joins,
//				isset($this->joins[$index])
//			));
//			dump_mark();
//		}

		// Scans the existing joins to find the alias of the join for the specified
		// $leftTableName.
		// 
		// Experience has shown that things that call this method tend to know only
		// the db name of the table they use, not the possible alias that has been
		// applied to create the join.
		// 
		// This becomes a problem specifically in the case that the join is not made
		// on the FROM table, but on another joined table instead. Indeed, the table
		// name will have been aliased by the join (eg. MyFirstTable->MyTargetTable for
		// my_target_table_records in the db), but the caller of this method will
		// use the db table name.
		// 
		// So, what we do here is scanning all existing relation to see if there is
		// already a join (and so an alias) to the left table.
		// 
		//     $this->dbTable !== $leftTableName
		//     // As said, the problem never happens with the FROM table
		//     
		//     isset($this->joins) // $this->joins is null at initialization
		//
		if ($this->dbTable !== $leftTableName && isset($this->joins)) {
			foreach ($this->joins as $joinAlias => $join) {
				assert('$join instanceof QueryJoin');
				if ($join->getForeignTableName() === $leftTableName) {
					$leftTableName = $join->getForeignTableAlias();
				}
			}
		}
		
		if (isset($this->joins[$index])) {
			return $this->joins[$index];
		} else if (isset($this->joinReferences[$index])) {
			return $this->joinReferences[$index];
//		} else if (!isset($this->joins[$index])) {
		} else {
			// $join->buildSql() must be called before the join is added to the join list,
			// in order to ensure that other joins required by the new join will be
			// built before it.
			$bindings = array();
			if (is_array($join = $relation->createJoin($this, $alias, $leftTableName))) {
				$join[0]->buildSql($bindings); // see upper
				return $this->joinReferences[$index] = $join[0];
			} else {
				$join->buildSql($bindings); // see upper
				return $this->joins[$index] = $join;
			}
		}
	}

	/**
	 *
	 * @param string $fieldName
	 * @return QueryJoin
	 */
	public function getJoin($fieldName) {
		return $this->join($this->table->getRelationInfo($fieldName));
	}

	/**
	 * @todo Test
	 */
	public function whereContext($context = null) {
		if ($context !== null) {
			$this->table->addAssocWhere($this->where, $this);
		}
		return $this;
	}

	/**
	 * @return ModelTableQuery
	 */
	public function getSelectSubQuery($alias, $require = true) {
		foreach ($this->select as $select) {
			if ($select instanceof QuerySelectSub) {
				if ($select->alias === $alias) return $select->query;
			}
		}
		if ($require) {
			throw new IllegalStateException("No subquery for alias: $alias");
		} else {
			return null;
		}
	}

	public function getQuery() {
		return $this;
	}

	public function & getContext() {
		return $this->context;
	}

	/**
	 *
	 * @param ModelTable $table
	 * @param string|QueryWhere $condition
	 * @param array|scalar $inputs
	 * @return ModelTableQuery 
	 */
	public function applyAssocWhere(ModelTable $table, $condition = null, $inputs = null) {
		$where = $this->createWhere($condition, $inputs);
		$table->addAssocWhere($where, $this);
		if (!$where->isNull()) {
			$this->andWhere($where);
		}
		return $this;
	}
	
}

class QualifiedNameConverter {

	/** @var ModelTableQuery */
	private $aliasable;
	private $aliasableTable;
	private $bindings, $defaultTable;

	function __construct(QueryAliasable $aliasable, &$bindings, $aliasableTable = null) {
		$this->bindings =& $bindings;
		$this->aliasable = $aliasable;
		$this->aliasableTable = $aliasableTable;
	}

	public function convert($in) {
		$s = $this->aliasable->getQualifiedName($in[2], $this->aliasableTable);
		if ($s instanceof SqlVar) {
			$pre = $in[1] !== '`' ? $in[1] : null;
			$suf = $in[3] !== '`' ? $in[3] : null;
			return $pre . $s->buildSql($this->defaultTable, $this->bindings) . $suf;
		} else {
			return "$in[1]$s$in[3]";
		}
	}
}
