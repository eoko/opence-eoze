<?php

namespace eoze\acl;

use eoze\acl\AclManager,
	eoze\acl\AclHelper,
	eoze\acl\Role,
	eoze\acl\User,
	eoze\acl\Group;

use IllegalArgumentException,
	IllegalStateException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 20 oct. 2011
 */
abstract class AbstractAclManager implements AclManager {

	private $lastUserId = null;
	
	private $lastUserAllowedRoleIdMap = null;

	/**
	 * @var AclManagerConfig
	 */
	protected $config;
	
	public function __construct(AclManagerConfig $config = null) {
		$this->config = AclManagerConfig::create($config);
	}
	
	public function isAllowed($user, $role) {
		$uid = AclHelper::uid($user);
		$rid = AclHelper::rid($role);
		if ($this->lastUserId !== $uid || $this->lastUserId === null) {
			$ridMap = AclHelper::getAllowedRoleIds($this->user($user), true);
			$this->lastUserAllowedRoleIdMap = $ridMap ? $ridMap : null;
		}
		return isset($this->lastUserAllowedRoleIdMap[$rid]) 
				&& $this->lastUserAllowedRoleIdMap[$rid];
	}
	
	/**
	 * @param Role|int $role
	 * @return Role 
	 */
	protected function role($role) {
		if ($role instanceof Role) {
			return $role;
		} else if (is_int($role)) {
			$role = $this->getRole($role);
			if ($role instanceof Role) {
				return $role;
			} else {
				throw new IllegalStateException();
			}
		} else {
			throw new IllegalArgumentException();
		}
	}

	/**
	 * @param User|int $user
	 * @return User 
	 */
	protected function user($user) {
		if ($user instanceof User) {
			return $user;
		} else if (is_int($user)) {
			$user = $this->getUser($user);
			if ($user instanceof Role) {
				return $user;
			} else {
				throw new IllegalStateException();
			}
		} else {
			throw new IllegalArgumentException();
		}
	}
	
	/**
	 *
	 * @param Group|int $group
	 * @return Group 
	 */
	protected function group($group) {
		if ($group instanceof Group) {
			return $group;
		} else if (is_int($group)) {
			$group = $this->getGroup($group);
			if ($user instanceof Role) {
				return $group;
			} else {
				throw new IllegalStateException();
			}
		} else {
			throw new IllegalArgumentException();
		}
	}
	
}
