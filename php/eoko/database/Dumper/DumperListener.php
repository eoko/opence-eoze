<?php

namespace eoko\database\Dumper;

use eoko\database\Dumper;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 10 avr. 2012
 * 
 * @version 1.0.0 10/04/12 01:13
 */
interface DumperListener {
	
	function __construct(Dumper $dumper);
	
	/**
	 * Called before the {@link eoko\Database\Dumper::dump()} method is called.
	 */
	function beforeDump($dataFilename, $structureFilename = null);
}
