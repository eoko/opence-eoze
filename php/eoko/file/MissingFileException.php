<?php

namespace eoko\file;

class MissingFileException extends \SystemException {

	protected $msgFormat = 'Missing %s: "%s"';

	public function __construct($filename, $type = null, $previous = null) {
		if (is_array($filename)) $filename = implode('|', $filename);
		$file = $type !== null ? "$type file" : "file";
		parent::__construct(sprintf($this->msgFormat, $file, $filename), '', $previous);
	}

}
