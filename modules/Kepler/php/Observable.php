<?php

namespace eoko\modules\Kepler;
//namespace eoko\comet;

/**
 * This interface may be implemented in order to provide custom names to 
 * observable objects.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 30 nov. 2011
 */
interface Observable {
	
	/**
	 * Gets the name that will be used as the class name in the Kepler event
	 * system.
	 * 
	 * @return string
	 */
	function getCometObservableName();
}
