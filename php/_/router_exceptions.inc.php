<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 */

// !!! IMPORTANT !!!
// Code in this file is not allowed to use the LANGUAGE facilities !!!

class OpenceException extends Exception {

	private $debugMessage = null;
	private $msg = null;

	private $details = null;

	/**
	 *
	 * @param String $debugMessage	technical details message; not meant to be
	 * displayed to the end-user (but rather be logged somewhere)
	 * @param String $message		message to be displayed to the end-user
	 * @param Exception $previous
	 */
	function __construct($debugMessage = '', $message = '', Exception $previous = null) {
		$this->debugMessage = $debugMessage;
		$this->msg = $message;
		parent::__construct($debugMessage, null, $previous);
	}
	
	/**
	 * @return OpenceException
	 */
	public static function create() {
		$class = get_called_class();
		$r = new ReflectionClass($class);
		return $r->newInstanceArgs(func_get_args());
	}

	function hasUserMessage() {
		return $this->msg != '';
	}

	function getUserMessage() {
		return $this->msg;
	}

	function getDebugMessage() {
		return $this->debugMessage;
	}

	function hasDebugMessage() {
		return $this->debugMessage != '';
	}

	/**
	 * Format a standard PHP exception for logging output. This method is
	 * provided to centralize the formatting of standard exceptions and
	 * OpenceExceptions (through their toString() method) in a single place.
	 * @param Exception $ex
	 * @return String
	 */
	static function formatPhpException($ex) {
		return get_class($ex) . ' -- ' . $ex->getMessage() . PHP_EOL .
				'## ' . $ex->getFile() . ':' . $ex->getLine() . PHP_EOL .
				$ex->getTraceAsString();
	}

	function __toString() {
		return get_class($this) . ' -- ' . $this->getDebugMessage() . PHP_EOL .
				'## ' . $this->getFile() . ':' . $this->getLine() . PHP_EOL .
				$this->getTraceAsString();
	}

	/**
	 * @param string $details
	 * @return OpenceException
	 */
	public function addDetails($details) {
		if ($this->details === null) {
			$this->details = $details;
		} else if (is_array($this->details)) {
			$this->details = array($this->details, $details);
		} else {
			$this->details[] = $details;
		}
		return $this;
	}

	/**
	 * @param string $docRef Syntactically correct PHPDoc reference.
	 * @return OpenceException
	 */
	public function addDocRef($docRef) {
		return $this->addDetails('doc://' . $docRef);
	}
}

class UserException extends OpenceException {

	private $errorTitle;

	function __construct($message = '', $errorTitle = '', $debugMessage = '', Exception $previous = null) {
		parent::__construct($debugMessage, $message, $previous);
		$this->errorTitle = $errorTitle;
	}

	public function getErrorTitle() {
		return $this->errorTitle;
	}

	public function hasErrorTitle() {
		return $this->errorTitle != '';
	}
}

class SystemException extends OpenceException {
	function __construct($debugMessage = '', $message = '', Exception $previous = null) {
		parent::__construct($debugMessage, $message, $previous);
	}
}

class IllegalArgumentException extends SystemException {
	function __construct($debugMessage = '', $previous = null) {
		parent::__construct($debugMessage, '', $previous);
	}
}

class IllegalStateException extends SystemException {
	function __construct($debugMessage = '', $previous = null) {
		parent::__construct($debugMessage, '', $previous);
	}
}

class NullPointerException extends SystemException {
	
}

// Needed bellow
require_once __DIR__ . '/UnsupportedOperationException.php';

class SecurityException extends SystemException {
	function __construct($debugMessage = '', $previous = null) {
		parent::__construct($debugMessage, '', $previous);
	}
}

class UnsupportedActionException extends SystemException {

	/**
	 * Name of the module
	 * @var String
	 */
	private $module;
	/**
	 * Name of the unsupported action
	 * @var String
	 */
	private $action;

	function __construct($module, $action = null, $message = '') {

		if ($action === null) {
			if (false == ($module instanceof Controller)) {
//				throw new IllegalArgumentException('', $this);
			} else {
				$this->module = $module->getModule();
				$this->action = $module->getAction();
			}
		} else {
			$this->module = $module;
			$this->action = $action;
		}

		if ($message === null) {
			$message = sprintf('Module [%s] does not support the action [%s]', $module, $action);
		}

		parent::__construct($message);
    }
}

class MissingRequiredRequestParamException extends SystemException {

	private $key;

	function __construct($key, $message = false) {

		$this->key = $key;

		if ($message === false) {
			$message = sprintf('Missing param(s) in request: \'%s\'', $key);
		}

		parent::__construct($message);
	}

	public function getKey() {
		return $this->key;
	}
}

class ConfigurationException extends SystemException {

	function  __construct($file = 'UNKNOWN', $nodePath = null, $debugMessage = '', $message = '', Exception $previous = null) {
		if ($file === null) $file = 'UNKNOWN';
		$name = "$file:$nodePath";
		if ($debugMessage == null) {
			$debugMessage = $name
				. ($previous !== null ? ' (' . $previous->getMessage() . ')' : null);
 		} else {
			$debugMessage = "$name $debugMessage";
		}
		parent::__construct($debugMessage, $message, $previous);
	}
}

class MissingConfigurationException extends ConfigurationException {

	public static function throwFrom(Config $config, $debugMessage = '',
			$message = '', Exception $previous = null) {

		throw new MissingConfigurationException($config->getConfigName(),
				$config->getNodeName(), $debugMessage, $message, $previous);
	}
}

class InvalidConfigurationException extends ConfigurationException {

	public static function throwFrom(Config $config, $debugMessage = '',
			$message = '', Exception $previous = null) {

		throw new InvalidConfigurationException($config->getConfigName(),
				$config->getNodeName(), $debugMessage, $message, $previous);
	}
}

class GenerationException extends SystemException {
	function  __construct(Exception $previous = null) {
		$debugMessage = $previous->getMessage();
		parent::__construct($debugMessage, null, $previous);
	}
}

interface SqlException {

}

class SqlUserException extends UserException implements SqlException {

	public $errorInfo;
	
	public function __construct($errorInfo, $message, $title, $previous = null) {
		$this->errorInfo = $errorInfo;
		parent::__construct($message, $title, null, $previous);
	}
}

class SqlSystemException extends SystemException implements SqlException {
	
	public $errorInfo;

	public function __construct($errorInfo, $message = null, $previous = null) {
		if (is_array($errorInfo)) {
			$this->errorInfo = $errorInfo;
			$msg = $errorInfo[2];
		} else {
			$msg = $errorInfo;
		}
		parent::__construct($msg, $message, $previous);
	}
}

class IllegalRequestParamException extends IllegalArgumentException {

}

class RequestException extends SystemException {

	public function __construct(Request $request, $field, $cause = null, $userMessage = '', $debugMessage = null) {
		if ($debugMessage === null) {
			if ($request->has($field)) {
				$debugMessage = "Invalid field in request: $field";
			} else {
				$debugMessage = "Missing field in request: $field";
			}
			if ($cause !== null) $debugMessage .= " ($cause)";
		}
		parent::__construct($debugMessage, $userMessage, $previous);
	}
}

class UnimplementedYetException extends UnsupportedOperationException {

	public function __construct() {
		$msg = "Unimplemented yet";
		if (func_num_args() > 0) {
			$msg .= ' ' . func_get_arg(0);
		}
		if (func_num_args() > 1) {
			$msg .= '::' . func_get_arg(1);
		}
		if (func_num_args() > 2) {
			$msg .= ' (' . func_get_arg(2) . ')';
		}
		parent::__construct($msg, null);
	}
}

class DeprecatedException extends SystemException {
	
}