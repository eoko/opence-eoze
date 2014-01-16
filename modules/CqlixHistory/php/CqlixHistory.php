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

use HistoryEntryLot;
use Router;
use Zend\Di\Di;
use Zend\ServiceManager\ServiceManager;
use eoko\config\Application;

/**
 * Container for resources shared by all classes of CqlixHistory module.
 *
 * Ideally, this class should probably use DI or services facilities from ZF...
 *
 * @category Eoze
 * @package CqlixHistory
 * @since 2013-04-03 12:23
 */
class CqlixHistory {

	private static $instance;

	/**
	 * @var DeltaParser\Factory
	 */
	private $deltaParserFactory;

	/**
	 * @var HistoryEntryLot
	 */
	private $entryLotRecord = null;

	/**
	 * @return CqlixHistory
	 */
	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new static;
		}
		return self::$instance;
	}

	/**
	 * @return DeltaParser\Factory
	 */
	public function getDeltaParserFactory() {
		if (!$this->deltaParserFactory) {
			$this->deltaParserFactory = new DeltaParser\Factory;
		}
		return $this->deltaParserFactory;
	}

	/**
	 * The Lot record gathers all entries resulting from one same request (hence, same user and same time).
	 *
	 * @return HistoryEntryLot
	 */
	public function getEntryLotRecord() {
		if (!$this->entryLotRecord) {
			$this->entryLotRecord = $this->createEntryLotRecord();
		}
		return $this->entryLotRecord;
	}

	/**
	 * @return HistoryEntryLot
	 */
	protected function createEntryLotRecord() {
		return HistoryEntryLot::create(array(
			'user_id' => $this->getUserId(),
			'datetime' =>  $this->getDateTime(),
		));
	}

	/**
	 * Extracts the active user's id from the application context.
	 *
	 * @return int|null
	 */
	protected function getUserId() {
		return Application::getInstance()->getActiveUserId();
	}

	/**
	 * Gets the current time (to be used in history entry).
	 *
	 * @return string
	 */
	protected function getDateTime() {
		return date('Y-m-d H:i:s', Router::getActionTimestamp());
	}
}
