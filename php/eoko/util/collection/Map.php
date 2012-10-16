<?php

namespace eoko\util\collection;

interface Map {
	
	function __get($key);
	
	function count();
	
	function toArray();
	
	/**
	 * @return boolean TRUE if this Map is mutable (that is, can be modified by
	 * any code accessing it), else FALSE
	 */
	function isMutable();
}

class InvalidOffsetException extends \SystemException {
	
}