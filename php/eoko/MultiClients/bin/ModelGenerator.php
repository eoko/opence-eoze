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

namespace eoko\MultiClients\bin;

use eoko\script\Script;
use eoko\cqlix\generator\Generator;
use eoko\database\Database;
use eoko\application\Bootstrap;

/**
 *
 * @category Opence
 * @package
 * @subpackage
 * @since 2013-02-18 07:04
 */
class ModelGenerator extends Generator {

	protected function run() {

		$this->modelNamespace = __NAMESPACE__ . '\\Model';

		$mcConfig = Bootstrap::getCurrent()->getMultiClient()->getConfig();

		if ($mcConfig === false) {
			throw new \RuntimeException('Multi client is not configured.');
		}
		if (!isset($mcConfig['database'])) {
			throw new \RuntimeException('Multi client database is not configured.');
		}

		// Use multi client database
		$previous = Database::setDefaultProxy($mcConfig['database']);

		// Use multi client model paths
		$this->paths->setPath('model', __DIR__ . '/../Model');

		parent::run();

		// Restore previous proxy
		Database::setDefaultProxy($previous);
	}
}
