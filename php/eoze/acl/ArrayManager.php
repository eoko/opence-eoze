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
		do {
			$id = $this->nextId++;
		} while (array_key_exists($id, $this->entities));
		return $id;
	}
	
	public function takeId($id, $o) {
		if (array_key_exists($id, $this->entities)) {
			throw new IllegalStateException("Id $id is already taken");
		}
		if ($id == $this->nextId) {
			$this->nextId++;
		}
		$this->entities[$id] = array($id, $o);
		return $id;
	}
	
	public function add(ArrayManager\Role $o) {
		$id = $o->getId();
		if ($o->getManager() !== $this) {
			throw new IllegalArgumentException('Entity from other Manager');
		}
		if (array_key_exists($id, $this->entities)
				&& (!is_array($this->entities[$id]))) {
			throw new IllegalStateException();
		}
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
		$this->entities[$id] = null;
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

	public function newRole($id = null, $disablable = null) {
		if ($disablable === null) {
			$disablable = $this->config->disablableRolesDefault;
		}
		if (!$disablable) {
			$role = new ArrayManager\Role($this, $id);
		} else {
			$role = new ArrayManager\DisablableRole($this, $id);
		}
		return $role;
	}

	public function newGroup($id = null, $disablable = null) {
		if ($disablable === null) {
			$disablable = $this->config->disablableGroupsDefault;
		}
		if (!$disablable) {
			$group = new ArrayManager\Group($this, $id);
		} else {
			$group = new ArrayManager\DisablableGroup($this, $id);
		}
		return $group;
	}

	public function newUser($id = null, $disablable = null) {
		if ($disablable === null) {
			$disablable = $this->config->disablableUserDefault;
		}
		if (!$disablable) {
			$user = new ArrayManager\User($this, $id);
		} else {
			$user = new ArrayManager\DisablableUser($this, $id);
		}
		return $user;
	}
}
