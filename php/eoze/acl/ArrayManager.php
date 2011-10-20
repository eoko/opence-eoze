<?php

namespace eoze\acl;

use IllegalArgumentException;
use IllegalStateException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 20 oct. 2011
 */
class ArrayManager extends AbstractAclManager {
	
	private $entities = array();
	
	private $nextId = 1;
	
	public function nextId() {
		return $this->nextId++;
	}
	
	public function add(ArrayManager\Role $o) {
		$id = $o->getId();
		if ($o->getManager() !== $this) {
			throw new IllegalArgumentException('Entity from other Manager');
		}
		assert($id < $this->nextId);
		$this->entities[$id] = $o;
		return $id;
	}
	
	public function remove(ArrayManager\Role $o) {
		if ($o->getManager() !== $this) {
			throw new IllegalArgumentException();
		}
		$id = $o->getId();
		if (!isset($this->entities[$id])) {
			return false;
		}
		if ($this->entities[$id] !== $o) {
			throw new IllegalStateException();
		}
		unset($this->entities[$id]);
		return true;
	}
	
	private function get($id, $class) {
		if (!isset($this->entities[$id])) {
			return null;
		} else {
			$o = $this->entities[$id];
			if (!is_a($o, get_class() . "\\$class")) {
				$actualClass = basename(str_replace('\\', DIRECTORY_SEPARATOR, get_class($o)));
				throw new IllegalArgumentException("$actualClass#$id is not a $class");
			}
			return $o;
		}
	}

	/**
	 * @param int $gid
	 * @return ArrayManager\Group
	 */
	public function getGroup($gid) {
		return $this->get($gid, 'Group');
	}
	
	/**
	 * @param int $rid
	 * @return ArrayManager\Role
	 */
	public function getRole($rid) {
		return $this->get($rid, 'Role');
	}
	
	/**
	 * @param int $uid
	 * @return ArrayManager\User
	 */
	public function getUser($uid) {
		return $this->get($uid, 'User');
	}

	/**
	 * @param bool $disablable
	 * @return ArrayManager\Role 
	 */
	public function newRole($disablable = false) {
		if (!$disablable) {
			$role = new ArrayManager\Role($this);
		} else {
			$role = new ArrayManager\DisablableRole($this);
		}
		$this->add($role);
		return $role;
	}

	/**
	 * @param bool $disablable
	 * @return ArrayManager\Group 
	 */
	public function newGroup($disablable = false) {
		if (!$disablable) {
			$group = new ArrayManager\Group($this);
		} else {
			$group = new ArrayManager\DisablableGroup($this);
		}
		$this->add($group);
		return $group;
	}

	/**
	 * @param bool $disablable
	 * @return ArrayManager\User 
	 */
	public function newUser($disablable = false) {
		if (!$disablable) {
			$user = new ArrayManager\User($this);
		} else {
			$user = new ArrayManager\DisablableUser($this);
		}
		$this->add($user);
		return $user;
	}
}