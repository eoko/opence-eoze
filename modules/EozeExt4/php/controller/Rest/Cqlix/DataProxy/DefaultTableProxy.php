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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix\DataProxy;

use ModelTable;
use Zend\Filter\Word\UnderscoreToCamelCase;
use eoko\modules\EozeExt4\Exception;

/**
 * This table proxy generates a sensible default configuration for itself by inspecting the
 * associated {@link ModelTable}'s fields.
 *
 * This implementation exposes all the first level fields of a given {@link ModelTable} to the client.
 * The field names are converted from underscore notation to camel case.
 *
 * @since 2013-04-25 20:22
 */
class DefaultTableProxy extends TableProxy {

	/**
	 * Creates a new DefaultTableProxy.
	 *
	 * @param array|ModelTable $config
	 * @throws \eoko\modules\EozeExt4\Exception\InvalidArgument
	 */
	public function __construct($config) {

		if ($config instanceof \ModelTable) {
			$table = $config;
		} else if (is_array($config)) {
			if (isset($config['table'])) {
				$table = $config['table'];
			} else {
				throw new Exception\InvalidArgument('Table name in config object is required.');
			}
		} else {
			throw new Exception\InvalidArgument();
		}

		parent::__construct($table, $this->getMappingConfig($table));
	}

	/**
	 * Creates the default field mapping configuration for the given table.
	 *
	 * @param ModelTable $table
	 * @return array
	 */
	private function getMappingConfig(ModelTable $table) {
		$mapping = array();
		$filter = $this->getServerToClientFieldFilter();
		foreach ($table->getColumns() as $column) {
			$serverFieldName = $column->getName();
			$clientFieldName = $filter($serverFieldName);
			$mapping[$clientFieldName] = $serverFieldName;
		}
		return $mapping;
	}

	/**
	 * Returns the function that will be used to convert server field names to client ones.
	 *
	 * @return callable
	 */
	private function getServerToClientFieldFilter() {
		$filter = new UnderscoreToCamelCase();
		return function($value) use($filter) {
			return lcfirst($filter($value));
		};
	}
}
