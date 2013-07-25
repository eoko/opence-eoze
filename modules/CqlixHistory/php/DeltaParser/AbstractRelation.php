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

namespace eoko\modules\CqlixHistory\DeltaParser;

use Model;
use ModelTable;
use ModelRelationInfo;
use HistoryEntryModifiedRelation;
use eoko\modules\CqlixHistory\DeltaRecordInterface;

/**
 * Base implementation for relation delta parsers.
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage DeltaParser
 * @since 2013-04-03 10:10
 */
abstract class AbstractRelation extends SingleFieldParser {

	/**
	 * @var callback
	 */
	private $idReader;
	/**
	 * @var callback
	 */
	private $nameReader;

	/**
	 * @inheritdoc
	 *
	 * Triggers the loading of the tracked model copy's relations from the database (because that won't
	 * be possible anymore after the model has been saved).
	 */
	public function initOriginalModel(Model $model) {
		parent::initOriginalModel($model);

		// preload the relation target
		$model->getRelation($this->getRelationName())->get();
	}

	/**
	 * Configure the relation name.
	 *
	 * @param $relationName
	 * @return $this
	 */
	public function setRelationName($relationName) {
		return $this->setFieldName($relationName);
	}

	/**
	 * Get the configured relation name.
	 *
	 * @return string
	 */
	protected function getRelationName() {
		return $this->getFieldName();
	}

	/**
	 * Configure a custom target model id reader.
	 *
	 * The function will be called with the relation target model as argument and must return
	 * a string.
	 *
	 * @param callback $readerCallback
	 * @return $this
	 */
	public function setIdReader($readerCallback) {
		$this->idReader = $readerCallback;
		return $this;
	}

	/**
	 * Configure a custom target model friendly name reader.
	 *
	 * The function will be called with the relation target model as argument and must return
	 * a string, or null if it was not able to find an apt friendly name.
	 *
	 * @param callback $readerCallback
	 * @return $this
	 */
	public function setNameReader($readerCallback) {
		$this->nameReader = $readerCallback;
		return $this;
	}

	/**
	 * Helper method for extracting the ID of the specified relation target model.
	 *
	 * @param Model $model
	 * @return mixed
	 */
	protected function extractModelId(Model $model) {
		 if ($this->idReader) {
			 return call_user_func($this->idReader, $model);
		 } else {
			 return $model->getPrimaryKeyValue();
		 }
	}

	/**
	 * Helper method for extracting the friendly name of the specified relation target model.
	 *
	 * @param Model $model
	 * @return string|null
	 */
	protected function extractModelFriendlyName(Model $model = null) {
		if ($model && $this->nameReader) {
			return call_user_func($this->nameReader, $model);
		} else {
			return null;
		}
	}

	/**
	 * Creates the delta record for the passed relation target model.
	 *
	 * @param Model $model
	 * @param string $modification
	 * @return DeltaRecordInterface
	 */
	protected function createDeltaRecordForModel(Model $model, $modification) {
		return $this->createDeltaRecord(array(
			'XRelation' => array(
				'modification' => $modification,
				'target_id' => $this->extractModelId($model),
				'target_display_name' => $this->extractModelFriendlyName($model),
			),
		));
	}
}
