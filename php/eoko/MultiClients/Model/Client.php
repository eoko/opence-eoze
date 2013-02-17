<?php

namespace eoko\MultiClients\bin\Model;

require_once __DIR__ . '/ClientBase.php';

/**
 * @category opence
 * @package Model
 */
class Client extends ClientBase {

	/**
	 * It is not safe for Model concrete implementations to override their
	 * parent's constructor. They can do initialization job in this initialize()
	 * method.
	 */
	protected function initialize() {
		// initialization ...
	}

}
