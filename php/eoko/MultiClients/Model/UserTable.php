<?php

namespace eoko\MultiClients\bin\Model;

require_once __DIR__ . '/UserTableBase.php';

/**
 *
 * @category opence
 * @package Model
 * @subpackage Table
 */
class UserTable extends UserTableBase {

	/**
	 * It is not safe for ModelTable concrete implementations to override their
	 * parent's constructor. They can do initialization job in this configure()
	 * method.
	 */
	protected function configure() {
		// initialization ...
	}

}
