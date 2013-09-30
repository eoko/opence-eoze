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

namespace eoko\modules\CqlixHistory\Entry;

use eoko\modules\CqlixHistory\Exception;
use History;
use Model;
use History as HistoryModel;
use HistoryEntry;
use Zend\Validator\File\Hash;
use eoko\modules\CqlixHistory\CqlixHistory;
use eoko\modules\CqlixHistory\Enum;
use eoko\modules\CqlixHistory\HasHistory;
use eoko\modules\CqlixHistory\ModelPlugin;
use eoko\modules\CqlixHistory\ModelPluginConfiguration;
use eoko\modules\CqlixHistory\ModelPluginContext;

/**
 * Base implementation for entries.
 *
 * Entries are created before a model instance is saved (beforeSave event). After the model has been saved,
 * the {@link commit()} method is called.
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage Entry
 * @since 2013-04-02 14:24
 */
abstract class AbstractEntry implements Enum {

	/**
	 * @var string
	 */
	protected $operation = null;

	/**
	 * @var Model
	 */
	protected $model;

	/**
	 * @var ModelPluginContext
	 */
	private $context;

	/**
	 * @var HistoryModel
	 */
	private $historyRecord;

	/**
	 * Creates a new entry object.
	 *
	 * @param Model $model
	 * @param ModelPluginContext $context
	 * @throws Exception\IllegalState
	 * @throws Exception\InvalidArgument
	 * If the passed model is not an instance of {@link History history record}.
	 */
	public function __construct(Model $model, ModelPluginContext $context) {

		if (!isset($this->operation)) {
			throw new Exception\IllegalState(
				get_class($this) . ' must set the value of its `operation` property.'
			);
		}

		if (!($model instanceof HasHistory)) {
			throw new Exception\InvalidArgument(
				get_class($model) . ' class must implements eoko\modules\CqlixHistory\HasHistory interface.'
			);
		}

		$this->model = $model;
		$this->context = $context;

		$this->getHistoryRecord();

		$this->init();
	}

	/**
	 * Initialization method. Provided for commodity, so implementing classes don't have to worry about
	 * the constructor arguments (and their possible evolution). The parent's method should still always
	 * be called.
	 */
	protected function init() {}

	/**
	 * This method is called after the monitored record has been saved. This is the time when the
	 * history entry should be {@link populateEntry populated}.
	 */
	public function commit() {

		$entry = $this->createEntryRecord();

		$save = $this->populateEntry($entry);

		if ($save !== false) {
			$entry->save();
		}
	}

	/**
	 * Populate the entry record.
	 *
	 * If this method returns `false`, then the entry record won't be persisted in the database.
	 *
	 * @param HistoryEntry $entry
	 * @return bool|void
	 */
	abstract protected function populateEntry(HistoryEntry $entry);

	/**
	 * Gets the associated context.
	 *
	 * @return ModelPluginContext
	 */
	protected function getContext() {
		return $this->context;
	}

	/**
	 * Gets the history record to which new entries should be attached. If the associated model doesn't
	 * have an history record yet, one will be created for it.
	 *
	 * @return History
	 */
	protected function getHistoryRecord() {
		if (!$this->model->isNew()) {
			/** @var HasHistory $model */
			$model = $this->model->getStoredCopy();

			// Using stored copy, in order to safeguard partial representations of
			// existing models.
			$this->historyRecord = $model->getHistory();
		}

		if (empty($this->historyRecord)) {
			$this->historyRecord = $this->createHistoryRecord();
		}

		return $this->historyRecord;
	}

	/**
	 * Creates the history record for the attached model.
	 *
	 * @return History
	 */
	protected function createHistoryRecord() {
		/** @var HasHistory $model */
		$model = $this->model;

		$history = HistoryModel::create();
		$history->save();

		$model->setHistoryId($history->getId());

		return $history;
	}

	/**
	 * Creates the history entry record represented by this object, that will be persisted in the
	 * database.
	 *
	 * @return HistoryEntry
	 */
	protected function createEntryRecord() {

		$lot = CqlixHistory::getInstance()->getEntryLotRecord();
		$modelName = $this->model->getTable()->getModelName();

		return HistoryEntry::create(array(
			'history_id' => $this->historyRecord->getId(),
			'Lot' => $lot,
			'model' => $modelName,
			'model_id' => $this->model->getPrimaryKeyValue(),
			'operation' => $this->operation,
		));
	}
}
