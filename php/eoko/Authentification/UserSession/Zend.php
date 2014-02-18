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

namespace eoko\Authentification\UserSession;

use eoko\Authentification\Helper\Crypter;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable\CredentialTreatmentAdapter as AuthAdapter;
use Zend\Authentication\Result;
use Zend\Db\Adapter\Adapter as DbAdapter;

use \User;

/**
 * UserSession implementation built upon Zend Framework Authentication package.
 *
 * Session recovery
 * ----------------
 *
 * Three session recovery mechanisms are in place to prevent losing session. All
 * of them are based on an authentication token that is provided to the client
 * upon successful login request.
 *
 * The server asks the client to pass this token with each request as a cookie
 * (see {@link $restoreCookieName}). Independently from that, the client can
 * also pass this token as a custom header 'X-Eoze-Session'.
 *
 * When the session is lost on the server-side and the server can find the token
 * in any of these headers (custom or cookie), it will use it to recreate the
 * session. The custom header allows the strategy to be effective even with
 * clients that don't accept to set cookies.
 *
 * The last recovery mechanism is implemented on the client-side only. When the
 * javascript client detects that the authenticated session has been lost, it
 * will try to recreate it by authenticating with the token it has kept in memory.
 *
 * The two server-side recovery strategy will recover the session without the
 * client even knowing, and so the request will be fulfilled transparently.
 *
 * The client-side strategy, however, cannot blindly repeat the fail request
 * because it doesn't really know its content and/or if it would have side
 * effects. So, in most case, the failed request will effectively be lost, and
 * the user will have to repeat their action once the session has been restored.
 *
 * @category Eoze
 * @package Authentification
 * @subpackage UserSession
 * @since 2013-03-01 14:00
 */
class Zend implements \eoko\Authentification\UserSession {

	/**
	 * @var \Zend\Authentication\AuthenticationService
	 */
	private $auth;

	/**
	 * @var DbAdapter
	 */
	private $dbAdapter;

	/**
	 * Cache for User record.
	 *
	 * @var \User
	 */
	private $user = false;

	/**
	 * @var callback[]
	 */
	private $loginListeners = array();

	private $authAdapter;

	protected $omittedColumns = 'pwd';

	// Should start and end with a character that is forbidden in
	// username (i.e. ' ')
	private $tokenSeparator = ' <|> ';

	/**
	 * Name of the cookie that will be used to restore the session, if
	 * the session is lost and the cookie is present.
	 *
	 * The default name is slightly obfuscated...
	 *
	 * @var string
	 */
	private $restoreCookieName = 'Eoze_Auhentication_Validity';

	public function __construct(DbAdapter $dbAdapter) {

		$this->dbAdapter = $dbAdapter;

		$storage = new Zend\SessionStorage();
		$this->auth = new AuthenticationService($storage);
	}

	public function requireLoggedIn($return = false) {
		if ($this->getUserId() !== null) {
			return true;
		} else if ($return) {
			return false;
		} else {
			// TODO:
			throw new \UserSessionTimeout();
		}
	}

	/**
	 * Proxies the call to the AuthenticationService, trying to restore
	 * the session first if the session restoration cookie is present.
	 *
	 * @return bool
	 */
	private function hasIdentity() {
		if ($this->auth->hasIdentity()) {
			return true;
		} else if (isset($_SERVER['HTTP_X_EOZE_SESSION'])) {
			$this->loginByToken($_SERVER['HTTP_X_EOZE_SESSION']);
			return $this->auth->hasIdentity();
		} else if (isset($_COOKIE[$this->restoreCookieName])) {
			$this->loginByToken($_COOKIE[$this->restoreCookieName]);
			return $this->auth->hasIdentity();
		} else {
			return false;
		}
	}

	/**
	 * @return \User|null
	 */
	public function getUser() {
		if ($this->user === false) {
			$userId = $this->getUserId();
			$this->user = $userId !== null
				? \User::load($this->getUserId())
				: null;
		}
		return $this->user;
	}

	/**
	 * @param bool $require
	 * @throws \RuntimeException
	 * @return int|null
	 */
	public function getUserId($require = false) {
		if ($this->hasIdentity()) {
			$userData = $this->auth->getIdentity();
			return $userData['id'];
		} else if ($require) {
			throw new \RuntimeException();
		} else {
			return null;
		}
	}

	private function getCrypter() {
		return new Crypter;
	}

	private function getAuthToken() {
		if ($this->hasIdentity()) {
			$userData = $this->auth->getIdentity();
			return $userData['token'];
		} else {
			throw new \RuntimeException();
		}
	}

	private function createToken($username, $password) {
		// random gibberish added for token obfuscation
		return $this->getCrypter()->encrypt(
			$username . $this->tokenSeparator . $password . substr(md5(rand()), 0, 10)
		);
	}

	private function decryptToken($token, &$username, &$password) {
		$decrypted = $this->getCrypter()->decrypt($token);
		$parts = explode($this->tokenSeparator, substr($decrypted, 0, -10));
		if (count($parts) === 2) {
			$username = $parts[0];
			$password = $parts[1];
		}
	}

	public function isAuthorized($level) {
		if ($this->hasIdentity()) {

			if ($level instanceof \Level) {
				$level = $level->level;
			}
			$level = (int) $level;

			$userData = $this->auth->getIdentity();
			$userLevel = $userData['level'];

			return $level >= $userLevel;
		} else {
			return false;
		}
	}

	public function getLoginInfos() {
		// Removed User context on 2013-03-01
		return array(
			'restricted' => !$this->isAuthorized(100), // TODO security
			'userId' => $this->getUserId(),
			'token' => $this->getAuthToken(),
		);
	}

	public function setLoginAdapter($adapter) {
		// TODO: Implement setLoginAdapter() method.
	}

	public function setAuthAdapter(AuthAdapter $authAdapter) {
		$this->authAdapter = $authAdapter;
		return $this;
	}

	/**
	 * @return \Zend\Authentication\Adapter\DbTable
	 */
	private function getAuthAdapter() {
		if (!$this->authAdapter) {
			$this->authAdapter = new AuthAdapter($this->dbAdapter, 'users', 'username', 'pwd', 'SHA1(?)');

			$this->authAdapter->getDbSelect()
				->join(
				'levels',
				'levels_id = levels.id',
				array('level')
			);
		}
		return $this->authAdapter;
	}

	public function loginByToken($token) {
		$this->decryptToken($token, $username, $password);
		return $this->login($username, $password);
	}

	public function login($username, $password) {

		// We want a new session id, because the current may be expired
		// or lost some way or another, and if we don't change it, the cookie
		// won't be set again in the request (don't know who decide to set
		// it or not)...
		session_regenerate_id();

		$omittedColumns = explode(',', $this->omittedColumns);

		$authAdapter = $this->getAuthAdapter();

		$authAdapter
			->setIdentity($username)
			->setCredential($password);

		$result = $this->auth->authenticate($authAdapter);

		if ($result->isValid()) {
			// Succeed

			// Save record as identity
			$userData = (array) $authAdapter->getResultRowObject(null, $omittedColumns);

			if (!$userData['actif']) {
				return new Result(Result::FAILURE, $username, array('Compte désactivé.'));
			}

			$endUse = $userData['end_use'];
			if ($endUse) {
				$endUseDate = new \DateTime($endUse);
				if ($endUseDate->getTimestamp() < time()) {
					return new Result(Result::FAILURE, $username, array('Compte expiré.'));
				}
			}

			$token = $this->createToken($username, $password);
			$userData['token'] = $token;
			setcookie($this->restoreCookieName, $token);

			$storage = $this->auth->getStorage();
			$storage->write($userData, true);

			$this->fireLoginEvent($this->getUserId(true));
		}

		return $result;
	}

	public function logout() {
		// unset restoration cookie
		setcookie($this->restoreCookieName, '', time() - 3600);
		$this->auth->getStorage()->clear();
	}

	public function onLogin($callback) {
		$this->loginListeners[] = $callback;
		return $this;
	}

	private function fireLoginEvent($userId) {
		foreach ($this->loginListeners as $callback) {
			call_user_func($callback, $userId);
		}
	}
}
