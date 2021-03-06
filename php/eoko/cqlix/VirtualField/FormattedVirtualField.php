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

namespace eoko\cqlix\VirtualField;

use eoko\cqlix\VirtualField\AbstractVirtualField;
use ModelColumn;
use Query;
use QueryAliasable;

/**
 * @todo doc
 *
 * @since 2013-10-02 13:03
 */
class FormattedVirtualField extends AbstractVirtualField {

	protected $format = null;
	protected $nullable = true;
	protected $type = ModelColumn::T_STRING;

	protected $defaultAlias = null;

	protected $nullField  = null;
	protected $nullString = '?';

	function __construct($format = null, $defaultAlias = null, $nullable = null, $nullField = null,
	                     $nullString = null) {
		parent::__construct($defaultAlias);

		$this->nullable = $nullable;

		if ($format !== null) {
			$this->format = $format;
		}
		if ($defaultAlias !== null) {
			$this->defaultAlias = $defaultAlias;
		}
		if ($nullable !== null) {
			$this->nullable = $nullable;
		}
		if ($nullField !== null) {
			$this->nullField = $nullField;
		}
		if ($nullString !== null) {
			$this->nullString = $nullString;
		}
	}

	public function isNullable() {
		return $this->nullable;
	}

	protected function doGetClause(QueryAliasable $aliasable) {
		return Query::format($this->format, $aliasable, $this->nullField, $this->nullString);
	}
}
