<?php
/**
 * Copyright (C) 2013 Eoko
 *
 * This file is part of Opence.
 *
 * Opence is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Opence is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Opence. If not, see <http://www.gnu.org/licenses/gpl.txt>.
 *
 * @copyright Copyright (C) 2013 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

use eoko\cqlix\FieldMetadata;

/**
 * @todo doc
 *
 * @since 2013-10-02 12:58
 */
abstract class VirtualFieldBase extends ModelFieldBase implements VirtualField {

	protected $alias;
	protected $cachable = true;

	protected $type = null;
	protected $maxLength = null;
	protected $defaultAlias = null;

	/**
	 * @var FieldMetadata
	 */
	private $meta;

	public function __construct($alias = null) {
		if ($alias !== null) {
			$this->alias = $alias;
		} else if ($this->defaultAlias) {
			$this->alias = $this->defaultAlias;
		} else if ($this->alias) {
			if ($this->alias === true) {
				$this->alias = $this->guessAliasFromClassName();
			}
		}
	}

	public function configureMeta($config) {
		if ($config !== null) {
			if (!is_array($config)) {
				if (is_string($config)) {
					$config = array(
						'label' => $config,
					);
				} else {
					throw new IllegalStateException('Invalid virtual configuration: '
					. print_r($config, true));
				}
			}
		}
		$this->meta = new FieldMetadata($config);
	}

	public function getMeta() {
		return $this->meta;
	}

	/**
	 * Implementation of the {@link ModelField::getLength()} method.
	 *
	 * This method will return the value of {@link maxLength}, which is `null`
	 * by default.
	 *
	 * @return int|null
	 */
	public function getLength() {
		return $this->maxLength;
	}

	private function guessAliasFromClassName() {
		$class = get_class($this);
		if (preg_match('/(?:^|_|\\\\)([^_\\\\]+?)(?:VirtualField)?$/', $class, $matches)) {
			return lcfirst($matches[1]);
		} else {
			return null;
		}
	}

	public function isCachable() {
		return $this->cachable;
	}

	public function isSelectable(QueryAliasable $aliaser) {
		return true;
	}

	public function getName() {
		if ($this->alias !== null) {
			return $this->alias;
		} else {
			throw new UnsupportedOperationException(get_class($this) . '::getName()');
		}
	}

	public function setAlias($alias) {
		$this->alias = $alias;
		return $this;
	}

	public function getType() {
		if ($this->type !== null) {
			return $this->type;
		} else {
			throw new UnsupportedOperationException(get_class($this) . '::getType()');
		}
	}

	public function getSqlType() {
		return $this->getType();
	}

	public function isNullable() {
		return true;
	}

	public function select(ModelTableQuery $query, $alias = null, QueryAliasable $aliasable = null) {
		if ($alias === null) {
			$alias = $this->alias;
		}

		$clause = $this->getClause($query, $aliasable);

		$bindings = null;
		if ($clause instanceof SqlVar) {
			$clause = $clause->buildSql(false, $bindings);
		}

		return $query->select(
			new QuerySelectRaw(
				"$clause AS `$alias`",
				$bindings
			)
		);
	}

	public function getClause(ModelTableQuery $query, QueryAliasable $aliasable = null) {
		return $this->createClause($this->doGetClause($aliasable !== null ? $aliasable : $query));
	}

	protected function doGetClause(QueryAliasable $aliasable) {
		if (null !== $clause = $this->getQualifiedClause()) {
			return $aliasable->convertQualifiedNames($clause, $bindings);
		} else {
			throw new UnsupportedOperationException(get_class($this) . '::doGetClause()');
		}
	}

	/**
	 * Can be implemented by child classes instead of {@link doGetClause()}. Must
	 * return a string in which field names will be
	 * {@link QueryAliasable::convertQualifiedNames() interpreted}.
	 * @return string|null
	 */
	protected function getQualifiedClause() {
		return null;
	}
}
