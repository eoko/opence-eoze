<?php

namespace eoze\util\ObjectRepository;

use eoze\util\ObjectRepository;

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
//	 * A reference to the ObjectRepository is needed to return repository-type
//	 * dependant Query implementations.
//	 */
//	function options(ObjectRepository $repository, &$filter, &$sorter, &$pager);

	/**
	 * Gets the different parts composing the query.
	 * 
	 * A reference to the ObjectRepository is needed to return repository-type
	 * dependant Query implementations.
	 * 
	 * @param ObjectRepository $repository
	 * 
	 * @return array in the form array($optionType1 => $option1, $optionType2 => $option2, etc.)
	 */
	function getOptions(ObjectRepository $repository);

}
