<?php

namespace eoze\util\DataStore;

use eoze\util\DataStore;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
class ArrayStore implements DataStore {

	private $data;

	public function __construct(array $data) {
		$this->data = $data;
	}

	public function query(Query $query = null) {

		if (!$query) {
			return $this->data;
		}

		extract($query->getOptions($this));

		$data = $this->data;

		// filters
		if (isset($filter)) {
			$data = array_filter($data, array($filter, 'test'));
		}

		// sorters
		if (isset($sorter)) {
			usort($data, array($sorter, 'compare'));
		}

		// pagers
		if (isset($pager)) {
			$data = array_slice($data, $pager->getStart(), $pager->getLimit());
		}

		return $data;
	}

	public function getElementFieldValue($element, $field) {
		if ($field === null) {
			return $element;
		} else if (isset($element[$field])) {
			return $element[$field];
		} else {
			return null;
		}
	}

}
