<?php

namespace eoze\acl\ArrayManager;

use eoze\acl\Disablable;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 20 oct. 2011
 */
class DisablableGroup extends Group implements Disablable {
	
	private $disabled = false;
	
	public function isDisabled() {
		return $this->disabled;
	}

	public function setDisabled($disabled) {
		$this->disabled = $disabled;
	}
}
