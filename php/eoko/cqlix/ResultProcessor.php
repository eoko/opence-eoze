<?php

namespace eoko\cqlix;

/**
 * Interface that must be implemented by classes used to convert the values of
 * the fields of each element in a raw result array to a given format.
 * 
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 21 déc. 2011
 */
interface ResultProcessor {
	
	/**
	 * Returns the processed results. This method expects an (indexed) array
	 * which elements are themselves arrays associating field names to raw
	 * value, obtained as the result of a query.
	 * 
	 * @param array $records
	 * @return array
	 */
	function process(array $records);
}
