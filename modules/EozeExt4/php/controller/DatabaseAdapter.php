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

namespace eoko\modules\EozeExt4\controller;

use IllegalStateException;
use RuntimeException;

/**
 * Adapter used by this module's controllers to interact with the database.
 *
 * @since 2013-04-18 10:31
 */
interface DatabaseAdapter {

	/**
	 * Begins a database transaction. Multiple calls to this method must stack, and the
	 * transaction shall be committed only when the {@link comitTransaction()} method
	 * has been called the same number of times.
	 *
	 * @throws RuntimeException if the driver is not able to begin a transaction.
	 */
	public function beginTransaction();

	/**
	 * Commits the current transaction. If {@link beginTransaction()} has been called multiple
	 * times, then the transaction shall actually be commited only when this method has been
	 * called the same number of times.
	 *
	 * @throws RuntimeException if the underlying driver is not able to commit the transaction.
	 * @throws IllegalStateException is called while no transaction has been begun.
	 */
	public function commitTransaction();

	/**
	 * Rollback the complete transaction stack.
	 *
	 * @throws RuntimeException if the underlying driver is not able to rollback the transaction.
	 */
	public function rollbackTransaction();
}
