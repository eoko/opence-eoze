<?php

namespace eoko\modules\AccessControl;

use eoko\module\executor\JsonExecutor;
use eoko\file\FileType;
use eoko\template\Template;

/**
 * @internal Using alternate class name LoginExecutor (instead of simply login
 * to prevent the method login() from being considered a constructor...)
 */
class LoginExecutor extends JsonExecutor {

	public function index() {
		return $this->forward('root.bootstrap', 'get_js', array('name' => 'login'));
	}

	public function login() {

		$request = $this->getRequest();

		if ($request->has('token')) {
			$token = $request->get('token');
			dump_trace(); // TODO
//			session_id($token);
//			$userSession = $this->getApplication()->getUserSession();
//			dump(array(
//				$token,
//				$_SESSION,
//				session_id(),
//				$userSession->getLoginInfos(),
//			));
//			$userSession->requireLoggedIn();
//			$this->loginInfos = $userSession->getLoginInfos();
//			return true;
		} else {
			$username = $this->request->req('username', true);
			$password = $this->request->req('password', true);
		}

		$userSession = $this->getApplication()->getUserSession();

		$result = $userSession->login($username, $password);

		if ($result->isValid()) {
			$this->loginInfos = $userSession->getLoginInfos();
			return true;
		} else {
			//$this->messages = implode('<br/>', $result->getMessages());
			$this->loginInfos = false;
			if ($result->getMessages()) {
				$this->message = '<p>' . implode('</p><p>', $result->getMessages()) . '</p>';
			} else {
				switch ($result->getCode()) {
					case $result::SUCCESS:
						throw new \IllegalStateException;
					default:
					case $result::FAILURE_CREDENTIAL_INVALID:
						$this->message = 'Identifiant ou mot de passe incorrect. Veuillez réessayer.';
						break;
				}
			}
			return true;
		}
	}

	public function logout() {
		$this
			->getApplication()
			->getUserSession()
			->logout();
		return true;
	}

	public function get_module() {
		if (!headers_sent()) {
			header('Content-type: application/x-javascript');
		}
		echo $this->getModule()->getJavascriptAsString();
	}
}
