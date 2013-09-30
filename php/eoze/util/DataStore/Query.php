<?php

namespace eoze\util\DataStore;

use eoze\util\DataStore;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
interface Query {

	const PAGER  = 'pager';
	const FILTER = 'filter';
	const SORTER = 'sorter';

//	/**
//	 * A reference to the DataStore is needed to return repository-type
//	 * dependant Query implementations.
//	 */
//	function options(DataStore $store, &$filter, &$sorter, &$pager);

	/**
	 * Gets the different parts composing the query.
	 * 
	 * A reference to the DataStore is needed to return repository-type
	 * dependant Query implementations.
	 * 
	 * @param DataStore $store
	 * 
	 * @return array in the form array($optionType1 => $option1, $optionType2 => $option2, etc.)
	 */
	function getOptions(DataStore $store);

}
