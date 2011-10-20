<?php

namespace eoze\acl\ArrayManager;

use eoze\acl\AclHelper;

use IllegalStateException, IllegalArgumentException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 20 oct. 2011
 */
class User extends Group implements \eoze\acl\User {
	
	private $groups = array();
	
	public function getGroups() {
		return $this->groups;
	}

	public function setGroups(array $groups) {
		$this->groups = array();
		foreach ($groups as $group) {
			if (!($group instanceof Group)) {
				$this->groups = array();
				throw new IllegalArgumentException();
			} else {
				$this->groups[$group->getId()] = $group;
			}
		}
	}
	
	public function addGroup($group) {
		$group = $this->getManager()->group($group);
		$this->groups[$group->getId()] = $group;
	}
	
	public function removeGroup($group, $strict = false) {
		$gid = AclHelper::gid($group);
		if ($strict && !isset($this->roles[$gid])) {
			throw new IllegalStateException("$this has no Group#$gid");
		}
		unset($this->groups[$gid]);
	}
}
