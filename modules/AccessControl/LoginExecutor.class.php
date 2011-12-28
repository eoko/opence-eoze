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
	
	protected function prepareLoginTemplate(Template $tpl) {

		$tpl->help = false;

//		$tpl->text = <<<'TXT'
//OpenCE est un service proposé par le comité inter-entreprise de Rhodia. Ses
//services sont réservés aux membres du comité et à ses adhérents. <br /><br />
//Pour accéder à openCE il est nécessaire de s'identifier.
//TXT;
		$tpl->text = 'Bonjour, veuillez vous identifier pour accéder à la console d\'administration.';
	}
	
	public function get_module() {
		$path = $this->findPath('login', FileType::JS);
		$tpl = Template::create()->setFile($path);
		$this->prepareLoginTemplate($tpl);
		header('Content-type: application/x-javascript');
		$tpl->render();
	}
}