<?php

namespace eoko\MultiClient\Model;

/**
 *
 * @category Eoze
 * @package Model
 * @subpackage
 *
 * @method static \eoko\MultiClient\Model\User findOneWhere($condition = null, $input = null, array $context = null, $aliasingCallback = null)
 */
class UserTable extends \eoko\MultiClient\Model\Base\UserTableBase {

	/**
	 * It is not safe for ModelTable concrete implementations to override their
	 * parent's constructor. They can do initialization job in this configure()
	 * method.
	 */
	protected function configure() {
		// initialization ...
	}

}
