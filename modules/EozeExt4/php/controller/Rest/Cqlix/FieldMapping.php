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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix;

use Model;
use ModelTable;
use ModelTableQuery;
use Query;
use eoko\cqlix\Query\OrderByField;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Exception\UnknownField as UnknownFieldException;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy\TableProxy\IdQueryCache;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy\TableProxy\ExpandedFields;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\RecordParser;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Record;
use eoko\modules\EozeExt4\Exception;

/**
 *
 * - reader
 *   - requires
 *   - readFromModel
 *
 * - proxy
 *   - class
 *   - table
 *   - field
 *
 * - expandable
 * - defaultExpand
 *
 * @since 2013-04-23 14:11
 */
class FieldMapping {

	private static $defaultProxyClass = 'eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy\DefaultTableProxy';

	/**
	 * @var array
	 */
	private $requiredFields = null;

	/**
	 * Expanded fields helper.
	 *
	 * @var ExpandedFields
	 */
	private $expandedFields;

	private $clientToServerMap;
	private $serverToClientMap;

	private $clientFieldsConfig;

	/**
	 * @var string[]
	 */
	private $clientFieldsCache;
	private $serverFields;

	/**
	 * @todo doc
	 * @var DataProxy[]
	 */
	private $proxies;

	/**
	 * @var \ModelTable
	 */
	private $table;

	public function __construct(ModelTable $table, array $clientToServerFieldMap) {
		$this->table = $table;
		$this->createFieldMaps($clientToServerFieldMap);
	}

	private function createFieldMaps(array $clientToServerFieldMap) {

		$this->clientToServerMap = array();
		$this->serverToClientMap = array();

		foreach ($clientToServerFieldMap as $clientField => $serverField) {

			$config = null;

			if (is_array($serverField)) {
				$config = $serverField;

				$serverField = empty($serverField['field'])
					? null
					: $serverField['field'];

				if (!is_string($clientField)) {
					throw new Exception\Domain();
				}
			}

			else if ($serverField instanceof \Closure) {
				$config['reader'] = $serverField;
				$serverField = null;

				if (!is_string($clientField)) {
					throw new Exception\Domain();
				}
			}

			else {
				if (!is_string($clientField)) {
					$clientField =& $serverField;
				}
			}

			// Guess readFromModel option with reflection
			if (isset($config['reader']) && !isset($config['readFromModel'])) {
				/** @var $reader \Closure */
				$reader = $config['reader'];
				$function = new \ReflectionFunction($reader);
				$params = $function->getParameters();
				if (count($params) > 0) {
					$recordClass = $params[0]->getClass()->getName();
					$config['readFromModel'] = is_subclass_of($recordClass, 'Model');
				} else {
					$config['readFromModel'] = false;
				}
			}

			if (isset($config['requires'])) {
				foreach ($config['requires'] as $requiredField) {
					$this->requiredFields[$requiredField] = true;
				}
			}

			if (substr($clientField, 0, 2) === '??') {
				$clientField = substr($clientField, 2);
				$config[ExpandedFields::CFG_EXPANDABLE] = true;
				$config[ExpandedFields::CFG_DEFAULT_EXPAND] = false;
			}

			if (substr($clientField, 0, 1) === '?') {
				$clientField = substr($clientField, 1);
				$config[ExpandedFields::CFG_EXPANDABLE] = true;
				$config[ExpandedFields::CFG_DEFAULT_EXPAND] = true;
			}

			// Field name maps
			$this->clientToServerMap[$clientField] = $serverField;

			if ($serverField !== null) {

				$field = $this->table->getField($serverField);
				if ($field instanceof \ModelRelationInfo) {
					if (!isset($config['proxy'])) {
						$config['proxy'] = array(
							'class' => self::$defaultProxyClass,
							'table' => $field->getTargetTable(),
						);
					}
				}

				$this->serverToClientMap[$serverField] = $clientField;
			}

			// Save config
			$this->clientFieldsConfig[$clientField] = $config;

			unset($clientField);
		}

		// --- Fields

		$this->serverFields = array_keys($this->serverToClientMap);
	}

	/**
	 * @todo
	 * @return string[]
	 */
	public function getClientFields() {
		return $this->clientFieldsCache;
	}

	/**
	 * @return string[]
	 */
	public function getServerFields() {
		return $this->serverFields;
	}

	/**
	 * Implementation for {@link eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy\Simple::getServerFieldName()}.
	 *
	 * Nested client fields can be accessed:
	 *
	 * - either with dot notation (e.g. `myRelationField.targetField`),
	 * - or the successive field names can be passed as an array (e.g. `array('myRelationField', 'targetField')`)
	 *
	 * @param string|array $clientField
	 * @param bool $require
	 * @throws \UnsupportedOperationException
	 * @throws UnknownFieldException
	 * @return string|null
	 */
	public function getServerFieldName($clientField, $require = false) {
		if (is_array($clientField)) {
			return $this->resolveServerFieldName($clientField, $require);
		} else if (strstr($clientField, '.') === false) {
			return $this->getLocalServerFieldName($clientField, $require);
		} else {
			$nodes = explode('.', $clientField);
			return $this->resolveServerFieldName($nodes, $require);
		}
	}

	/**
	 * Resolves the specified client field path to the target server field name.
	 *
	 * @param array $nodes
	 * @param bool $require
	 * @throws UnknownFieldException
	 * @throws \UnsupportedOperationException
	 * @return null|string
	 */
	private function resolveServerFieldName(array $nodes, $require) {

		$clientField = array_shift($nodes);

		// If that was the last node, then it points to a field local to this proxy
		if (empty($nodes)) {
			return $this->getLocalServerFieldName($clientField, $require);
		}
		// ... else if the field points to a specific item in a collection
		// eg. field[12].childField
		else if (preg_match('/^(?<field>.+)\[(?<index>\d+)\]$/', $clientField, $matches)) {
			throw new \UnsupportedOperationException();
		}
		// ... else delegate to a sub proxy
		else {
			$proxy = $this->getProxy($clientField);

			if ($proxy) {
				return $this->getLocalServerFieldName($clientField, true) . '->' . $proxy->getServerFieldName($nodes);
			} else if ($require) {
				throw new UnknownFieldException($clientField);
			} else {
				return null;
			}
		}

	}

	/**
	 * Gets the server model field name for the specified client field local to this proxy.
	 *
	 * @param string $clientField
	 * @param bool $require
	 * @return string|null
	 * @throws UnknownFieldException
	 */
	private function getLocalServerFieldName($clientField, $require) {
		$map = $this->clientToServerMap;
		if (isset($map[$clientField])) {
			return $map[$clientField];
		} else if ($require) {
			throw new UnknownFieldException($clientField);
		} else {
			return null;
		}
	}

	/**
	 * @todo
	 */
	public function setExpandedParams($expandParam, $expandDefault) {

		$expandedFields = new ExpandedFields($expandDefault);
		$expandedFields->setExpandParam($expandParam);

		return $this->setExpandedFields($expandedFields);
	}

	/**
	 * @todo
	 */
	public function setExpandedFields(ExpandedFields $expandedFields) {

		// Store reference
		$this->expandedFields = $expandedFields;

		// Set fields config
		$fieldsConfig = array();
		foreach (array_keys($this->clientToServerMap) as $clientField) {
			$fieldsConfig[$clientField] = $this->clientFieldsConfig[$clientField];
		}

		// Refresh caches
		$this->clientFieldsCache = $expandedFields->getExpandedClientFields($fieldsConfig);

		return $this;
	}

	/**
	 * @param string $clientFieldName
	 * @return DataProxy|bool
	 */
	private function getProxy($clientFieldName) {
		if (!isset($this->proxies[$clientFieldName])) {
			$config = $this->clientFieldsConfig[$clientFieldName];
			if (isset($config['proxy'])) {
				$proxyConfig = $config['proxy'];
				$this->proxies[$clientFieldName] = $this->createProxy($proxyConfig, $clientFieldName);
			} else {
				$this->proxies[$clientFieldName] = false;
			}
		}
		return isset($this->proxies[$clientFieldName])
			? $this->proxies[$clientFieldName]
			: null;
	}

	private function createProxy($classOrConfig, $clientFieldName) {
		// Class & config
		if (is_string($classOrConfig)) {
			$class = $classOrConfig;
			$config = null;
		} else if (is_array($classOrConfig)) {
			if (isset($classOrConfig['class'])) {
				$class = $classOrConfig['class'];
				$config = $classOrConfig;
			} else {
				throw new Exception\Domain('Proxy class is required.');
			}
		} else {
			throw new Exception\Domain('Invalid proxy configuration: ' . print_r($classOrConfig, true));
		}

		// Create proxy
		/** @var $proxy DataProxy */
		$proxy = new $class($config);

		// Configure proxy
		$this->afterCreateProxy($proxy, $clientFieldName);

		// Return
		return $proxy;
	}

	// TODO
	private function afterCreateProxy(DataProxy $proxy, $clientFieldName) {
		$childExpandFields = $this->expandedFields->getChildExpandedFields($clientFieldName);
		$proxy->setExpandedFields($childExpandFields);
	}

	// TODO
	public function readField(Record $record, $clientFieldName) {

		$fieldName = $this->clientToServer($clientFieldName);
		$config = $this->clientFieldsConfig[$clientFieldName];

		$value = null;

		if ($fieldName) {
			$value = $record->get($fieldName);
		}

		if (!empty($config['reader'])) {
			if (empty($config['readFromModel'])) {
				$value = call_user_func($config['reader'], $record, $value);
			} else {
				$model = $record->getModel();
				$value = call_user_func($config['reader'], $model, $value);
			}
		}

		if (!empty($value)) {
			if (!empty($config['proxy'])) {
				$proxy = $this->getProxy($clientFieldName);

				if ($value instanceof \ModelSet || is_array($value)) {
					$set = $value;
					$value = array();
					foreach ($set as $record) {
						$value[] = $proxy->getRecordData($record);
					}
				} else {
					$record = $value;
					$value = $proxy->getRecordData($record);
				}
			}
		}

		return $value;
	}

	// TODO
	private function getFieldMetaData($clientFieldName) {
		$meta = array(
			'name' => $clientFieldName,
		);

		$serverFieldName = $this->getServerFieldName($clientFieldName);

		if ($serverFieldName !== null) {
			$serverField = $this->table->getField($serverFieldName);
			$fieldMeta = $serverField->getMeta();

			$meta['label'] = $fieldMeta->get('label');
			$meta['shortLabel'] = $fieldMeta->get('shortLabel');
			$meta['description'] = $fieldMeta->get('description');

			switch ($serverField->getType()) {
				case 'int':
					$meta['type'] = 'int';
					break;
				case 'text':
				case 'string':
					$meta['type'] = 'string';
					break;
				case 'datetime':
					$meta['type'] = 'date';
					break;
			}
		}

		return $meta;
	}

	/**
	 * @todo
	 */
	public function getMetaData() {
		$fields = array();
		foreach ($this->getClientFields() as $field) {
			$fields[] = $this->getFieldMetaData($field);

			$proxy = $this->getProxy($field);

			if ($proxy) {
				$md = $proxy->getMetaData();
				foreach ($md['fields'] as $meta) {
					$meta['name'] = $field . '.' . $meta['name'];
					$fields[] = $meta;
				}
			}
		}
		return array(
			'fields' => $fields,
		);
	}

	// TODO
	public function writeField(Model $record, $clientFieldName, $value) {
		$fieldName = $this->clientToServer($clientFieldName);
		return $record->setField($fieldName, $value);
	}

	// TODO
	private function clientToServer($field) {
		return isset($this->clientToServerMap[$field])
			? $this->clientToServerMap[$field]
			: null;
	}

	/**
	 * @param ModelTableQuery $query
	 * @param string $fieldPrefix
	 * @throws Exception\IllegalState
	 * @return RecordParser
	 */
	public function selectListFields(ModelTableQuery $query, $fieldPrefix = '') {

		// The query is not necessarily based on the same table as this mapping, since
		// this method can be called for children proxies (hence the field prefix, also)
		$rootTable = $query->getTable();

		// Creates the parser that maps selected fields in the query to the record proxy's
		// own fields.
		$parser = new RecordParser($this->table, $query->getContext());

		// The query will be limited either (1) by the initial limit if the working
		// query is the root query, or (2) the WHERE IN clause if the working query
		// is already a child of the root query.
		$idsCache = new IdQueryCache($query);

		$requiredFields = $this->requiredFields;

		foreach ($this->getClientFields() as $clientFieldName) {

			$serverField = $this->clientToServer($clientFieldName);

			if ($serverField) {

				$field = $rootTable->getField($fieldPrefix . $serverField);
				$proxy = $this->getProxy($clientFieldName);

				// Fully qualified server field name
				$fqFieldName = $fieldPrefix . $serverField;

				if ($field instanceof \ModelRelationInfo) {

					if (!$proxy) {
						throw new Exception\IllegalState(
							'Proxy is required for field: ' . $clientFieldName
						);
					}

					if ($field instanceof \ModelRelationInfoHasOne) {
						$childParser = $proxy->selectListFields($query, $fqFieldName . '->');
						$parser->addFieldDataParser($serverField, $childParser);
					} else {
						/** @var $field \ModelRelationInfo */
						$parentPkName = $field->getLocalTable()->getPrimaryKeyName();
						$rootPkField = $fieldPrefix . $parentPkName;

						$ids = $idsCache->getIds($rootPkField);

						// Child records data query
						//
						// This query must be based on the root query so that the child records can
						// be searched, filtered, etc. according to request parameters.
						//
						$childQuery = clone $query;
						$childQuery
							->resetSelect()
							->select($rootPkField)
							->limit(false)
							->firstOrderBy(new OrderByField($rootPkField, $ids))
							->andWhereIn($rootPkField, $ids)
						;

						/** @var $childRecordParser RecordParser */
						$childRecordParser = $proxy->selectListFields($childQuery, $fieldPrefix . $serverField . '->');
						$childRecordParser->setParentIdFieldName($rootPkField);

						$childSet = new RecordSet\OnePass($childRecordParser, $childQuery);

						$wrapperParser = new RecordParser\HasManyDataParser($rootPkField, $childSet);

						$parser->addFieldDataParser($serverField, $wrapperParser);
					}
				} else {
					$parser->addMapping($fqFieldName, $serverField);
					$query->select($fqFieldName);
					unset($requiredFields[$serverField]);
				}
			}
		}

		// Remaining required fields
		if ($requiredFields) {
			foreach ($requiredFields as $fieldName => $required) {
				$fqFieldName = $fieldPrefix . $fieldName;
				$query->select($fqFieldName);
				$parser->addMapping($fqFieldName, $fieldName);
			}
		}

		return $parser;
	}

	/**
	 * Gets the client names of the fields that can be expanded. This list will contains
	 * the name in dotted notation of the expandable fields of fields that are expanded.
	 *
	 * @return string[]
	 */
	public function getResponseExpandable() {
		$fields = array();
		foreach ($this->expandedFields->getResponseExpandable() as $rootField) {
			$fields[] = $rootField;
			if (isset($this->proxies[$rootField])) {
				$proxy = $this->proxies[$rootField];
				foreach ($proxy->getResponseExpandable() as $field) {
					$fields[] = "$rootField.$field";
				}
			}
		}
		return $fields;
	}

	/**
	 * Gets the client names of the fields that are expanded, including the name in dotted
	 * notation of expanded fields of expanded records.
	 *
	 * @return string[]
	 */
	public function getResponseExpanded() {
		$fields = array();
		foreach ($this->expandedFields->getResponseExpanded() as $rootField) {
			$fields[] = $rootField;
			if (isset($this->proxies[$rootField])) {
				$proxy = $this->proxies[$rootField];
				foreach ($proxy->getResponseExpanded() as $field) {
					$fields[] = "$rootField.$field";
				}
			}
		}
		return $fields;
	}

}
