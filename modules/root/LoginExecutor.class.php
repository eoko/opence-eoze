<?php

namespace eoko\modules\root;

use \UserSession;

use \IllegalStateException;

/**
 * @internal Using alternate class name LoginExecutor (instead of simply login
 * to prevent the method login() from being considered a constructor...)
 */
class LoginExecutor extends \eoko\module\executor\JsonExecutor {
	
	public function index() {
		return $this->forward('root.bootstrap', 'get_js', array('name' => 'login'));
	}
	
	public function login() {
		$username = $this->request->req('login-user', true);
		$password = $this->request->req('login-pwd', true);

		if (UserSession::logIn($username, $password)) {
			return true;
		} else {
			// The logIn() method should have already fired any needed exception...
			throw new IllegalStateException('Unreachable code');
		}
	}

	public function logout() {
		UserSession::logOut();
		return true;
	}
}