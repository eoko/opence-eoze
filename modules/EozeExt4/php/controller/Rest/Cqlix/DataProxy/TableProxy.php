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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy;

use eoko\cqlix\Query\OrderByField;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy\TableProxy\ExpandedFields;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\RecordSet\FieldValueReader\HasManyFieldReader;
use Model;
use ModelTable;
use ModelTableQuery;
use Query;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Record;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\RecordSet\RecordParser;
use eoko\modules\EozeExt4\Exception;
use eoko\modules\EozeExt4\Exception\InvalidArgument as InvalidArgumentException;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Exception\UnknownField as UnknownFieldException;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy\TableProxy\IdQueryCache;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\RecordSet;
use eoko\modules\EozeExt4\controller\Rest\Cqlix\Request\Params as RequestParams;

/**
 * A complete {@link DataProxy} implementation that is bound to a Cqlix's {@link ModelTable}, and uses
 * user configuration to determine which fields are exposed.
 *
 *
 * Data contexts
 * =============
 *
 * Query, models, etc., read/write contexts: see {@link AbstractProxy}'s class documentation.
 *
 *
 * Configuration
 * =============
 *
 * Reader
 * ------
 *
 * `reader` callback
 *
 * A function that will be passed a {@link Record} as its first argument, and must return the value
 * of the field.
 *
 * Required fields
 * ---------------
 *
 * `requires` string[]
 *
 * List of fields names (server names) that are required to be populated in the supplied Record (for use by the
 * reader).
 *
 * Writer
 * ------
 *
 * `writer` callback|false
 *
 * A function that will be tasked with with setting the field value of the model during update operation. The
 * proxy will entirely delegate the field writing to this function (i.e. it won't affect the model at all by
 * itself). The function will be called with the following parameters:
 *
 * - `$model` Model The model to update
 * - `$value` mixed The value to affect to the field
 *
 * `writer` can also be set to `false`, then the proxy will skip writing the field value into the model entirely.
 *
 * Read-only fields
 * ----------------
 *
 * `readOnly` bool
 *
 * If true, the field value cannot be set. An error will be raised is some data is passed
 * for this field.
 *
 * Update post processing
 * ----------------------
 *
 * `afterUpdate` callback
 *
 * If present, this function will be called after update operations on a model, after all the update have been
 * applied. The function will be called even if the input data contains no value for the field.
 *
 * The function will be called with the following parameters:
 *
 * - `$model` Model The model that have just been updated
 * - `$value` mixed The value of the client field in the input data
 * - `$inputData` array The whole input data array
 *
 *
 * @since 2013-05-22 16:48
 */
class TableProxy extends AbstractProxy {

	/**
	 * Name of the proxy class to be used for relations field for which no proxy is specified.
	 *
	 * @var string
	 */
	private static $defaultProxyClass = 'eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy\DefaultTableProxy';

	/**
	 * Associative array in which keys are the names of the fields that are explicitly required (in
	 * the configuration of the proxy) to be loaded in the list query.
	 *
	 * E.g.
	 *
	 *     array(
	 *         'fieldName1' => true,
	 *         'fieldName2' => true,
	 *     )
	 *
	 * @var array
	 */
	private $requiredListFieldsMap = null;

	/**
	 * Expanded fields helper.
	 *
	 * @var ExpandedFields
	 */
	private $expandedFields;

	/**
	 * Associative array that maps client field names to server field names. Client fields that
	 * are not associated to a server fields are not represented in this map.
	 *
	 * @var string[]
	 */
	private $clientToServerMap;
	/**
	 * Associative array that maps server field names to server field names.
	 * 
	 * @var string[]
	 */
	private $serverToClientMap;

	/**
	 * Associative array in which indexes are the client name of this proxy's fields, and the
	 * values are the fields configuration. All client fields are represented in this array,
	 * even if they don't have explicit configuration (in this case, the value will be `null`).
	 * 
	 * E.g.
	 * 
	 *     array(
	 *         'fieldClientName1' => $field1ConfigArray,
	 *         'fieldClientName2' => $field2ConfigArray,
	 *     )
	 * 
	 * @var array[]
	 */
	private $clientFieldsConfig;

	/**
	 * @var string[]
	 */
	private $expandedClientFieldsCache;

	/**
	 * Cache for proxies of this proxy's fields.
	 *
	 * @var DataProxy[]
	 */
	private $proxies;

	/**
	 * Working model table.
	 *
	 * @var \ModelTable
	 */
	private $table;

	/**
	 * Configuration of this proxy's field mapping.
	 *
	 * @var array
	 */
	protected $mapping;

	/**
	 * Creates a new {@link TableProxy} instance.
	 *
	 * @param ModelTable $table
	 * @param array $mappingConfig
	 * @throws InvalidArgumentException
	 */
	public function __construct(ModelTable $table, array $mappingConfig) {
		// Table
		$this->table = $table;
		// Mapping
		$this->createFieldMaps($mappingConfig);
	}

	/**
	 * @inheritdoc
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * Initializes this object with the passed field mapping configuration array.
	 *
	 * @param array $clientToServerFieldMap
	 * @throws \eoko\modules\EozeExt4\Exception\Domain
	 */
	private function createFieldMaps(array $clientToServerFieldMap) {

		$table = $this->getTable();

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
			if (isset($config['reader'])) {
				$reader = $config['reader'];

				if (is_string($reader) && substr($reader, 0, 2) === '::') {
					$reader = array(
						get_class($this),
						substr($reader, 2),
					);
					$config['reader'] = $reader;
				}

				// Option readFromModel
				if (!isset($config['readFromModel'])) {
					if (is_array($reader)) {
						$class = new \ReflectionClass($reader[0]);
						$function = $class->getMethod($reader[1]);
					} else {
						/** @var $reader \Closure */
						$function = new \ReflectionFunction($reader);
					}
					$params = $function->getParameters();
					if (count($params) > 0) {
						$recordClass = $params[0]->getClass()->getName();
						$config['readFromModel'] = is_subclass_of($recordClass, 'Model');
					} else {
						$config['readFromModel'] = false;
					}
				}
			}

			if (isset($config['requires'])) {
				if (is_string($config['requires'])) {
					$requires = explode(',', $config['requires']);
					foreach ($requires as &$field) {
						$field = trim($field);
					}
					unset($field);
				} else {
					$requires = $config['requires'];
				}
				foreach ($requires as $requiredField) {
					$this->requiredListFieldsMap[$requiredField] = true;
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

				$field = $table->getField($serverField);
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
	}

	protected function serverNameFromClient($name) {
		return \Inflector::camelCaseToUnderscored($name);
	}

	/**
	 * Gets the names of the expanded client fields, that is both non-expandable fields, and explicitly
	 * or implicitly expanded fields.
	 * 
	 * @return string[]
	 */
	private function getExpandedClientFieldNames() {
		return $this->expandedClientFieldsCache;
	}

	/**
	 * @inheritdoc
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
	 * Gets the server model field name for the specified client field *local to this proxy* (i.e. dotted
	 * notation in field name won't be expanded).
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
	 * @inheritdoc
	 */
	public function setExpandedParams($expandParam, $expandDefault) {

		$expandedFields = new ExpandedFields($expandDefault);
		$expandedFields->setExpandParam($expandParam);

		return $this->setExpandedFields($expandedFields);
	}

	/**
	 * @inheritdoc
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
		$this->expandedClientFieldsCache = $expandedFields->getExpandedClientFields($fieldsConfig);

		return $this;
	}

	/**
	 * Gets the {@link DataProxy} for the field specified by its client name.
	 *
	 * Returns `null` if no proxy is configured for the specified field.
	 *
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

	/**
	 * Creates the {@link DataProxy} for this proxy's field specified by its client name.
	 *
	 * Raises an exception if the proxy config cannot be parsed.
	 *
	 * @param string|array $classOrConfig
	 * @param string $clientFieldName
	 * @return DataProxy
	 * @throws \eoko\modules\EozeExt4\Exception\Domain
	 */
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
		$childExpandFields = $this->expandedFields->getChildExpandedFields($clientFieldName);
		$proxy->setExpandedFields($childExpandFields);

		// Return
		return $proxy;
	}

	/**
	 * @inheritdoc
	 */
	public function getIdFromData(array $data, $require = true) {

		$pkName = $this->getTable()->getPrimaryKeyName();
		$map = $this->clientToServerMap;

		if (isset($map[$pkName])) {
			$clientPkName = $map[$pkName];
			if (isset($data[$clientPkName])) {
				return $data[$clientPkName];
			} else if ($require) {
				throw new Exception\InvalidArgument('Provided data does not contain id.');
			} else {
				return null;
			}
		} else {
			throw new Exception\IllegalState('Cannot resolve id field server-side name.');
		}
	}

	private function writeField(Model $model, $clientFieldName, $value) {
		$fieldName = $this->clientToServer($clientFieldName);
		$config = $this->clientFieldsConfig[$clientFieldName];

		if ($config) {
			if (!empty($config['proxy'])) {
				$proxy = $this->getProxy($clientFieldName);

				if (empty($value)) {
					$model->setField($fieldName, $value, true);
				} else {
					$field = $model->getTable()->getField($fieldName);
					$associatedTable = $proxy->getTable();

					if ($field instanceof \ModelRelationInfoHasOne) {
						// 2013-07-22 11:44
						//
						// Instead of creating an empty record, we're trying to use an
						// existing linked model. Then, only if there is no linked model
						// do we create a new one and link it to the main model.
						//
						// Prior to this modification, the whole code was identical to
						// the one in the else alternative bellow.

						//$associatedModel = $associatedTable->createModel(null, false, $model->context);
						$associatedModel = $model->getField($fieldName);

						if ($associatedModel) {
							// Update
							$proxy->setRecordData($associatedModel, $value);
						} else {
							// Create
							$associatedModel = $associatedTable->createModel(null, false, $model->context);
							// Update
							$proxy->setRecordData($associatedModel, $value);
							// Assign
							$model->setField($fieldName, $associatedModel);
						}
					} else if ($field instanceof \ModelRelationInfoHasMany) {
						$associatedModels = array();
						foreach ($value as $data) {
							$associatedModel = $associatedTable->createModel(null, false, $model->context);
							$proxy->setRecordData($associatedModel, $value);
							$associatedModels[] = $associatedModel;
						}
						$model->setField($fieldName, $associatedModels);
					} else {
						throw new Exception\IllegalState('Unexpected field type: ' . get_class($field));
					}
				}
			} else if (array_key_exists('writer', $config)) {
				if ($config['writer'] !== false) {
					call_user_func($config['writer'], $model, $value);
				}
			} else if ($fieldName) {
				$model->setField($fieldName, $value, true);
			}
		} else if ($fieldName) {
			// allowing null values, that will be checked later, before save
			$model->setField($fieldName, $value, true);
		}
	}

	/**
	 * Reads the value of the field specified by its client name from the passed record.
	 *
	 * @param Record $record
	 * @param string $clientFieldName
	 * @return mixed
	 */
	private function readField(Record $record, $clientFieldName) {

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

	/**
	 * @inheritdoc
	 */
	public function getMetaData() {
		$fields = array();
		foreach ($this->getExpandedClientFieldNames() as $field) {
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

	/**
	 * Gets meta data array (see {@link DataProxy::getMetaData()}) for the field specified by
	 * its client name.
	 *
	 * @param string $clientFieldName
	 * @return array
	 */
	private function getFieldMetaData($clientFieldName) {

		$table = $this->getTable();

		$meta = array(
			'name' => $clientFieldName,
		);

		$serverFieldName = $this->getServerFieldName($clientFieldName);

		if ($serverFieldName !== null) {
			$serverField = $table->getField($serverFieldName);
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

//	/**
//	 * Writes the passed value to the field specified by its client name in the given record.
//	 *
//	 * @param Model $record
//	 * @param string $clientFieldName
//	 * @param mixed $value
//	 * @return Model
//	 */
//	public function writeField(Model $record, $clientFieldName, $value) {
//		$fieldName = $this->clientToServer($clientFieldName);
//		return $record->setField($fieldName, $value);
//	}

	/**
	 * Returns the name of the server field matching the specified client field name,
	 * or `null` if no server field is associated to the specified client field.
	 *
	 * @param string $field
	 * @return string|null
	 */
	private function clientToServer($field) {
		return isset($this->clientToServerMap[$field])
			? $this->clientToServerMap[$field]
			: null;
	}

	/**
	 * @inheritdoc
	 */
	public function selectListFields(Query $query, $fieldPrefix = '', RequestParams $request) {

		$table = $this->getTable();

		// The query is not necessarily based on the same table as this mapping, since this
		// method can be called for child proxies (that's also why we need the field prefix)
		if ($query instanceof ModelTableQuery) {
			$rootTable = $query->getTable();
		} else {
			throw new Exception\UnsupportedOperation();
		}

		// Creates the parser that maps selected fields in the query to the record proxy's
		// own fields.
		$parser = new RecordParser($table, $query->getContext());

		// The query will be limited either (1) by the initial limit if the working
		// query is the root query, or (2) the WHERE IN clause if the working query
		// is already a child of the root query.
		$idsCache = new IdQueryCache($query);

		$requiredFields = $this->requiredListFieldsMap;

		foreach ($this->getExpandedClientFieldNames() as $clientFieldName) {

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
						$this->mergeContextFromProxy($query, $proxy, $request, false);
						$childParser = $proxy->selectListFields($query, $fqFieldName . '->', $request);
						$parser->addFieldValueReader($serverField, $childParser);
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
							// we want all the child rows
							->limit(false)
							// returns child rows in the same order as parent ones
							->firstOrderBy(new OrderByField($rootPkField, $ids))
							->andWhereIn($rootPkField, $ids)
						;

						/** @var RecordParser $childRecordParser */
						$this->mergeContextFromProxy($query, $proxy, $request, false);
						$childRecordParser = $proxy->selectListFields($childQuery, $fieldPrefix . $serverField . '->', $request);
						$childRecordParser->setParentIdFieldName($rootPkField);

						$childSet = new RecordSet\OnePass($childRecordParser, $childQuery);

						$fieldReader = new HasManyFieldReader($rootPkField, $childSet);

						$parser->addFieldValueReader($serverField, $fieldReader);
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

	private function mergeContextFromProxy(Query $query, DataProxy $proxy, RequestParams $request, $allowOverride = false) {
		if ($proxy instanceof HasQueryContext) {
			$context =& $query->getContext();
			$newContext = $proxy->createContext($request);
			if ($newContext) {
				foreach ($newContext as $key => $value) {
					if ($context === null) {
						$context = array(
							$key => $value,
						);
					} else if (array_key_exists($key, $context)) {
						if (!$allowOverride && $newContext[$key] !== $context[$key]) {
							// TODO
							throw new \RuntimeException;
						}
					} else {
						$context[$key] = $value;
					}
				}
			}
		}
	}

	/**
	 * @inheritdoc
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
	 * @inheritdoc
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

	/**
	 * @inheritdoc
	 */
	public function getRecordData(Record $record) {
		$recordData = array();
		foreach ($this->getExpandedClientFieldNames() as $clientField) {
			$recordData[$clientField] = $this->readField($record, $clientField);
		}

		// MetaData
		$metaData = array(
			'$expanded' => $this->getResponseExpanded(),
		);

		return array_merge($metaData, $recordData);
	}

	private function cleanAntagonists(array $inputData) {

		$antagonists = array();
		foreach ($this->clientFieldsConfig as $clientFieldName => $config) {
			if (isset($config['foreignKey'])) {
				$antagonists[$clientFieldName] = $config['foreignKey'];
				$antagonists[$config['foreignKey']] = $clientFieldName;
			}
		}

		foreach ($antagonists as $left => $right) {
			if (isset($inputData[$left])) {
				if (isset($inputData[$right])) {
					throw new Exception\InvalidArgument;
				} else {
					unset($inputData[$right]);
				}
			} else if (isset($inputData[$right])) {
				unset($inputData[$left]);
			}
		}

		return $inputData;
	}

	/**
	 * @inheritdoc
	 */
	protected function doSetRecordData(Model $model, array $inputData) {

		$postProcessors = array();

		$inputData = $this->cleanAntagonists($inputData);

		// Preprocessors
		foreach ($this->clientFieldsConfig as $clientFieldName => $config) {
			if (isset($config['beforeUpdate'])) {
				$value = isset($inputData[$clientFieldName])
					? $inputData[$clientFieldName]
					: null;

				call_user_func($config['beforeUpdate'], $model, $value, $inputData);
			}
		}

		foreach ($this->clientFieldsConfig as $clientFieldName => $config) {
			$value = isset($inputData[$clientFieldName])
				? $inputData[$clientFieldName]
				: null;

			if (array_key_exists($clientFieldName, $inputData)) {
				if (empty($config['readOnly'])) {
					$this->writeField($model, $clientFieldName, $value);
				} else {
					throw new Exception\IllegalState('Read-only field: ' . $clientFieldName);
				}
			}

			if (isset($config['afterUpdate'])) {
				$postProcessors[] = array(
					'function' => $config['afterUpdate'],
					'value' => $value,
				);
			}
		}

		// Execute post processors
		foreach ($postProcessors as $processor) {
			call_user_func($processor['function'], $model, $processor['value'], $inputData);
		}
	}
}
