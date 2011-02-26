<?php
/**
 * @author Éric Ortéga <eric@planysphere.fr>
 * @since 2/26/11 4:48 PM
 */
namespace eoko\acl;

interface SessionProvider {
	
	/**
	 * @return Session
	 */
	function getSession();
}