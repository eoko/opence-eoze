<?php

namespace eoze\acl\ArrayManager;

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
		foreach ($roles as $role) {
			if (!($role instanceof Role)) {
				throw new \IllegalArgumentException();
			}
		}
		$this->roles = $roles;
	}

	public function addRole(Role $role) {
		$this->roles[] = $role;
	}
}
