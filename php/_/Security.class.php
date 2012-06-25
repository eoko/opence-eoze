<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 * @license http://www.planysphere.fr/licenses/psopence.txt
 */

if (!isset($GLOBALS['directAccess'])) { header('HTTP/1.0 404 Not Found'); exit('Not found'); }

class Security {

	static function cryptPassword($password) {
		return sha1($password);
	}
}

