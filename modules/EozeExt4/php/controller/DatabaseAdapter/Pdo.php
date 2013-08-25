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

namespace eoko\modules\EozeExt4\controller\DatabaseAdapter;

use IllegalStateException;
use eoko\modules\EozeExt4\controller\DatabaseAdapter as AdapterInterface;
use PDO as Connection;
use RuntimeException;

/**
 * PDO database adapter.
 *
 * @since 2013-04-18 10:39
 */
class Pdo implements AdapterInterface {

	/**
	 * @var \PDO
	 */
	private $pdo;

	/**
	 * @var int
	 */
	private $transactionCounter = 0;

	/**
	 * Creates a new PDO database adapter.
	 *
	 * @param \PDO $pdo
	 */
	public function __construct(Connection $pdo) {
		$this->pdo = $pdo;
	}

	/**
	 * @inheritdoc
	 */
	public function beginTransaction() {
		if ($this->transactionCounter++ === 0) {
			if (!$this->pdo->beginTransaction()) {
				throw new RuntimeException('Failed to begin data transaction.');
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function commitTransaction() {
		if (--$this->transactionCounter === 0) {
			if (!$this->pdo->commit()) {
				throw new RuntimeException('Failed to commit data transaction.');
			}
		}
		if ($this->transactionCounter < 0) {
			throw new IllegalStateException('beginTransaction has not been called.');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function rollbackTransaction() {

		$this->transactionCounter = 0;

		if (!$this->pdo->rollBack()) {
			throw new RuntimeException('Failed to rollback data transaction.');
		}
	}
}
