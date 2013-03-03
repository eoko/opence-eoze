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

		$username = $this->request->req('username', true);
		$password = $this->request->req('password', true);

		$userSession = $this->getApplication()->getUserSession();

		$result = $userSession->login($username, $password);

		if ($result->isValid()) {
			$this->loginInfos = $userSession->getLoginInfos();
			return true;
		} else {
			//$this->messages = implode('<br/>', $result->getMessages());
			$this->loginInfos = false;
			$this->message = 'Identifiant ou mot de passe incorrect. Veuillez réessayer.';
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
