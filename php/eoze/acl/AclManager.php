<?php

namespace eoze\acl;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 20 oct. 2011
 */
interface AclManager {
	
	function isAllowed($user, $role);

	/**
	 * Loads the Role for the given id.
	 * @return Role
	 */
	function getRole($rid);
	
	/**
	 * Loads the User for the given id.
	 * @return User
	 */
	function getUser($uid);
	
	/**
	 * Loads the Group for the given id.
	 * @return Group
	 */
	function getGroup($gid);
}
