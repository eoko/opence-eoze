<?php

namespace eoko\security\LoginAdapter;

use eoko\security\LoginAdapter;
use User;
use eoko\util\date\DateHelper;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 24 nov. 2011
 */
class DummyAdapter implements LoginAdapter {
	
	private $level;
	
	public function tryLogin($username = null, $password = null, &$reason = null) {
		return User::create(array(
			'username'   => $username,
			'Level'      => $this->level,
			'end_use'    => date('Y-m-d', time() + 86400),
			'actif'      => true,
			'deleted'    => false,
		), false);
	}
	
	public function setLevel($level) {
		$this->level = $level;
	}

}
