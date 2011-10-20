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
	
	private static function id($var) {
		if (is_numeric($var) && (int) $var == $var) {
			return (int) $var;
		} else {
			return false;
		}
	}
	
	/**
	 * @param Role|int $role
	 * @return Role 
	 */
	public function role($role) {
		if ($role instanceof Role) {
			return $role;
		} else if (false !== $id = self::id($role)) {
			$role = $this->getRole($id, true);
			if ($role instanceof Role) {
				return $role;
			} else {
				throw new IllegalStateException();
			}
		} else {
			throw new IllegalArgumentException('Must be an instance of Role, or a value '
					. "parsable to an int: '$role'");
		}
	}

	/**
	 * @param User|int $user
	 * @return User 
	 */
	public function user($user) {
		if ($user instanceof User) {
			return $user;
		} else if (self::id($user)) {
			$user = $this->getUser($id, true);
			if ($user instanceof User) {
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
	public function group($group) {
		if ($group instanceof Group) {
			return $group;
		} else if (self::id($group)) {
			$group = $this->getGroup($id, true);
			if ($group instanceof Group) {
				return $group;
			} else {
				throw new IllegalStateException();
			}
		} else {
			throw new IllegalArgumentException();
		}
	}
	
}
