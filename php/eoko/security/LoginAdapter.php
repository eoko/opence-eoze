<?php

namespace eoko\security;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 24 nov. 2011
 */
interface LoginAdapter {

	/**
	 * @return \User
	 */
	function tryLogin($username, $password, &$reason = null);
}
