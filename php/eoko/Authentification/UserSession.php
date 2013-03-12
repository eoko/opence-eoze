<?php
/**
 * Copyright (C) 2013 Eoko
 *
 * This file is part of Opence.
 *
 * Opence is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Opence is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Opence. If not, see <http://www.gnu.org/licenses/gpl.txt>.
 *
 * @copyright Copyright (C) 2013 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

namespace eoko\Authentification;

use Zend\Session\ManagerInterface as SessionManager;

/**
 * UserSession package exposes authenticated user to application.
 *
 * @category Eoze
 * @package Authentification
 * @subpackage UserSession
 * @since 2013-02-28 13:14
 */
interface UserSession {

	// public static $SESSION_LENGTH

	public function requireLoggedIn();

	/**
	 * @return \User|null
	 */
	public function getUser();

	/**
	 * @return int|null
	 */
	public function getUserId();

	public function isAuthorized($level);


	public function getLoginInfos();


	public function setLoginAdapter($adapter);

	/**
	 * @param string $username
	 * @param string $password
	 * @return \Zend\Authentication\Result
	 */
	public function login($username, $password);
	public function logout();


	public function setSessionManager(SessionManager $sessionManager);

	/**
	 * @param callback $callback
	 * @return UserSession
	 */
	public function onLogin($callback);

	/**
	 * @param callback $callback
	 * @return UserSession
	 */
	public function onDestroy($callback);
}
