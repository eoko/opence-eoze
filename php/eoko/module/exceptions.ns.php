<?php

namespace eoko\module\exceptions;

use SystemException;

class MissingModuleException extends SystemException {

	public function __construct($name, \Exception $previous = null) {
		parent::__construct('Missing module: ' . $name, '', $previous);
	}
}

class InvalidModuleException extends SystemException {

	public function __construct($name, $cause, \Exception $previous = null) {
		parent::__construct("Invalid module: $name ($cause)", '', $previous);
	}
}

