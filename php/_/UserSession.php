<?php

use eoko\security\LoginAdapter;
use eoko\security\LoginAdapter\DefaultLoginAdapter;
use eoko\php\SessionManager;

/**
 * @author Éric Ortéga <eric@mail.com>
 */
class UserSession {

	public static $SESSION_LENGTH = 3600; // in seconds

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

	/**
	 * @var SessionManager
	 */
	private static $sessionManager;

	private function __construct() {
		$this->loggedIn = false;
		$this->ip = getenv('REMOTE_ADDR');
		$this->user = null;
		$this->lastActivity = time();
	}

	public static function setSessionManager(SessionManager $sessionManager) {
		self::$sessionManager = $sessionManager;
	}

	/**
	 * @return UserSession
	 */
	private static function getInstance($updateLastActivity = true) {

		if (self::$instance !== null) {
			return self::$instance;
		}

		$session = self::$sessionManager->getData(false);

		if (self::$instance === null) {
			if (isset($session['UserSession'])) {

				$storedSession = $session['UserSession'];

				Logger::getLogger(__CLASS__)->debug('Found stored user session');

				if ($storedSession instanceof UserSession) {
					if ($storedSession->ip === getenv('REMOTE_ADDR')) {
						self::$instance = $storedSession;
					} else {
						Logger::getLogger(__CLASS__)->warn(
							'Request IP {} not matching stored IP {} of identified user',
							getenv("REMOTE_ADDR"), $storedSession->ip
						);
					}
				} else {
					Logger::getLogger(__CLASS__)->warn(
						'Value stored in session at "UserSession" is not UserSession object');
				}
			}
		}

		// Session has not been started
		if (self::$instance === null) {
			Logger::getLogger(__CLASS__)->debug('No valid user session stored');
			self::$instance = new UserSession();
		}

		// User session has expired
		else if (self::$instance->isIdentified()) {

			if (self::$instance->isExpired()) {
				self::$instance->loggedIn = false;
			}

			// We should update last activity right now
			else if ($updateLastActivity) {
				self::$instance->lastActivity = time();

				self::$sessionManager->put('UserSession', self::$instance);
			}
		}

		self::$sessionManager->commit();

//		Logger::dbg('Session user is: {}', self::$instance->user);

		return self::$instance;
	}

	/**
	 * @deprecated Not supported anymore, will throw an exception
	 */
	public static function updateUserLastActivity() {

		throw new DeprecatedException();

		$instance = self::getInstance();
		if (($now = time()) - $instance->lastActivity > self::$SESSION_LENGTH) {
			if ($instance->isIdentified()) {
				$instance->loggedIn = false;
			}
		} else {
			$instance->lastActivity = $now;
		}
	}

	private function isExpired(&$now = null) {
		if (!$this->loggedIn) {
			throw new IllegalStateException();
		}
		if ($now === null) {
			$now = time();
		}
		return $now - $this->lastActivity >= self::$SESSION_LENGTH;
	}

	public static function getExpirationDelay($now = null) {
		if ($now === null) $now = time();
		return self::getInstance(false)->lastActivity + self::$SESSION_LENGTH - $now;
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

		self::$sessionManager->put('UserSession', $instance)->commit();
	}

	private static $loginAdapter;

	public static function setLoginAdapter(LoginAdapter $adapter) {
		if (self::$loginAdapter) {
			throw new \RuntimeException('LoginAdapter already set!');
		}
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
			foreach (self::$loginListeners as $callback) {
				call_user_func($callback, $user);
			}
		}
	}

	public static function onLogin($callback) {
		self::$loginListeners[] = $callback;
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
		self::$sessionManager->destroySession();
	}

	public static function isIdentified($updateLastActivity = true) {
//		self::startIdentifiedSession(User::load(84), true);
		return self::getInstance($updateLastActivity)->loggedIn;
	}

	public static function isLoginRequestSet() {
		return isset($_POST['login-user']) && $_POST['login-user'] != ''
					&& isset($_POST['login-pwd']) && $_POST['login-pwd'];
	}

	/**
	 * @return User
	 */
	public static function getUser($updateLastActivity = true) {
		return self::getInstance($updateLastActivity)->user;
	}

	public static function getUserId($updateLastActivity = true) {
		if (null !== $user = self::getUser($updateLastActivity)) {
			return $user->getId();
		} else {
			return null;
		}
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
	 * @deprecated Not supported anymore, will throw an Exception.
	 */
	public static function & createSessionData($timeToLive = 3600) {

		throw new DeprecatedException();

		$instance = self::getInstance();

		do {
			$id = StringHelper::randomString(8);
		} while(isset($instance->data[$id]));

		$instance->data[$id] = new UserSessionDataItem($id, $timeToLive);

		return $instance->data[$id];
	}

	/**
	 * @deprecated Not supported anymore, will throw an Exception.
	 */
	public static function destroySessionData($id) {

		throw new DeprecatedException();

		// We don't really want to check for the session existance, which would
		// produce a completly meaningless error warning...
		unset(self::getInstance()->data[$id]);
		return true;
	}

	/**
	 * @deprecated Not supported anymore, will throw an Exception.
	 */
	public static function getSessionData($id) {

		throw new DeprecatedException();

		$instance = self::getInstance();
		return isset($instance->data[$id]) ? $instance->data[$id] : null;
	}

	/**
	 * @deprecated Not supported anymore, will throw an Exception.
	 */
	public static function getSessionDataFromRequest(Request $request, $require = true,
			$name = self::DEFAULT_REQ_DATA_NAME) {

		throw new DeprecatedException();

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
