<?php

namespace eoko\modules\root;

use eoko\template\Template;

use \UserSession;
use \ExtJSResponse;

class Bootstrap extends \eoko\module\executor\JsFileExecutor {
	
	public function index() {
		return $this->forward('root.html');
	}
	
	/**
	 * @todo LEGACY Move to the new model
	 */
	protected function beforeAction() {
		if (!$this->isAction('ping_session')) {
			UserSession::updateUserLastActivity();
		}
		parent::beforeAction();
	}

	/**
	 * @todo LEGACY Move to the new model
	 */
	public function ping_session() {
		if (UserSession::isIdentified()) {
			ExtJSResponse::put('pong', true);
		} else {
			ExtJSResponse::put('pong', false);
			ExtJSResponse::put('text',
				lang(
					'Vous avez été déconnecté suite à une longue période d\'inactivité. '
					. 'Veuillez vous identifier à nouveau pour continuer votre travail.'
				)
			);
		}
		ExtJSResponse::answer();
	}

	/**
	 * Needed for member space.
	 * @TODO see why...
	 */
	public function logout() {
		$this->forward('root.login', 'logout');
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
}