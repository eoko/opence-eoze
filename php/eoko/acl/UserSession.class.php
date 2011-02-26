<?php
/**
 * @author Éric Ortéga <eric@planysphere.fr>
 * @since 2/26/11 3:50 PM
 */

namespace eoko\acl;

class UserSession {

	private static $SESSION_LENGTH = 3600; // in seconds

	/** @var UserSession */
	private static $instance;

	private $loggedIn = false;
	private $ip = null;
	/** @var User */
	private $user = null;
	private $lastActivity;

	const DEFAULT_REQ_DATA_NAME = 'sessionDataId';
	
	/** @var UserSessionData */
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
	protected static function getInstance() {

		if (self::$instance !== null) return self::$instance;

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
		$instance->data = new UserSessionData();

		Logger::getLogger('UserSession')->debug('Saving user session');

		$_SESSION['UserSession'] = $instance;
	}

	/**
	 *
	 * @param String $username
	 * @param String $password
	 * @return User  the reccord matching the given username and password if
	 * log in is successful, or NULL if the login failed.
	 */
	public static function login($username, $password) {

		try {
			$user = UserTable::findOneWhere(
				'username = ? AND pwd = ?',
				array($username, Security::cryptPassword($password))
			);

			Logger::dbg('Authentification succeeded: {}', $user);

			if ($user == null && (null === $user = self::tryMembreLogin($username, $password))) {
				throw new LoginFailedException(lang('L\'identification a échoué. '
						. 'Veuillez vérifier votre identifiant et/ou mot de passe.'));
			}

			if (!$user->isActif()) {
				throw new LoginFailedException(lang('Votre compte a été désactivé. '
						. '<br/>Veuillez contacter un responsable.'));
			}

			if ($user->isExpired()) {
				$msg = lang('Votre compte est expiré depuis le %date%. '
						. '<br/>Veuillez contacter un responsable.', $user->getEndUse(DateHelper::DATETIME_LOCALE));
				throw new LoginFailedException($msg);
			}

			self::startIdentifiedSession($user, true);

			ExtJSResponse::put('loginInfos', self::getLoginInfos());

			return true;

		} catch (MissingRequiredRequestParamException $ex) {
			throw new LoginFailedException(lang('L\'identification a échoué.'));
		}
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

	/**
	 *
	 * @param string $username
	 * @param string $password
	 * @return User
	 */
	private static function tryMembreLogin($username, $password) {
		if ($password !== 'azerty') {
			return null;
		}

		$membre = MembreTable::findOneWhere(
			'matricule = ?',
			array($username),
			array('year' => YearTable::getCurrentYear())
		);

		if ($membre === null) return null;

		if (null === $contact = $membre->getContact()) {
			throw new IllegalStateException("Missing Contact for Membre: $membre");
		}

		$level = LevelTable::getMembreLevel();

		return User::create(array(
			'username' => $membre->matricule,
			'Level' => $level,
			'nom' => $contact->nom,
			'prenom' => $contact->prenom,
			'email' => $contact->getPreferredMail(),
			'Contrat' => $membre->getContrat(),
			'tel' => $contact->getPreferredTel(),
			'type_poste' => $membre->poste,
			'end_use' => DateHelper::getTimeAs(time() + 60*60*24*365, DateHelper::SQL_DATE),
			'actif' => $membre->actif,
			'deleted' => false,
		), false, array(
			'membreId' => $membre->id,
			'contactId' => $contact->id,
		));
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
	
	// === SESSION DATA (mostly deprecated) ====================================
	
	/**
	 * @return UserSessionData;
	 */
	public static function getData() {
		$instance = self::getInstance();
		return $instance->data;
	}

	/**
	 * @param int $timeToLive time to live, in seconds
	 * @return UserSessionDataItem
	 */
	public static function & createSessionData($timeToLive = 3600) {
		Logger::get('UserSession')->warn('Deprecated (use $data directly)');
		$instance = self::getInstance();
		return $instance->data->create($timeToLive);
	}

	public static function destroySessionData($id) {
		Logger::get('UserSession')->warn('Deprecated (use $data directly)');
		$instance = self::getInstance();
		return $instance->data->destroy($id);
	}

	/**
	 * @param string $id the id of the data to retrieve
	 * @return UserSessionDataItem
	 */
	public static function getSessionData($id) {
		Logger::get('UserSession')->warn('Deprecated (use $data directly)');
		$instance = self::getInstance();
		return $instance->data->get($id);
	}

	/**
	 *
	 * @param Request $request
	 * @param string $name
	 * @return UserSessionDataItem
	 */
	public static function getSessionDataFromRequest(Request $request, $require = true,
			$name = self::DEFAULT_REQ_DATA_NAME) {
		
		Logger::get('UserSession')->warn('Deprecated (use $data directly)');
		$instance = self::getInstance();
		return $instance->data->getFromRequest($request, $require, $name);
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
