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

namespace eoko\cqlix\generator;

use eoko\util\NamespaceResolver;

/**
 * Generated model class lookup.
 *
 * @category Eoze
 * @package cqlix
 * @subpackage generator
 * @since 2013-02-19 18:29
 */
class ClassLookup extends NamespaceResolver {

	private $baseSuffix = 'Base';
	private $tableSuffix = 'Table';

	public function __construct($modelNamespace = null) {

		parent::__construct(array(
			'model' => '\\' . ltrim($modelNamespace, '\\'),
		));

		$this->setPaths(array(
			'modelBase' => ':model\Base',
			'table' => ':model',
			'tableBase' => ':table\Base',
			'proxy' => ':model\Proxy',
		));
	}

	private function applyNamespace($namespaceName, $className, $namespaced) {
		if ($namespaced) {
			return $this->resolve(":$namespaceName\\$className");
		} else {
			return $className;
		}
	}

	public function tableFromDb($dbTableName, $namespaced = true) {
		$className = NameMaker::tableFromDB($dbTableName);
		$namespace = 'table';
		return $this->applyNamespace($namespace, $className, $namespaced);
	}

	public function tableBaseFromDb($tableName, $namespaced = true) {
		$table = $this->tableFromDb($tableName, false);
		return $this->tableBaseForTable($table, $namespaced);
	}

	public function modelFromDb($dbTableName, $namespaced = true) {
		$table = $this->tableFromDb($dbTableName, false);
		return $this->modelForTable($table, $namespaced);
	}

	public function modelBaseFromDb($dbTableName, $namespaced = true) {
		$table = $this->tableFromDb($dbTableName, false);
		return $this->modelBaseForTable($table, $namespaced);
	}

	public function proxyFromDb($dbTableName, $namespaced = true) {
		$table = $this->tableFromDb($dbTableName, false);
		return $this->proxyForTable($table, $namespaced);
	}

	public function modelForTable($table, $namespaced = true) {
		$className = NameMaker::modelFromTable($table);
		$namespace = 'model';
		return $this->applyNamespace($namespace, $className, $namespaced);
	}

	public function proxyForTable($tableName, $namespaced = true) {
		$className = $tableName . 'Proxy';
		$namespace = 'proxy';
		return $this->applyNamespace($namespace, $className, $namespaced);
	}

	public function modelBaseForTable($tableName, $namespaced = true) {
		$className = $this->modelForTable($tableName, false) . $this->baseSuffix;
		$namespace = 'modelBase';
		return $this->applyNamespace($namespace, $className, $namespaced);
	}

	private function tableBaseForTable($table, $namespaced = true) {
		$className = $this->modelForTable($table, false) . $this->tableSuffix . $this->baseSuffix;
		$namespace = 'tableBase';
		return $this->applyNamespace($namespace, $className, $namespaced);
	}
}
