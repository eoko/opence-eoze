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

use eoko\config\Application;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Session\ManagerInterface as SessionManager;
use Eoze\Session\SaveHandler\ObservableInterface as ObservableSaveHandler;

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

	public function __construct(DbAdapter $dbAdapter) {

		$this->dbAdapter = $dbAdapter;

		$storage = new Storage();
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
		if ($this->auth->hasIdentity()) {
			$userData = $this->auth->getIdentity();
			return $userData['id'];
		} else if ($require) {
			throw new \RuntimeException();
		} else {
			return null;
		}
	}

	public function isAuthorized($level) {
		if ($this->auth->hasIdentity()) {

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
		);
	}

	public function setLoginAdapter($adapter) {
		// TODO: Implement setLoginAdapter() method.
	}

	private $authAdapter;

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

	protected $omittedColumns = 'pwd';

	public function login($username, $password) {

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

			$storage = $this->auth->getStorage();
			$storage->write($userData, true);

			$this->fireLoginEvent($this->getUserId(true));
		}

		return $result;
	}

	public function logout() {
		$this->auth->clearIdentity();
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

/**
 * Session storage that automatically close the session on writing, unless otherwise specified.
 */
class Storage extends \Zend\Authentication\Storage\Session {

	/**
	 * @inheritdoc
	 */
	public function write($contents, $close = false) {
		/** @noinspection PhpVoidFunctionResultUsedInspection */
		$result = parent::write($contents);

		if ($close) {
			$this->session->getManager()->writeClose();
		}

		return $result;
	}
}
