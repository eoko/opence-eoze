<?php

namespace eoze\acl;

use IllegalArgumentException,
	RuntimeException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 20 oct. 2011
 */
class AclHelper {
	
	private function __construct() {}
	
	private static function getId($o, $class) {
		if (is_integer($o)) {
			return $o;
		} else if (is_object($o)) {
			if ($o instanceof HasIntId && is_a($o, __NAMESPACE__ . "\\$class")) {
				return $o->getId();
			}
		} else if (null !== $id = eoze\parseInt($o)) {
			return $id;
//		} else if ($o === null) {
//			return null;
		}
		throw new RuntimeException('Invalid id: ' . $o);
	}
	
	public static function rid($role) {
		return self::getId($role, 'Role');
	}
	
	public static function uid($user) {
		return self::getId($user, 'User');
	}
	
	public static function gid($group) {
		return self::getId($group, 'Group');
	}
	
	private static function isEnabled($o) {
		if (!is_object($o)) {
			throw new IllegalArgumentException();
		} else if ($o instanceof Disablable) {
			return !$o->isDisabled();
		} else {
			return true;
		}
	}
	
	public static function isDisablable($o) {
		if (!is_object($o)) {
			throw new IllegalArgumentException();
		} else {
			return $o instanceof Disablable;
		}
	}

	/**
	 * @param HasRoles $o
	 * @param bool $asMap
	 * @return array
	 */
	public static function getAllowedRoleIds(HasRoles $o, $asMap = true) {
		if (!is_object($o)) {
			throw new IllegalArgumentException();
		}
		$map = array();
		if (self::isEnabled($o)) {
			if ($o instanceof Role) {
				$map[$o->getId()] = true;
			}
			if ($o instanceof HasRoles) {
				foreach ($o->getRoles() as $role) {
					assert('$role instanceof Role');
					if (self::isEnabled($role)) {
						$map[$role->getId()] = true;
					}
				}
			}
			if ($o instanceof HasGroups) {
				foreach ($o->getGroups() as $group) {
					assert('$group instanceof Group');
					$rids = self::getAllowedRoleIds($group, true);
					if ($rids) {
						$map = array_intersect_key($map, array_diff_key($map, $rids));
					}
				}
			}
		}
		return $asMap ? $map : array_keys($map);
	}
	
}
