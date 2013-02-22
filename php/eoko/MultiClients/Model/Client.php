<?php

namespace eoko\MultiClients\Model;

/**
 * @category Eoze
 * @package Model
 */
class Client extends \eoko\MultiClients\Model\Base\ClientBase {

	/**
	 * It is not safe for Model concrete implementations to override their
	 * parent's constructor. They can do initialization job in this initialize()
	 * method.
	 */
	protected function initialize() {
		// initialization ...
	}

}
