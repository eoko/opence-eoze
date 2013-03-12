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
class Zend extends \eoko\security\UserSessionHandler implements \eoko\Authentification\UserSession {

	/**
	 * @var \Zend\Authentication\AuthenticationService
	 */
	private $auth;

	/**
	 * @var DbAdapter
	 */
	private $dbAdapter;

	/**
	 * @var SessionManager
	 */
	private $sessionManager = null;

	/**
	 * Cache for User record.
	 *
	 * @var \User
	 */
	private $user = false;

	public function __construct(DbAdapter $dbAdapter) {
		$storage = null;

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

			$this->fireEvent(self::EVENT_LOGIN, $this->getUserId(true));
		}

		return $result;
	}

	public function logout() {
		$this->auth->clearIdentity();
	}

	public function setSessionManager(SessionManager $sessionManager) {
		$this->sessionManager = $sessionManager;

		// Register events for session destruction
		$saveHandler = $sessionManager->getSaveHandler();
		if ($saveHandler instanceof ObservableSaveHandler) {
			$saveHandler->getEventManager()->attach(ObservableSaveHandler::EVENT_DESTROY, array($this, 'fireDestroyEvent'));
		} else {
			\Logger::get($this)->warn('Cannot monitor session destruction.');
		}

		return $this;
	}

	public function fireDestroyEvent() {
		$this->fireEvent(self::EVENT_DESTROY);
	}

//	private $loginListeners = array();
//
//	private function fireLoginEvent(\User $user) {
//		foreach ($this->loginListeners as $callback) {
//			call_user_func($callback, $user);
//		}
//	}
//
//	public function onLogin($callback) {
//		$this->loginListeners[] = $callback;
//	}
}

class Storage extends \Zend\Authentication\Storage\Session {

//	/**
//	 * Returns true if and only if storage is empty
//	 *
//	 * @throws \Zend\Authentication\Exception\ExceptionInterface If it is impossible to determine whether storage is empty
//	 * @return boolean
//	 */
//	public function isEmpty() {
//		// TODO: Implement isEmpty() method.
//		return true;
//	}

	/**
	 * Returns the contents of storage
	 *
	 * Behavior is undefined when storage is empty.
	 *
	 * @throws \Zend\Authentication\Exception\ExceptionInterface If reading contents from storage is impossible
	 * @return mixed
	 */
//	public function read() {
//		// TODO: Implement read() method.
//		dump_trace(false);
//		dump(parent::read());
//		return parent::read();
//	}

	/**
	 * Writes $contents to storage
	 *
	 * @param  mixed $contents
	 * @throws \Zend\Authentication\Exception\ExceptionInterface If writing $contents to storage is impossible
	 * @return void
	 */
	public function write($contents, $close = false) {
		$result = parent::write($contents);

		if ($close) {
			$this->session->getManager()->writeClose();
		}

		return $result;
	}

//	/**
//	 * Clears contents from storage
//	 *
//	 * @throws \Zend\Authentication\Exception\ExceptionInterface If clearing contents from storage is impossible
//	 * @return void
//	 */
//	public function clear() {
//		// TODO: Implement clear() method.
//	}
}
