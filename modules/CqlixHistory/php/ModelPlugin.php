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

namespace eoko\modules\CqlixHistory;

use Model;
use ModelRelation;
use ModelTable;
use Zend\EventManager\Event;
use eoko\modules\CqlixHistory\Entry\AbstractEntry;

/**
 * This plugin will monitor events from one target record and handle related history logic.
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage Plugin
 * @since 2013-04-02 12:50
 */
class ModelPlugin {

	/**
	 * @var ModelPluginContext
	 */
	private $context;

	/**
	 * @var \Model
	 */
	private $model;

	/**
	 * @var AbstractEntry
	 */
	private $entry;

	/**
	 * Attaches a new plugin instance to the specified model.
	 *
	 * @param Model $model
	 * @param ModelPluginContext $context
	 * @return ModelPlugin
	 */
	public static function attach(Model $model, ModelPluginContext $context) {
		return new self($model, $context);
	}

	public function __construct(Model $model, ModelPluginContext $context) {

		$this->model = $model;
		$this->context = $context;

		$model->events
			->on(Model::EVT_BEFORE_SAVE, array($this, 'beforeSave'))
			->on(Model::EVT_AFTER_SAVE, array($this, 'afterSave'))

			->on(Model::EVT_BEFORE_DELETE, array($this, 'beforeDelete'))
			->on(Model::EVT_AFTER_DELETE, array($this, 'afterDelete'));
	}

	/**
	 * Handler for watched model "before save" event.
	 *
	 * @param Model $model
	 * @throws Exception\IllegalState
	 */
	public function beforeSave(Model $model) {

		if ($this->model !== $model) {
			throw new Exception\IllegalState('The event did not originate from the watched model.');
		}

		if ($this->entry !== null) {
			throw new Exception\IllegalState('History entry has already been created for this record instance.');
		}

		if ($model->isNew()) {
			$this->entry = new Entry\Creation($model, $this->context);
		} else {
			$this->entry = new Entry\Modification($model, $this->context);
		}
	}

	/**
	 * Handler for watched model "after save" event.
	 *
	 * @param Model $model
	 * @throws Exception\IllegalState If the corresponding entry has not been created (which should
	 * have been done in the "before save" event.
	 */
	public function afterSave(Model $model) {

		if ($this->model !== $model) {
			throw new Exception\IllegalState('The event did not originate from the watched model.');
		}

		if (!$this->entry) {
			throw new Exception\IllegalState;
		}

		$this->commit();
	}

	/**
	 * Handler for watched model "before delete" event.
	 *
	 * @param Model $model
	 * @throws Exception\IllegalState
	 */
	public function beforeDelete(Model $model) {

		if ($this->model !== $model) {
			throw new Exception\IllegalState('The event did not originate from the watched model.');
		}

		if ($this->entry !== null) {
			throw new Exception\IllegalState('History entry has already been created for this record instance.');
		}

		$this->entry = new Entry\Deletion($model, $this->context);
	}

	/**
	 * Handler for watched model "after delete" event.
	 *
	 * @param Model $model
	 * @throws Exception\IllegalState
	 * If the associated entry has not been created, or it is not an instance of a Deletion entry.
	 */
	public function afterDelete(Model $model) {

		if ($this->model !== $model) {
			throw new Exception\IllegalState('The event did not originate from the watched model.');
		}

		if (!($this->entry instanceof Entry\Deletion)) {
			throw new Exception\IllegalState('A deletion entry should have been created.');
		}

		$this->commit();
	}

	/**
	 * Commit the associated entry, and reset it to null (so that further save/delete events can be handled).
	 */
	private function commit() {
		$this->entry->commit();
		$this->entry = null;
	}
}
