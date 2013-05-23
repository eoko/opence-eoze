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

use eoko\modules\EozeExt4\controller\Rest\Cqlix\Request\Params as RequestParams;

/**
 * Query processor for limit param.
 *
 * @since 2013-05-21 15:30
 */
class Limit extends AbstractProcessor {

	/**
	 * Maximum number of returned results. `false` means no limit.
	 *
	 * @var int|bool
	 */
	protected $limit;
	/**
	 * Cardinality of the first result to fetch.
	 *
	 * @var int
	 */
	protected $start;

	/**
	 * @inheritdoc
	 */
	protected function setData(array $data) {
		$this->limit = $data['limit'];
		$this->start = $data['start'];
	}

	/**
	 * @inheritdoc
	 */
	public function apply(\ModelTableQuery $query) {
		$query->limit($this->limit, $this->start);
	}

	/**
	 * @inheritdoc
	 */
	public function getResponseMetaData(RequestParams $requestParams) {
		return $requestParams->convertArrayIndexes(array(
			$requestParams::LIMIT => $this->limit,
			$requestParams::START => $this->start,
		));
	}
}
