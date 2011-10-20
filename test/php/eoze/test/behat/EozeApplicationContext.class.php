<?php

namespace eoze\test\behat;

use eoze\behat\context\EozeContext;

abstract class EozeApplicationContext extends DatabaseBehatContext {
	
	public function __construct() {
		$this->useContext('eoze', new EozeContext);
	}
	
}
