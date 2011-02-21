<?php

namespace eoko\util\collection;

interface Map {
	
	function __get($key);
	
	function count();
	
	function toArray();
}

class InvalidOffsetException extends \SystemException {
	
}