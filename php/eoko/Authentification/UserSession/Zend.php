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

	private function decryptToken($token, &$username, &$password) {
		$parts = explode(
			$this->tokenSeparator,
			$this->getCrypter()->decrypt($token)
		);
		$username = $parts[0];
		$password = $parts[1];
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

			$userData['token'] = $this->getCrypter()->encrypt(
				$username . $this->tokenSeparator . $password
			);

			$storage = $this->auth->getStorage();
			$storage->write($userData, true);

			$this->fireLoginEvent($this->getUserId(true));
		}

		return $result;
	}

	public function logout() {
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
