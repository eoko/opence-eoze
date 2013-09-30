<?php

namespace eoze\behat\context;

use Behat\Behat\Context\BehatContext;

class EozeContext extends BehatContext {

	public function __construct() {
		$this->useContext('acl', new AclContext);
	}
}
