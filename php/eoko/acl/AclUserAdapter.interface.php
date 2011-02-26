<?php
/**
 * @author Éric Ortéga <eric@planysphere.fr>
 * @since 2/26/11 3:51 PM
 */
namespace eoko\acl;

use User;

interface AclUserAdapter {
	
	/**
	 * @return User
	 */
	function findUser($login, $password);
	
	/**
	 * Verifies that any further restriction (e.g. account is active, etc.) 
	 * needed to successfuly login is ok for the passed user. If something is
	 * not valid, an UserException explaining the problem should be thrown.
	 */
	function validateUser(User $user);
}