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

use ModelTable;

/**
 * @todo doc
 *
 * @since 2013-07-24 18:43
 */
class DefaultSelector extends DefaultColumns {

	protected static $configAliases = array(
		'type' => null,
		'include' => 'setIncludedFields',
	);

	protected $includedFieldsMap;

	/**
	 * Configure field list.
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
	 * @inheritdoc
	 */
	public function getTrackedFieldNames(ModelTable $table) {
		return array_keys($this->includedFieldsMap);
	}
}
