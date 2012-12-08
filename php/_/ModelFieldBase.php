<?php

use eoko\cqlix\ModelFieldHelper;
use eoko\cqlix\Aliaser\Aliaser;

/**
 * Base class implementation for {@link ModelField}.
 * 
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 22 nov. 2011
 */
abstract class ModelFieldBase implements ModelField {

	public function castValue($value) {
		if ($value === null) {
			return null;
		}
		$type = $this->getType();
		switch ($type) {
			case ModelField::T_INT:
				return (int) $value;
			case ModelField::T_BOOL:
				return (bool) $value;
			case ModelField::T_FLOAT:
				return (double) $value;
			case ModelField::T_ENUM:
				return ModelFieldHelper::castValue($value, $this->getSqlType());
			case ModelField::T_DATE:
			case ModelField::T_DATETIME:
//				throw new UnsupportedOperationException('Not implemented yet');
			case ModelField::T_DECIMAL:
			case ModelField::T_STRING:
			case ModelField::T_TEXT:
				return $value;
			default:
				throw new Exception('Unknown type: ' . $type);
				return $value;
		}
	}

	public function getActualField() {
		return $this;
	}

	public function hasDefault() {
		return false;
	}

	public function getDefault() {}

	public function isPrimary() {
		return false;
	}

	/**
	 * Default implementation for {@link ModelField::getLength()}. This 
	 * implementation always return `null`.
	 * @return null
	 */
	public function getLength() {
		return null;
	}

	protected function createClause($clause) {
		if (is_string($clause) && !preg_match('/^\(.+\)$/', $clause)) {
			return "($clause)";
		} else if ($clause instanceof Query) {
			return new QuerySelectSub($clause);
		} else {
			return $clause;
		}
	}

	final public function getSortClause($dir, QueryAliasable $aliaser) {
		$query = $aliaser->getQuery();
		// protected dir
		$dir = $query->protectDir($dir);
		return $this->assembleSortClause($dir, $query, $aliaser);
	}

	/**
	 * @param string $dir Direction clause, already protected.
	 * @param ModelTableQuery $query
	 * @param QueryAliasable $aliaser
	 * @return string
	 */
	protected function assembleSortClause($dir, ModelTableQuery $query, QueryAliasable $aliaser) {
		return $this->makeSortClause($query, $aliaser) . ' ' . $dir;
	}

	/**
	 * @param ModelTableQuery $query
	 * @param QueryAliasable $aliaser
	 * @return string
	 */
	protected function makeSortClause(ModelTableQuery $query, QueryAliasable $aliaser) {
		return $this->doMakeSortClause($aliaser);
	}

	/**
	 * @param QueryAliasable $aliaser
	 * @return string
	 */
	protected function doMakeSortClause(QueryAliasable $aliaser) {
		return $aliaser->getQualifiedName($this->getName());
	}

}
