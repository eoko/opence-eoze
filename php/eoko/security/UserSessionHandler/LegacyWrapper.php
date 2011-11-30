<?php

namespace eoko\security\UserSessionHandler;

use eoko\security\UserSessionHandler;
use eoko\php\SessionManager;

use UserSession;
use IllegalStateException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 30 nov. 2011
 */
class LegacyWrapper extends UserSessionHandler {
	
	public function __construct(SessionManager $sessionManager) {
		UserSession::setSessionManager($sessionManager);
		UserSession::onLogin(array($this, 'onLogin'));
	}
	
	public function onLogin($user) {
		$this->fireEvent('login', $user);
	}
	
	public function getUser() {
		return UserSession::getUser();
	}
	
	public function getUserId($require = false) {
		$user = UserSession::getUser();
		if ($user) {
			return $user->getId();
		} else if ($require) {
			throw new IllegalStateException('No user logged');
		} else {
			return null;
		}
	}
	
	public function isAuthorized($level) {
		return UserSession::isAuthorized($level);
	}
}
