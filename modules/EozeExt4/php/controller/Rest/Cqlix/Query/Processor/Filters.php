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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix\Query\Processor;

use eoko\modules\EozeExt4\controller\Rest\Cqlix\Exception;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Request\Params as RequestParams;
use eoko\util\date\Date;
use ModelRelationInfo;
use eoko\modules\EozeExt4\Exception\InvalidArgument;

/**
 * Processor for column filters.
 *
 * @since 2013-05-17 15:05
 */
class Filters extends AbstractProcessor {

	/**
	 * Accepted comparison operators.
	 *
	 * @var array
	 */
	private static $acceptedOperators = array(
		'eq' => '=',
		'neq' => '!=',
		'gt' => '>',
		'lt' => '<',
		'gte' => '>=',
		'lte' => '<=',
	);

	/**
	 * Accepted comparison operators for filters of type 'date'.
	 *
	 * @var array
	 */
	private static $acceptedDateOperators = array(
		'eq' => '=',
		'neq' => '!=',
		'gt' => '>=',
		'lt' => '<',
//		'gte' => '>=',
//		'lte' => '<',
	);

	/**
	 * Accepted comparison operator for filters of type 'age'.
	 *
	 * @var array
	 */
	private static $acceptedAgeOperators = array(
		'neq' => '!=',
		'eq' => '=',
		'gt' => '<',
		'lt' => '>',
		'gte' => '<=',
		'lte' => '>=',
	);

	/**
	 * Accepted filter types.
	 *
	 * @var array
	 */
	private static $acceptedTypes = array(
		'boolean' => 'boolean',
		'date' => 'date',
		'list' => 'list',
		'numeric' => 'numeric',
		'string' => 'string',
		'age' => 'age',
		'emptyvalue' => 'emptyValue',
		'emptyValue' => 'emptyValue',
	);

	/**
	 * Filters configuration data.
	 *
	 * @var array[]
	 */
	private $filters = array();

	/**
	 * @inheritdoc
	 */
	protected function setData(array $data) {
		$this->filters = $data;
	}

	/**
	 * @inheritdoc
	 */
	public function apply(\ModelTableQuery $query) {

		$table = $query->getTable();

		foreach ($this->filters as $filter) {

			if (isset($filter['property'])) {
				$this->applyPropertyFilter($query, $filter);
				continue;
			}

			$fieldName = $this->resolveFieldName($filter['field']);

			// Depends on the client code version
			if (isset($filter['data'])) {
				$data = $filter['data'];
			} else {
				$data = $filter;
			}

			if (isset(self::$acceptedTypes[$data['type']])) {
				$type = self::$acceptedTypes[$data['type']];
			} else {
				throw new InvalidArgument('Invalid filter type: ' . $data['type']);
			}

			$value = isset($data['value'])
				? $data['value']
				: null;

			switch ($type) {
				case 'date':
					$format = isset($data['dateFormat'])
						? $data['dateFormat']
						: 'd/m/Y';
					$date = \DateTime::createFromFormat($format, $value);
					$value = $date->format('Y-m-d');
					$op = self::$acceptedDateOperators[$data['comparison']];
					$query->andWhere("DATE(`$fieldName`) $op ?", $value);
					break;

				case 'numeric':
					$op = self::$acceptedOperators[$data['comparison']];
					$query->andWhere("`$fieldName` $op ?", $value);
					break;

				case 'boolean':
					$query->andWhere("`$fieldName` IS " . ($value ? 'TRUE' : 'FALSE'));
					break;

				case 'list':
					$field = $table->getField($fieldName);

					while (method_exists($field, 'getColumnFilterField')) {
						/** @noinspection PhpUndefinedMethodInspection */
						$fieldName = $field->getColumnFilterField();
						$field = $table->getField($fieldName);
					}

					// If the field points directly to a relation (not a relation
					// field), we must specify that we aim at the id field
					if ($field instanceof ModelRelationInfo) {
						$fieldName .= '->' . $field->getTargetTable()->getPrimaryKeyName();
					}

					// Processing filter
					$where = $query->createWhere();
					foreach ($value as $i => $field) {
						if ($field === '${null}') {
							$where->orWhere("`$fieldName` IS NULL");
							unset($value[$i]);
						}
					}
					if ($value) {
						$where->orWhereIn($fieldName, $value);
					}
					$query->andWhere($where);
					break;

				case 'string':
					if (!empty($value)) {
						$search = $this->processColumnFilterString($value);
						$query->andWhere("`$fieldName` LIKE ?", $search);
					}
					break;

				case 'age':
					if ($value !== null) {
						// operator
						$op = self::$acceptedAgeOperators[$data['comparison']];

						// reference date
						$context = $query->getContext();
						if (!isset($context['date'])) {
							throw new \MissingRequiredRequestParamException('Missing date');
						}
						$now = Date::parseDate($context['date']);
						$date = $now->sub(new \DateInterval($value))->format('Y-m-d');

						// dob column
						$field = $table->getField($data['field']);
						if ($field->getActualField() instanceof \AgeVirtualField) {
							/** @var \AgeVirtualField $field */
							$dobField = $field->getDateField($query);
						} else {
							throw new \RuntimeException("Not an age field: $data[field]");
						}

						$query->andWhere("`$dobField` $op ?", $date);
					}
					break;
			}

			// Empty
			if (isset($data['acceptEmpty']) && !$data['acceptEmpty']) {
				$query->andWhere("`$fieldName` IS NOT NULL");
			}
			if (isset($data['acceptNonEmpty']) && !$data['acceptNonEmpty']) {
				$query->andWhere("`$fieldName` IS NULL");
			}
		}
	}

	/**
	 * Handles filters with the following format:
	 *
	 *     array(
	 *         'property' => ...,
	 *         'value' => ...,
	 *     )
	 *
	 * @param \ModelTableQuery $query
	 * @param array $filter
	 * @throws InvalidArgument
	 */
	private function applyPropertyFilter(\ModelTableQuery $query, array $filter) {
		if (isset($filter['property'])) {
			if (isset($filter['value'])) {
				$property = $filter['property'];
				$value = $filter['value'];
				if (is_array($property)) {
					$where = $query->createWhere();
					foreach ($property as $p) {
						$field = $this->resolveFieldName($p, false);
						if ($field) {
							$where->orWhere("`$field` LIKE ?", "%$value%");
						}
					}
					$query->andWhere($where);
				} else {
					$field = $this->resolveFieldName($property, false);
					if ($field) {
						$query->andWhere("`$field` LIKE ?", "%$value%");
					}
				}
			} // else, let's say that's not a filter!
		} else {
			throw new InvalidArgument();
		}
	}

	/**
	 * Transforms the input value to a SQL search string, replacing jokers accepted on the client
	 * side with the ones accepted by the database engine.
	 *
	 * @param string $value
	 * @return string
	 */
	protected function processColumnFilterString($value) {
		$value = str_replace('%', '\\%', $value);
		$value = str_replace('_', '\\_', $value);
		$value = str_replace('*', '%', $value);
		$value = str_replace('?', '_', $value);
		return $value;
	}

	/**
	 * @inheritdoc
	 */
	public function getResponseMetaData(RequestParams $requestParams) {
		return array(
			$requestParams->getParamName($requestParams::FILTERS) => $this->filters,
		);
	}
}
