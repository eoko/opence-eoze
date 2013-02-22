<?php

namespace eoko\MultiClients\Model;

/**
 *
 * @category Eoze
 * @package Model
 * @subpackage 
 */
class ClientTable extends \eoko\MultiClients\Model\Base\ClientTableBase {

	/**
	 * It is not safe for ModelTable concrete implementations to override their
	 * parent's constructor. They can do initialization job in this configure()
	 * method.
	 */
	protected function configure() {
		// initialization ...
	}

}
