<?php

namespace eoze\message;

use eoze\util\Data;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 27 oct. 2011
 */
interface Envelope extends Message {
	
	/**
	 * @return bool
	 */
	function hasHeader($name);
	
	/**
	 * @return Data
	 */
	function getHeader($name);

	/**
	 * @return array($name => Data $data)
	 */
	function getHeaders();
}
