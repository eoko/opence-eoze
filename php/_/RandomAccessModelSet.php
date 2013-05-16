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

/**
 * Random access {@link ModelSet}.
 *
 * @since 2013-05-16 14:21 (Extracted from file ModelTable.php)
 */
class RandomAccessModelSet extends ModelSet implements ArrayAccess {

	/**
	 * @var Model[]
	 */
	protected $set;
	protected $context = null;

	public function __construct(ModelTableProxy $table, Query $query = null,
			ModelRelationReciproqueFactory $reciproqueFactory = null) {

		$this->set = array();

		if ($query !== null) {

			$this->context = $query->context;

			foreach ($query->executeSelect() as $results) {
				/** @var ModelTable $table */
				$this->set[] = $table->loadModelFromData($results, $this->context);
			}
		}

		if ($reciproqueFactory !== null) {
			foreach ($this->set as $model) {
				$reciproqueFactory->init($model);
			}
		}
	}

	public function groupBy($fieldName, $asc = true) {
		$r = array();
		foreach ($this as $model) {
			/** @var $model Model */
			$r[$model->__get($fieldName)][] = $model;
		}

		if ($asc) ksort($r);
		else krsort($r);

		return $r;
	}

	public function groupByBoolean($fieldName, $asc = true) {
		$r = array();
		foreach ($this as $model) {
			/** @var $model Model */
			$r[(bool) $model->__get($fieldName)][] = $model;
		}

		if ($asc) ksort($r);
		else krsort($r);

		return $r;
	}

	public function count() {
		return count($this->set);
	}

	public function size() {
		return count($this->set);
	}

	/**
	 * @inheritdoc
	 */
	public function toArray() {
		return $this->set;
	}

	public function getModelsData() {
		$r = array();
		foreach ($this->set as $model) {
			$r[] = $model->getData();
		}
		return $r;
	}

	public function push(Model $model) {
		$this->set[] = $model;
		if ($this->context !== null) $model->setContextIf($this->context);
	}

	public function pop() {
		return array_pop($this->set);
	}

	/**
	 * @param mixed $value	the value of the primary key of the model to remove,
	 * or a Model from which the id wil be taken. If the Model is new (hence,
	 * has no id, an IllegalArgumentException will be thrown.
	 * @return bool|\Model
	 * @throws IllegalArgumentException
	 */
	public function removeById($value) {
		if ($value instanceof Model) {
			if ($value->isNew()) throw new IllegalArgumentException(
				'It is impossible to remove by id a new model'
			);
			$value = $value->getPrimaryKeyValue();
		}
		foreach ($this->set as $i => $m) {
			if ($m->getPrimaryKeyValue() === $value) {
				unset($this->set[$i]);
				return $m;
			}
		}
		return false;
	}

	public function offsetExists($offset) {
		return array_key_exists($offset, $this->set);
	}

	/**
	 *
	 * @param int $offset
	 * @return Model
	 */
	public function offsetGet($offset) {
		return $this->set[$offset];
	}

	public function offsetSet($offset, $value) {
		throw new UnsupportedOperationException('Read Only');
	}

	public function offsetUnset($offset) {
		for ($i=$offset, $l=count($this->set)-1; $i<$l; $i++) {
			$this->set[$i] = $this->set[$i+1];
		}
		array_pop($this->set);
	}

	protected $i;

	public function key() {
		return $this->i;
	}

	public function next() {
		$this->i++;
	}

	public function rewind() {
		$this->i = 0;
	}

	public function valid() {
		return array_key_exists($this->i, $this->set);
	}

	/**
	 *
	 * @return Model
	 */
	public function current() {
		return $this->set[$this->i];
	}

}
