<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 * @license http://www.planysphere.fr/licenses/psopence.txt
 */

if (!isset($GLOBALS['directAccess'])) { header('HTTP/1.0 404 Not Found'); exit('Not found'); }

class Security {

	/**
	 * @author Romain Dary
	 */
	static function restricted($level) {

		if (!isset($_SESSION['usr.level'])
				|| ! isset($_SESSION['usr.nom'])
				|| $_SESSION['usr.ip'] != getenv("REMOTE_ADDR")
				|| $_SESSION['usr.level'] > $level) {

			@session_destroy();
			header('Location:index.html');
			exit();
			die();
			// And now it is dead, double-dead :D !
		}
	}

	static function cryptPassword($password) {
		return sha1($password);
	}
}

