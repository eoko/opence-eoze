<?php
/**
 * @author Éric Ortéga <eric@planysphere.fr>
 * @since 2/26/11 4:25 PM
 */

namespace eoko\acl\Session;
use eoko\acl\Session;

use eoko\acl\AclUserAdapter as UserAdapter;

use LoginFailedException;

class BasicSession implements Session {
	
	private static $SESSION_LENGTH = 3600; // in seconds
	
	/** @var UserAdapter */
	private $userAdapter;
	
	/** @var User */
	private $user;
	/** @var boolean */
	private $userValid = false;
	
	private $lastActivity;
	
	private $ip;

	public function __construct(UserAdapter $userAdapter) {
		$this->userAdapter = $userAdapter;
		$this->ip = getenv('REMOTE_ADDR');
	}
	
	public function validate() {
		if ($this->ip !== getenv('REMOTE_ADDR')) {
			Logger::getLogger($this)->warn('Request IP {} not '
					. 'matching stored IP {} of identified user',
					getenv("REMOTE_ADDR"), $storedSession->ip);
			return false;
		} else {
			return true;
		}
	}
	
	public function isLoggedIn() {
		return $this->user !== null && $this->userValid;
	}

	public function login($username, $password) {
		
		$user = $this->userAdapter->findUser($username, $password);
		
		if ($user == null) {
			throw new LoginFailedException(lang("L'identification a échoué. "
					. 'Veuillez vérifier votre identifiant et/ou mot de passe.'));
		}
		
		$this->userAdapter->validateUser($user);
		$this->userValid = true;
		
		$this->user = $user;
		
		$this->lastActivity = time();
		
		return true;
	}

	public function logout() {
		$this->user = null;
		session_write_close();
	}
	
	public function getUser() {
		return $this->user;
	}

	public function requireLoggedIn() {
		if (!$this->isLoggedIn()) {
			throw new \UserException('Must be logged in');
		}
	}
	
	function getExpirationDelay(&$now = null) {
		if ($now === null) $now = time();
		return $this->lastActivity + self::$SESSION_LENGTH - $now;
	}
	
	public function updateLastActivity() {
		if (($now = time()) - $this->lastActivity > self::$SESSION_LENGTH) {
			if ($this->isLoggedIn()) {
				$this->logout();
			}
		} else {
			$this->lastActivity = $now;
		}
	}
	
	public function isExpired(&$now = null) {
		if (!$this->isIdentified()) {
			throw new IllegalStateException();
		}
		if ($now === null) $now = time();
		return $now - $this->lastActivity >= self::$SESSION_LENGTH;
	}
}