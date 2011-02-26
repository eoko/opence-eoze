<?php
/**
 * @author Éric Ortéga <eric@planysphere.fr>
 * @since 2/26/11 3:58 PM
 */
namespace eoko\acl;

interface Session {
	
	function validate();
	
	function login($username, $password);
	
	function logout();
	
	/** @return \User */
	function getUser();
	
	function isLoggedIn();
	
	function requireLoggedIn();
	
	function getExpirationDelay(&$now = null);
	
	function updateLastActivity();
	
	function isExpired(&$now = null);
}

interface HasUser {
	
	/**
	 * @return User
	 */
	function getUser();
}

interface HasLevels {
	
	function requireLevel($level);
}
