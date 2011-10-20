<?php

namespace eoze\acl\ArrayManager;

use eoze\acl\AclHelper;
use IllegalStateException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 20 oct. 2011
 */
class Group extends Role implements \eoze\acl\Group {
	
	private $roles = array();
	
	public function getRoles() {
		return $this->roles;
	}
	
	public function setRoles(array $roles) {
		$this->roles = array();
		foreach ($roles as $role) {
			if (!($role instanceof Role)) {
				$this->roles = array();
				throw new \IllegalArgumentException();
			}
			$this->roles[$role->getId()] = $role;
		}
	}

	public function addRole($role) {
		$role = $this->getManager()->role($role);
		$this->roles[$role->getId()] = $role;
	}
	
	public function removeRole($role, $strict = false) {
		$rid = AclHelper::rid($role);
		if ($strict && !isset($this->roles[$rid])) {
			throw new IllegalStateException("$this has no Role#$rid");
		}
		unset($this->roles[$rid]);
	}
}
