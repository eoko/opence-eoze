<?php

namespace eoze\Exception;

use UnsupportedOperationException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 30 oct. 2011
 */
class NotImplementedYetException extends UnsupportedOperationException {
	
	public function __construct($debugMessage = null, $previous = null) {
		if ($debugMessage === null) {
			$debutMessage = '';
		}
		parent::__construct($debugMessage, $previous);
	}
}
