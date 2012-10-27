<?php

namespace eoko\module;

interface HasChildrenModules {
	
	/**
	 * @return array[Module] Returns an associative array, with children module
	 * names as index and the module instances themselves as value.
	 */
	function listChildrenModules();
}