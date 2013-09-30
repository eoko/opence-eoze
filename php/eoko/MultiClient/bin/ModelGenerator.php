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
use eoko\cqlix\generator\Generator;
use eoko\database\Database;
use eoko\application\Bootstrap;
use eoko\MultiClient\MultiClient;

/**
 * Model generator for multi client installations.
 *
 * @category Eoze
 * @package MultiClient
 * @subpackage bin
 * @since 2013-02-18 07:04
 */
class ModelGenerator extends Generator {

	protected $modelCategory = 'Eoze';

	protected $databaseProxyName = MultiClient::DATABASE_PROXY_NAME;

	protected $modelNamespace = 'eoko\MultiClient\Model';

	protected $namespaces = array(
		'model' => 'eoko\MultiClient\Model',
		'modelBase' => ':model\Base',
		'table' => ':model',
		'tableBase' => ':table\Base',
		'proxy' => ':proxy\Proxy',
	);

	public function __construct() {

		$this->database = new Database($this->databaseProxyName);

		// Use multi client model paths
		$this->paths['model'] = __DIR__ . '/../Model';

		$this->namespaces['model'] = substr(__NAMESPACE__, 0, -4);

		parent::__construct();
	}

	protected function run() {

		// Use multi client database
		$previous = Database::setDefaultProxy(MultiClient::DATABASE_PROXY_NAME);

		parent::run();

		// Restore previous proxy
		Database::setDefaultProxy($previous);
	}
}
