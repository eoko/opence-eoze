<?php

namespace eoko\MultiClients\bin\Model;

require_once __DIR__ . '/UserBase.php';

/**
 * @category opence
 * @package Model
 */
class User extends UserBase {

	/**
	 * It is not safe for Model concrete implementations to override their
	 * parent's constructor. They can do initialization job in this initialize()
	 * method.
	 */
	protected function initialize() {
		// initialization ...
	}

}
