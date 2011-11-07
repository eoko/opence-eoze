<?php

namespace eoze\util;

use eoze\util\DataStore\Query;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
interface DataStore {

	function query(Query $query = null);
	
	function getElementFieldValue($element, $field);
}
