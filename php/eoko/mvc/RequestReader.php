<?php

namespace eoko\mvc;

use Zend\Http\Request;

/**
 * Marker class for legitimate Request data readers.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 29 juil. 2012
 */
interface RequestReader {
	
	function __construct(Request $request);
	
	function createRequest();
}
