<?php

namespace eoze\message;

use eoze\util\Data;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 27 oct. 2011
 */
interface Message {

	/**
	 * @return Data
	 */
	function getBody();
}
