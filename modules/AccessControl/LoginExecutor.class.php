<?php

namespace eoko\modules\AccessControl;

use eoko\module\executor\JsonExecutor;
use eoko\file\FileType;
use eoko\template\Template;

use UserSession;
use IllegalStateException;

/**
 * @internal Using alternate class name LoginExecutor (instead of simply login
 * to prevent the method login() from being considered a constructor...)
 */
class LoginExecutor extends JsonExecutor {
	
	public function index() {
		return $this->forward('root.bootstrap', 'get_js', array('name' => 'login'));
	}
	
	public function login() {
		$username = $this->request->req('username', true);
		$password = $this->request->req('password', true);
		
//		dump(array(
//			$username, $password
//		));

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
	
	public function get_module() {
		if (!headers_sent) {
			header('Content-type: application/x-javascript');
		}
		echo $this->getModule()->getJavascriptAsString();
	}
}