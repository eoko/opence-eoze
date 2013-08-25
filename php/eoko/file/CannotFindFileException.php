<?php

namespace eoko\file;

class CannotFindFileException extends MissingFileException {

	protected $msgFormat = 'Cannot find path for %s: "%s"';
}