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
use eoko\modules\EozeExt4\Exception as ModuleException;

/**
 * A {@link TableProxy} with flexible configuration possibilities.
 *
 * The working table and field mapping of the proxy can be configured by overriding either
 * the corresponding properties, or methods.
 *
 * @since 2013-05-22 17:22
 */
class TemplateTableProxy extends TableProxy {

	/**
	 * Name or instance of the root table of this proxy.
	 *
	 * @var ModelTable|string
	 */
	protected $table = null;

	/**
	 * Configuration of the field mapping for this table proxy.
	 *
	 * @var array
	 */
	protected $mapping = null;

	/**
	 * Creates a new TemplateTableProxy instance.
	 *
	 * This constructor will inspect the $table and $mapping properties, as well as the
	 * {@link TemplateTableProxy::getTable()} and {@link TemplateTableProxy::getMappingConfig()}
	 * methods to get the arguments for the {@link TableProxy::__construct() parent constructor}.
	 */
	public function __construct() {

		// Table
		if ($this->table !== null) {
			$table = $this->table;
		} else {
			// Maybe the method has been overridden
			$table = $this->getTable();
		}
		if (is_string($table)) {
			$table = ModelTable::getTable($table);
		}

		// Mapping
		/** @var array $mapping */
		if ($this->mapping !== null) {
			$mapping = $this->mapping;
		} else {
			$mapping = $this->getMappingConfig();
		}

		parent::__construct($table, $mapping);
	}

	/**
	 * Gets the field mapping config for this proxy. This method can be used as an alternative
	 * to overriding the {@link TemplateTableProxy::mapping} property.
	 *
	 * @throws \eoko\modules\EozeExt4\Exception\Domain
	 * @return array
	 */
	protected function getMappingConfig() {
		throw new ModuleException\Domain('Missing mapping configuration.');
	}
}
