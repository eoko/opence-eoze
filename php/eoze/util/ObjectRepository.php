<?php

namespace eoze\util;

use eoze\util\ObjectRepository\Query;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
interface ObjectRepository {

	function query(Query $query = null);
	
	function getElementFieldValue($element, $field);
	
//	/**
//	 * @return Query
//	 */
//	function createQuery();
}
