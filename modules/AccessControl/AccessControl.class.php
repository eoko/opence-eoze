<?php

namespace eoko\modules\AccessControl;

use eoko\module\Module;

use eoko\acl\Session;
use eoko\acl\SessionProvider;
use eoko\acl\AclUserAdapter;

use eoko\util\Arrays;
use eoko\log\Logger;

use User, UserTable;
use Security;

class AccessControl extends Module implements SessionProvider {
	
	private static $UserSessionName = 'UserSession';
	
	protected $defaultExecutor = 'login';
	
	private $session = null;
	
	/**
	 * @return Session
	 */
	public final function getSession() {
		if ($this->session) {
			return $this->session;
		} else if (($this->session = $this->retrieveSession())) {
			return $this->session;
		} else {
			return $this->session = $_SESSION[self::$UserSessionName] =
					$this->createSession();
		}
	}
	
	/**
	 * @return Session
	 */
	protected function retrieveSession() {
		if (isset($_SESSION[self::$UserSessionName])) {
			
			$storedSession = $_SESSION[self::$UserSessionName];
			Logger::getLogger($this)->debug('Found stored user session');

			if ($storedSession instanceof Session) {
				if ($storedSession->validate()) {
					return $storedSession;
				}
			} else {
				Logger::getLogger($this)->warn(
					'Value found in $_SESSION["UserSession" is not a Session object !!'
				);
			}
		}
		
		return null;
	}
	
	/**
	 * @return Session
	 */
	protected function createSession() {
		return new Session\BasicSession(new DefaultUserAdapter());
	}
	
	public function getLoginInfos($json = false) {
		if ($json) {
			return json_encode($this->getLoginInfos());
		}
		$session = $this->getSession();
		$infogs = array(
//			'restricted' => !self::isAuthorized(100), // TODO security
			'restricted' => false, // TODO security
			'userId' => $session->getUser()->id,
		);
		return Arrays::apply($infos, $session->getUser()->context);
	}
}
