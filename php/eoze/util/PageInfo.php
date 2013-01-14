<?php

namespace eoze\util;

use IllegalArgumentException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 nov. 2011
 */
class PageInfo {

	public $limit = null;

	public $start = 0;

	public function __construct($limit = null, $start = 0) {
		if ($limit !== null && $limit <= 0) {
			throw new IllegalArgumentException;
		}
		$this->limit = $limit;
		$this->start = $start;
	}

	public static function getArrayPage(array $array, PageInfo $pageInfo = null) {
		if ($pageInfo === null) {
			return $array;
		} else {
			if ($pageInfo->limit === null) {
				if ($pageInfo->start === 0) {
					return $array;
				} else {
					return array_slice($array, $pageInfo->start);
				}
			} else {
				return array_slice($array, $pageInfo->start, $pageInfo->limit);
			}
		}
	}

}
