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
 * One-pass {@link ModelSet}. When used in only one loop, this model set is memory efficient
 * since it does not store data from the database in the memory of the PHP process.
 *
 * @since 2013-05-16 13:41 (Extracted from file ModelTable.php)
 */
class OnePassModelSet extends ModelSet {

	/**
	 * @var ModelTable
	 */
	protected $table;

	/**
	 * @var Query
	 */
	protected $query;
	/**
	 * @var PDOStatement
	 */
	protected $pdoStatement;

	protected $reciproqueFactory;

	private $i = null;

	/**
	 * The current record.
	 *
	 * @var Model
	 * @version 2013-05-01 Was protected, made private.
	 */
	private $current = null;

	public function __construct(ModelTableProxy $table, Query $query, ModelRelationReciproqueFactory $reciproqueFactory = null) {
		$this->query = $query;
		$table->attach($this->table);
		$this->reciproqueFactory = $reciproqueFactory;
	}

	protected $count = null;

	public function count() {
		if ($this->count !== null) {
			return $this->count;
		} else {
			return $this->count = $this->query->executeCount();
		}
	}

	/**
	 * @inheritdoc
	 */
	public function toArray() {
		$r = array();
		foreach($this as $record) $r[] = $record;
		return $r;
	}

	/**
	 * @return Model
	 */
	public function current() {
		return $this->current;
	}

	/**
	 * Creates the current Model with the given row of data read from the query.
	 *
	 * @param array $data
	 * @return Model
	 */
	protected function createRecord($data) {
		$model = $this->table->loadModelFromData($data, $this->query->context);

		if ($this->reciproqueFactory !== null) {
			$this->reciproqueFactory->init($model);
		}

		return $model;
	}

	/**
	 * Reads the next row of data from the query result, and update the
	 * {@link $current current record property}.
	 */
	private function updateCurrent() {
		$data = $this->pdoStatement->fetch(PDO::FETCH_ASSOC);
		if ($data !== false) {
			$this->current = $this->createRecord($data);
		} else {
			$this->finished = true;
		}
	}

	public function key() {
		return $this->i;
	}

	private $finished = false;

	public function next() {
		$this->i++;
		$this->updateCurrent();
	}

	public function rewind() {
		if ($this->i === null) {
			$this->pdoStatement = $this->query->executeSelectRaw();
		} else {
			$this->pdoStatement = $this->query->reExecuteSelectRaw();
		}
		$this->i = 0;
		$this->updateCurrent();
	}

	public function valid() {
		return !$this->finished;
	}
}
