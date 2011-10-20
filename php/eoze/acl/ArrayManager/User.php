<?php

namespace eoze\acl\ArrayManager;

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

	public function setGroups($groups) {
		foreach ($groups as $group) {
			if (!($group instanceof Group)) {
				throw new \IllegalArgumentException();
			}
		}
		$this->groups = $groups;
	}
	
	public function addGroup(Group $group) {
		$this->groups[$group->getId()] = $group;
	}
	
	public function removeGroup(Group $group) {
		unset($this->groups[$group->getId()]);
	}
}
