<?php

use eoko\security\LoginAdapter;
use eoko\security\LoginAdapter\DefaultLoginAdapter;

/**
 * @author Éric Ortéga <eric@mail.com>
 */
class UserSession {

	private static $SESSION_LENGTH = 3600; // in seconds
	
	const LEVEL_AUTHENTICATED = 99;

	/** @var UserSession */
	private static $instance;

	private $loggedIn = false;
	private $ip = null;
	/** @var User */
	private $user = null;
	private $lastActivity;

	const DEFAULT_REQ_DATA_NAME = 'sessionDataId';
	private $data = null;

	private function __construct() {
		$this->loggedIn = false;
		$this->ip = getenv('REMOTE_ADDR');
		$this->user = null;
		$this->lastActivity = time();
	}

	/**
	 * @return UserSession
	 */
	private static function getInstance() {

		if (self::$instance !== null) {
			return self::$instance;
		}

		if (self::$instance === null) {
			if (isset($_SESSION['UserSession'])) {

				$storedSession = $_SESSION['UserSession'];
				Logger::getLogger('UserSession')->debug('Found stored user session');

				if ($storedSession instanceof UserSession) {
					if ($storedSession->ip === getenv('REMOTE_ADDR')) {
						self::$instance = $storedSession;
					} else {
						Logger::getLogger('UserSession')->warn('Request IP {} not '
								. 'matching stored IP {} of identified user',
								getenv("REMOTE_ADDR"), $storedSession->ip);
					}
				} else {
					Logger::getLogger('UserSession')->warn('Value found in $_SESSION["UserSession"'
							. ' is not UserSession object');
				}
			}
		}

		if (self::$instance === null) {
			Logger::getLogger('UserSession')->debug('No valid user session stored');
			self::$instance = new UserSession();
		}

		if (self::$instance->isIdentified() && self::$instance->isExpired()) {
			self::$instance->loggedIn = false;
		}

//		Logger::dbg('Session user is: {}', self::$instance->user);

		return self::$instance;
	}

	private static function isExpired(&$now = null) {
		$instance = self::getInstance();
		if (!$instance->isIdentified()) {
			throw new IllegalStateException();
		}
		if ($now === null) $now = time();
		return $now - $instance->lastActivity >= self::$SESSION_LENGTH;
	}

	public static function getExpirationDelay($now = null) {
		if ($now === null) $now = time();
		return self::getInstance()->lastActivity + self::$SESSION_LENGTH - $now;
	}

	private static function startIdentifiedSession(User $user, $loggedIn) {

		$user->setPwd(null, true);

		$instance = self::getInstance();
		$instance->ip = getenv("REMOTE_ADDR");
		$instance->loggedIn = $loggedIn;
		$instance->user = $user;
		$instance->lastActivity = time();
		$instance->data = null;

		Logger::getLogger('UserSession')->debug('Saving user session');

		$_SESSION['UserSession'] = $instance;
	}
	
	private static $loginAdapter;
	
	public static function setLoginAdapter(LoginAdapter $adapter) {
		self::$loginAdapter = $adapter;
	}
	
	/**
	 * @return LoginAdapter
	 */
	protected static function getLoginAdapter() {
		if (!self::$loginAdapter) {
			self::$loginAdapter = new LoginAdapter\DefaultLoginAdapter();
		}
		return self::$loginAdapter;
	}

	public static function login($username, $password) {
		try {
			$user = self::getLoginAdapter()->tryLogin($username, $password, $reason);
			if ($user) {
				self::startIdentifiedSession($user, true);
				ExtJSResponse::put('loginInfos', self::getLoginInfos());
				self::fireLoginEvent($user);
				return true;
			} else {
				throw new LoginFailedException($reason);
			}
		} catch (MissingRequiredRequestParamException $ex) {
			throw new LoginFailedException(lang('L\'identification a échoué.'));
		}
	}
	
	private static $loginListeners;
	
	private static function fireLoginEvent(User $user) {
		if (self::$loginListeners) {
			foreach (self::$loginListeners as $fn) {
				$fn($user);
			}
		}
	}
	
	public static function onLogin($fn) {
		self::$loginListeners[] = $fn;
	}

	public static function getLoginInfos($json = false) {
		if ($json) {
			return json_encode(self::getLoginInfos());
		}
		$infos = array(
			'restricted' => !self::isAuthorized(100), // TODO security
			'userId' => self::getUser()->id,
		);
		return ArrayHelper::apply($infos, self::getUser()->context);
	}

	public static function logOut() {
		session_destroy();
		session_write_close();
	}

	public static function isIdentified() {
//		self::startIdentifiedSession(User::load(84), true);
		return self::getInstance()->loggedIn;
	}

	public static function isLoginRequestSet() {
		return isset($_POST['login-user']) && $_POST['login-user'] != ''
					&& isset($_POST['login-pwd']) && $_POST['login-pwd'];
	}

	/**
	 *
	 * @return User
	 */
	public static function getUser() {
		return self::getInstance()->user;
	}

	public static function isAuthorized($level) {
		if ($level instanceof Level) $level = $level->level;
		if (self::isIdentified()) {
			if (!(($user = self::getUser()) instanceof User)
					|| null === $userLevel = $user->getLevel()) {
				return false;
			} else {
				return ((int) $level >= $userLevel->level);
			}
		} else {
			return false;
		}
	}

	public static function requireLevel($level) {
		if (!self::isAuthorized($level)) {
			throw new SecurityException();
		}
	}

	public static function updateUserLastActivity() {
		$instance = self::getInstance();
		if (($now = time()) - $instance->lastActivity > self::$SESSION_LENGTH) {
			if ($instance->isIdentified()) {
				$instance->loggedIn = false;
			}
		} else {
			$instance->lastActivity = $now;
		}
	}

	public static function requireLoggedIn() {
		$instance = self::getInstance();
		if (!$instance->isIdentified()) throw new UserSessionTimeout(
				null, null,
				"Session timeout. Last activity: $instance->lastActivity, "
				. "current time: " . ($now = time()) . " ; dif:"
				. ($now - $instance->lastActivity) . " > " . self::$SESSION_LENGTH . "."
		);
	}

	/**
	 * @param int $timeToLive time to live, in seconds
	 * @return UserSessionDataItem
	 */
	public static function & createSessionData($timeToLive = 3600) {
		$instance = self::getInstance();

		do {
			$id = StringHelper::randomString(8);
		} while(isset($instance->data[$id]));

		$instance->data[$id] = new UserSessionDataItem($id, $timeToLive);

		return $instance->data[$id];
	}

	public static function destroySessionData($id) {
		// We don't really want to check for teh session existance, which would
		// produce a completly meaningless error warning...
		unset(self::getInstance()->data[$id]);
		return true;
	}

	/**
	 * @param string $id the id of the data to retrieve
	 * @return UserSessionDataItem
	 */
	public static function getSessionData($id) {
		$instance = self::getInstance();
		return isset($instance->data[$id]) ? $instance->data[$id] : null;
	}

	/**
	 *
	 * @param Request $request
	 * @param string $name
	 * @return UserSessionDataItem
	 */
	public static function getSessionDataFromRequest(Request $request, $require = true,
			$name = self::DEFAULT_REQ_DATA_NAME) {

		if ($require) {
			if (null === $data = self::getSessionData($id = $request->req($name))) {
				throw new IllegalStateException("Missing session data (id=$id)");
			} else {
				return $data;
			}
		} else if (null !== $id = $request->get($name, null)) {
			return self::getSessionData($id);
		} else {
			return null;
		}
	}

}

class LoginFailedException extends UserException {

	function __construct($msg = null) {
		if ($msg === null){
			$msg = lang('L\'identification a échoué. Veuillez vérifier votre '
					. 'identifiant et/ou mot de passe');
		}
		parent::__construct($msg, lang('Échec de l\'identification'));
	}
}

class UserSessionTimeout extends UserException {

	public function  __construct($message = null, $errorTitle = null, $debugMessage = '', Exception $previous = null) {
		if ($message === null) $message = lang(
			'Vous avez été déconnecté suite à une longue période d\'inactivité. '
			. 'Veuillez vous identifier à nouveau pour continuer votre travail.'
		);
		if ($errorTitle === null) $errorTitle = lang('Déconnexion');

		parent::__construct($message, $errorTitle, $debugMessage, $previous);

		ExtJSResponse::put('cause', 'sessionTimeout');
	}
}

class UserSessionDataItem implements ArrayAccess {

	private $startTime;
	private $timeToLive;

	public $id;
	public $data = array();

	function __construct($id, $timeToLive = 3600) {
		$this->id = $id;
		$this->startTime = time();
		$this->timeToLive = $timeToLive;
	}

	public function renewLease() {
		$this->startTime = time();
	}
	
	public function & __get($name) {
		return $this->data[$name];
	}
	
	public function & __set($name, $value) {
		$this->data[$name] = $value;
		return $this->data[$name];
	}

	/**
	 * @return bool
	 */
	public function isExpired() {
		if ($this->startTime === -1) {
			return true;
		} else {
			if (time() - $this->startTime > $this->timeToLive) {
				$this->startTime = -1;
				return true;
			} else {
				return false;
			}
		}
	}

	public function offsetExists($offset) {
		return array_key_exists($offset, $this->data);
	}

	public function offsetGet($offset) {
		return $this->data[$offset];
	}

	public function offsetSet($offset, $value) {
		return $this->data[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}

	public function pushIdInResponse($name = UserSession::DEFAULT_REQ_DATA_NAME) {
		ExtJSResponse::put($name, $this->id);
	}

}