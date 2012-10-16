<?php

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 7 déc. 2011
 */
class UnsupportedOperationException extends SystemException {
	
	function __construct($debugMessage = true, $previous = null) {
		if ($debugMessage === true) {
			$trace = debug_backtrace();
			if (isset($trace[1]['class']) && isset($trace[1]['function'])) {
				$class = $trace[1]['class'];
				$method = $trace[1]['function'];
				$debugMessage = "Unsupported operation: $class::$method()";
			}
		}
		parent::__construct($debugMessage, '', $previous);
	}
}
