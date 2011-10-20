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
	
	/**
	 * Creates a new Role in this Manager.
	 * @return Role the new Role
	 */
	function newRole($disablable = false, $rid = null);
	
	/**
	 * Creates a new Group in this Manager.
	 * @return Group the new Group
	 */
	function newGroup($disablable = false, $gid = null);
	
	/**
	 * Creates a new User in this Manager.
	 * @return User the new User
	 */
	function newUser($disablable = false, $uid = null);
}
