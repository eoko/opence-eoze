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

namespace eoko\modules\EozeExt4\controller\Rest\Cqlix\Query\Processor;

use eoko\modules\EozeExt4\controller\Rest\Cqlix\Query\FieldNameResolver;

/**
 * Limit query processor for unlimited query.
 *
 * @since 2013-05-21 15:50
 */
class NoLimit extends Limit {

	/**
	 * @inheritdoc
	 *
	 * Removes $data parameter.
	 */
	public function __construct(FieldNameResolver $nameResolver) {
		parent::__construct($nameResolver, null);
	}

	/**
	 * @inheritdoc
	 */
	protected function setData(array $data = null) {
		$this->limit = false;
		$this->start = 0;
	}

	/**
	 * @inheritdoc
	 */
	public function apply(\ModelTableQuery $query) {
		$query->limit(false);
	}
}

