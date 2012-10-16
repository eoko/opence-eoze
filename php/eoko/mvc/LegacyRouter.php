<?php

namespace eoko\mvc;

use eoko\module\ModuleResolver;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 août 2012
 */
class LegacyRouter extends AbstractRouter {
	
	public function route() {

		$action = ModuleResolver::parseRequestAction($this->requestData);
		$action();
	}
}
