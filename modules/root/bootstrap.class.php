<?php

namespace eoko\modules\root;

use eoko\template\Template;

class Bootstrap extends \eoko\module\executor\JsFileExecutor {

	public function index() {
		return $this->forward('root.html');
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

	protected function getDefaultModule($name) {
		if ($name === 'ApplicationBootstrap') {
			return true;
		} else {
			return parent::getDefaultModule($name);
		}
	}
}
