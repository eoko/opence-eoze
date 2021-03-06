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

namespace eoko\MultiClient\bin;

use eoko\script\Script;

use eoko\MultiClient\Model\User;
use eoko\MultiClient\Model\Client;

/**
 * Test (debug) class for {@link ModelGenerator}.
 *
 * @category Eoze
 * @package MultiClient
 * @subpackage bin
 * @since 2013-02-18 09:14
 */
class ModelGeneratorTest extends Script {

	protected function run() {
		$client = Client::create(array(
			'name' => 'Test3',
			'home_directory' => '/dev/null',
			'database_name' => 'tests',
		));

		$user = User::create(array(
			'username' => 'test-user-2',
			'password' => 'x',
			'level' => 10,
		));

		$user->setClient($client);

		$user->save();
	}
}
