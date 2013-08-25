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

use eoko\modules\EozeExt4\controller\Rest\Cqlix\Exception;
use Model;
use ModelTable;

/**
 * Class to work with records in Cqlix rest controllers.
 *
 * This class replaces the heavy {@link Model} class in order to allow fast data access in most
 * reading use cases (which is critical for record listing).
 *
 * @since 2013-04-25 18:11
 */
class Record {

	/**
	 * @var ModelTable
	 */
	private $table;

	/**
	 * @var array
	 */
	private $context;

	/**
	 * @var array
	 */
	private $data = array();

	/**
	 * Instance of the represented model. Will be `null` until the model has been loaded.
	 *
	 * @var Model|null
	 */
	private $model = null;

	/**
	 * Cache for table's fields.
	 *
	 * @var \ModelField[]
	 */
	private $tableFields = null;

	/**
	 * Value of the identifier of this record's parent record. If this record has no parent (i.e. is not
	 * a child record), then this property will be `null`.
	 *
	 * @var mixed
	 */
	private $parentId = null;

	/**
	 * Creates a new Record object.
	 *
	 * @param ModelTable $table
	 * @param array $context
	 * @param array|null $data
	 */
	public function __construct(ModelTable $table, array $context = null, $data = null) {
		$this->table = $table;
		$this->context = $context;
		$this->data = $data;
	}

	/**
	 * Debug friendly toString method.
	 *
	 * @return string
	 */
	public function __toString() {
		$modelName = $this->table->getModelName();
		return "Record > $modelName"
			. (isset($this->parentId) ? ' @' . $this->parentId : '')
			. "\t" . json_encode($this->data);
	}

	/**
	 * Creates a new Record from the passed model.
	 *
	 * @param Model $model
	 * @return Record
	 */
	public static function fromModel(Model $model) {
		$record = new Record($model->getTable(), $model->context);
		$record->model = $model;
		return $record;
	}

	/**
	 * Gets the underlying model represented by this record. If the record has not been created
	 * from a model, then the model will be loaded from the database.
	 *
	 * @return Model
	 */
	public function getModel() {
		if (!$this->model) {
			$id = $this->data[$this->table->getPrimaryKeyName()];
			return $this->table->loadModel($id, $this->context);
		}
		return $this->model;
	}

	/**
	 * Sets the data of this record.
	 *
	 * @param array $data
	 */
	public function setData(array $data) {
		$this->data = $data;
	}

	/**
	 * Gets the whole data array of this record.
	 *
	 * @return array|null
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Gets the field definition for the specified field name.
	 *
	 * @param string $fieldName
	 * @return \ModelField
	 */
	private function getField($fieldName) {
		if (!isset($this->tableFields[$fieldName])) {
			$field = $this->table->getField($fieldName);
			$this->tableFields[$fieldName] = $field;
		}
		return $this->tableFields[$fieldName];
	}

	/**
	 * Gets the value of the specified field.
	 *
	 * @param string $fieldName
	 * @throws Exception\UnknownField
	 * @return array|Record|mixed|null
	 */
	public function get($fieldName) {
		$field = $this->getField($fieldName);
		if ($this->model) {
			if ($field instanceof \ModelRelationInfo) {
				$model = $this->model->__get($fieldName);
				if ($model === null) {
					return null;
				} else if ($model instanceof \Model) {
					return self::fromModel($model);
				} else if ($model instanceof \ModelSet) {
					$records = array();
					foreach ($model as $item) {
						$records[] = self::fromModel($item);
					}
					return $records;
				} else {
					return self::fromModel($model);
				}
			} else {
				return $this->model->__get($fieldName);
			}
		} else if (array_key_exists($fieldName, $this->data)) {
			if ($field instanceof \ModelRelationInfo) {
				return $this->data[$fieldName];
			} else {
				$value = $this->data[$fieldName];
				return $field->castValue($value);
			}
		} else {
			throw new Exception\UnknownField($fieldName);
		}
	}

	/**
	 * Returns the context of the model.
	 *
	 * @return array
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Gets the table for the associated model class.
	 *
	 * @return ModelTable
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * Sets the value of the identifier of this record's parent record. Used for representing relations
	 * between records.
	 *
	 * @param mixed $parentId
	 * @return $this
	 */
	public function setParentId($parentId) {
		$this->parentId = $parentId;
		return $this;
	}

	/**
	 * Gets the value of this record's parent record identifier, or `null` if this record is not a child
	 * record. Used to represent relations between records.
	 *
	 * @return mixed|null
	 */
	public function getParentId() {
		return $this->parentId;
	}
}
