<?php

namespace eoko\MultiClients\bin\Model;

require_once __DIR__ . '/ClientTableBase.php';

/**
 *
 * @category opence
 * @package Model
 * @subpackage Table
 */
class ClientTable extends ClientTableBase {

	/**
	 * It is not safe for ModelTable concrete implementations to override their
	 * parent's constructor. They can do initialization job in this configure()
	 * method.
	 */
	protected function configure() {
		// initialization ...
	}

}
