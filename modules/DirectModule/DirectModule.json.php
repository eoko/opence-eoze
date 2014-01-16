<?php

namespace eoko\modules\DirectModule;

use eoko\module\executor\JsonExecutor;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 8 nov. 2011
 */
class Json extends JsonExecutor {

	function get_module() {
		// UNTESTED
		$override = $this->request->has('name') ? null : array(
			'name' => $this->module->getName()
		);
		$this->forward("{$this->module->getName()}.js", 'get_module', $override);
	}

	public function hello() {
		$this->data = 'hello';
		return true;
	}
}
