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

namespace eoko\modules\CqlixHistory\DeltaParser\Fields\Selector;

use ModelColumn;
use ModelTable;

/**
 * Default field selection behaviour.
 *
 * This class can be configured with a field inclusion list, or it will use the following logic
 * to select fields: take all the base columns of the associated model that are not the primary key,
 * foreign keys, or automatic fields (on edition).
 *
 * @category Eoze
 * @package CqlixHistory
 * @subpackage DeltaParser
 * @since 2013-04-03 11:45
 */
class DefaultColumns extends AbstractSelector {

	protected static $configAliases = array(
		'include' => 'setIncludedFields',
		'exclude' => 'setExcludedFields',
//		'excludePrimaryField' => 'setExcludePrimary',
//		'excludePrimaryField' => 'setExcludePrimary',
	);

	private $excludedFieldsMap = null;

	private $includedFieldsMap = null;

	private $excludeForeignKeys = true;

	private $excludePrimary = true;

	private $excludeAutoFields = true;

	/**
	 * Configure field white list.
	 *
	 * @param string[] $excludedFields
	 * @return $this
	 */
	public function setExcludedFields(array $excludedFields) {
		$this->excludedFieldsMap = array();
		foreach ($excludedFields as $field) {
			$this->excludedFieldsMap[$field] = true;
		}
		return $this;
	}

	/**
	 * Configure field black list.
	 *
	 * @param string[] $includedFields
	 * @return $this
	 */
	public function setIncludedFields(array $includedFields) {
		$this->includedFieldsMap = array();
		foreach ($includedFields as $field) {
			$this->includedFieldsMap[$field] = true;
		}
		return $this;
	}

	/**
	 * Configure exclusion of foreign key fields.
	 *
	 * @param bool $excludeForeignKeys
	 * @return $this
	 */
	public function setExcludeForeignKeys($excludeForeignKeys) {
		$this->excludeAutoFields = $excludeForeignKeys;
		return $this;
	}

	/**
	 * Configure exclusion of auto fields.
	 *
	 * @param bool $excludeAutoFields
	 * @return $this
	 */
	public function setExcludeAutoFields($excludeAutoFields) {
		$this->excludeAutoFields = $excludeAutoFields;
		return $this;
	}

	/**
	 * Configure exclusion of primary field.
	 *
	 * @param bool $excludePrimary
	 * @return $this
	 */
	public function setExcludePrimary($excludePrimary) {
		$this->excludePrimary = $excludePrimary;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getTrackedFieldNames(ModelTable $table) {
		$fields = array();
		foreach ($table->getColumns() as $column) {
			if ($this->isIncluded($column)) {
				$fields[] = $column->getName();
			}
		}
		return $fields;
	}

	/**
	 * Returns true if the passed column must be included in the delta computation.
	 *
	 * @param ModelColumn $column
	 * @return bool
	 */
	protected function isIncluded(ModelColumn $column) {
		if ($this->includedFieldsMap !== null) {
			return isset($this->includedFieldsMap[$column]);
		} else {
			$fieldName = $column->getName();
			return !isset($this->excludedFieldsMap[$fieldName])
				&& (!$this->excludeForeignKeys || !$column->isForeignKey())
				&& (!$this->excludePrimary || !$column->isPrimary())
				&& (!$this->excludeAutoFields || !$column->isAuto(ModelColumn::OP_UPDATE));
		}
	}
}
